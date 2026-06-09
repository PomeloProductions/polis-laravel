<?php

declare(strict_types=1);

namespace Polis\Exceptions;

use Polis\Services\ExternalRateLimiter;
use RuntimeException;
use Throwable;

/**
 * Thrown by {@see ExternalRateLimiter::attempt()} when the
 * configured per-account quota has been exhausted within its decay window.
 *
 * Carries the seconds-until-available so HTTP layers can surface a
 * `Retry-After` header without re-querying the limiter.
 */
class RateLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $key,
        public readonly int $retryAfterSeconds,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'External rate limit exceeded for "%s"; retry in %d seconds.',
                $key,
                $retryAfterSeconds,
            ),
            0,
            $previous,
        );
    }
}
