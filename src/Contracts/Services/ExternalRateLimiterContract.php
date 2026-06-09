<?php

declare(strict_types=1);

namespace Polis\Contracts\Services;

use Polis\Exceptions\RateLimitExceededException;
use Polis\Models\User\ExternalAccountConnection;

/**
 * Outbound rate limiting for calls to third-party APIs that polis-laravel
 * tracks via {@see ExternalAccountConnection}.
 *
 * Two modes:
 *
 *  1. attempt() — per-account quota with explicit max/decay. Throws
 *     {@see RateLimitExceededException} when the quota is exhausted. Use
 *     this for documented "X requests / Y seconds per token" limits.
 *
 *  2. gate() — global minimum-gap throttle across ALL calls for a service.
 *     Sleeps the current process until at least `min_gap_seconds` have
 *     elapsed since the previous call. Use this when the upstream tolerates
 *     bursts poorly (CDN-style flagging) and you'd rather block than 429.
 *
 * Both modes are keyed; combining them is allowed and common (per-account
 * quota for normal flow + global gate for crawlers).
 */
interface ExternalRateLimiterContract
{
    /**
     * Atomically reserve one call against the (provider, account) quota.
     * Increments the cache counter and throws if the quota is now full.
     *
     * Implementations MUST use the same atomic primitive Laravel's
     * RateLimiter facade uses so concurrent workers cannot both squeeze in
     * the final request.
     *
     * @param  string  $provider  Provider slug, matches ExternalAccountConnection::provider.
     * @param  int|string  $accountId  Identifier for the throttled account (typically the connection id).
     * @param  int  $maxAttempts  Cap of attempts allowed within the decay window.
     * @param  int  $decaySeconds  Seconds the window persists after the first attempt.
     *
     * @throws RateLimitExceededException When the cap has already been hit.
     */
    public function attempt(string $provider, int|string $accountId, int $maxAttempts, int $decaySeconds): void;

    /**
     * Read the remaining attempts in the current window without consuming
     * one. Useful for surfacing quota state to UIs / 429 responses.
     */
    public function remaining(string $provider, int|string $accountId, int $maxAttempts): int;

    /**
     * Reset the (provider, account) counter to zero. Call after a refresh
     * token rotation or any operation that should restore a fresh window.
     */
    public function clear(string $provider, int|string $accountId): void;

    /**
     * Sleep the current process until at least `min_gap_seconds` have
     * elapsed since the previous `gate()` call on this `$service`. Useful
     * for serialising outbound traffic to a single upstream so the shared
     * outbound IP does not get CDN-flagged.
     *
     * The call is best-effort: it relies on a shared cache value and a
     * single-worker assumption. Multi-worker deployments should add a
     * Redis-backed mutex around the critical section if strict ordering
     * is required.
     */
    public function gate(string $service, ?string $url = null): void;
}
