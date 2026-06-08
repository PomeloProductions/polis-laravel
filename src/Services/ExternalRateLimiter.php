<?php

declare(strict_types=1);

namespace Polis\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Polis\Contracts\Services\ExternalRateLimiterContract;
use Polis\Exceptions\RateLimitExceededException;
use Psr\Log\LoggerInterface as LogContract;

/**
 * Concrete {@see ExternalRateLimiterContract}.
 *
 * Backed by Laravel's framework {@see RateLimiter} for atomic quota
 * tracking and a plain Cache repository for the global minimum-gap
 * timestamp used by gate().
 */
class ExternalRateLimiter implements ExternalRateLimiterContract
{
    private const GATE_CACHE_KEY_PREFIX = 'polis:external:gate:';

    private const GATE_CACHE_TTL_SECONDS = 300;

    private const DEFAULT_MIN_GAP_SECONDS = 20;

    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly CacheContract $cache,
        private readonly LogContract $log,
        private readonly int $defaultMinGapSeconds = self::DEFAULT_MIN_GAP_SECONDS,
    ) {}

    public function attempt(string $provider, int|string $accountId, int $maxAttempts, int $decaySeconds): void
    {
        $key = $this->quotaKey($provider, $accountId);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            throw new RateLimitExceededException(
                key: $key,
                retryAfterSeconds: $this->limiter->availableIn($key),
            );
        }

        $this->limiter->hit($key, $decaySeconds);
    }

    public function remaining(string $provider, int|string $accountId, int $maxAttempts): int
    {
        return $this->limiter->remaining($this->quotaKey($provider, $accountId), $maxAttempts);
    }

    public function clear(string $provider, int|string $accountId): void
    {
        $this->limiter->clear($this->quotaKey($provider, $accountId));
    }

    public function gate(string $service, ?string $url = null): void
    {
        $cacheKey = self::GATE_CACHE_KEY_PREFIX.$service;
        $minGapMs = $this->defaultMinGapSeconds * 1000;
        $nowMs = $this->nowMilliseconds();
        $lastAt = (int) ($this->cache->get($cacheKey) ?? 0);

        if ($lastAt > 0) {
            $elapsedMs = $nowMs - $lastAt;
            if ($elapsedMs < $minGapMs) {
                $sleepMs = $minGapMs - $elapsedMs;
                $this->log->info('ExternalRateLimiter: throttling', [
                    'service' => $service,
                    'sleep_seconds' => round($sleepMs / 1000, 1),
                    'min_gap_seconds' => $this->defaultMinGapSeconds,
                    'url' => $url,
                ]);
                $this->sleepMilliseconds($sleepMs);
            }
        }

        // Stamp the new request after any wait so the next caller sees when
        // WE went out, not when we started waiting.
        $this->cache->put($cacheKey, $this->nowMilliseconds(), self::GATE_CACHE_TTL_SECONDS);
    }

    /**
     * Cache key for the per-(provider, account) quota. Underscores rather
     * than colons because some cache stores (notably the array store used
     * in tests) tolerate both but Memcached's key validator rejects colons
     * on some configurations.
     */
    private function quotaKey(string $provider, int|string $accountId): string
    {
        return sprintf('polis_external_quota_%s_%s', $provider, $accountId);
    }

    /**
     * Indirection points for tests — overrideable in subclasses so unit
     * tests can avoid wall-clock dependency. Defaults are the obvious
     * production implementations.
     */
    protected function nowMilliseconds(): int
    {
        return (int) (microtime(true) * 1000);
    }

    protected function sleepMilliseconds(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
