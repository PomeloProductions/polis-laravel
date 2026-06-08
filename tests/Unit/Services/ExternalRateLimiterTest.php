<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Polis\Exceptions\RateLimitExceededException;
use Polis\Services\ExternalRateLimiter;
use Polis\Tests\TestCase;
use Psr\Log\NullLogger;

/**
 * Unit coverage for {@see ExternalRateLimiter}.
 *
 * Backed by the live array cache + Laravel's own RateLimiter so we exercise
 * the same atomic hit() / availableIn() semantics that production would.
 * No clock mocking is needed for the quota tests; the gate() test uses a
 * trivial subclass that overrides the clock + sleep so we can assert
 * the throttle math without burning real time.
 */
final class ExternalRateLimiterTest extends TestCase
{
    private function makeLimiter(?int $minGapSeconds = null): ExternalRateLimiter
    {
        // Use the framework's RateLimiter bound against the array cache
        // store that phpunit.xml configures (CACHE_DRIVER=array).
        $framework = $this->app->make(RateLimiter::class);
        $cache = $this->app->make(CacheContract::class);

        return new ExternalRateLimiter(
            $framework,
            $cache,
            new NullLogger,
            $minGapSeconds ?? 20,
        );
    }

    public function test_attempt_allows_calls_under_the_cap(): void
    {
        $limiter = $this->makeLimiter();

        // 3 attempts in a row, cap = 3 → all succeed.
        for ($i = 0; $i < 3; $i++) {
            $limiter->attempt('github', 42, maxAttempts: 3, decaySeconds: 60);
        }

        $this->assertSame(0, $limiter->remaining('github', 42, maxAttempts: 3));
    }

    public function test_attempt_throws_when_cap_exceeded(): void
    {
        $limiter = $this->makeLimiter();

        $limiter->attempt('github', 42, maxAttempts: 2, decaySeconds: 60);
        $limiter->attempt('github', 42, maxAttempts: 2, decaySeconds: 60);

        $this->expectException(RateLimitExceededException::class);
        $limiter->attempt('github', 42, maxAttempts: 2, decaySeconds: 60);
    }

    public function test_exception_carries_retry_after_seconds(): void
    {
        $limiter = $this->makeLimiter();

        $limiter->attempt('github', 42, maxAttempts: 1, decaySeconds: 60);

        try {
            $limiter->attempt('github', 42, maxAttempts: 1, decaySeconds: 60);
            $this->fail('Expected RateLimitExceededException.');
        } catch (RateLimitExceededException $exception) {
            $this->assertGreaterThan(0, $exception->retryAfterSeconds);
            // The retry-after must not exceed the decay window we asked for.
            $this->assertLessThanOrEqual(60, $exception->retryAfterSeconds);
            $this->assertStringContainsString('github', $exception->key);
            $this->assertStringContainsString('42', $exception->key);
        }
    }

    public function test_quotas_are_keyed_independently_per_account(): void
    {
        $limiter = $this->makeLimiter();

        $limiter->attempt('github', 1, maxAttempts: 1, decaySeconds: 60);
        // Different account on same provider must NOT be affected.
        $limiter->attempt('github', 2, maxAttempts: 1, decaySeconds: 60);

        // Both accounts now have 0 remaining; both would throw on next hit.
        $this->assertSame(0, $limiter->remaining('github', 1, 1));
        $this->assertSame(0, $limiter->remaining('github', 2, 1));
    }

    public function test_quotas_are_keyed_independently_per_provider(): void
    {
        $limiter = $this->makeLimiter();

        $limiter->attempt('github', 42, maxAttempts: 1, decaySeconds: 60);
        // Same account id, different provider — separate bucket.
        $limiter->attempt('discord', 42, maxAttempts: 1, decaySeconds: 60);

        $this->assertSame(0, $limiter->remaining('github', 42, 1));
        $this->assertSame(0, $limiter->remaining('discord', 42, 1));
    }

    public function test_clear_resets_the_quota(): void
    {
        $limiter = $this->makeLimiter();

        $limiter->attempt('github', 99, maxAttempts: 1, decaySeconds: 60);
        $this->assertSame(0, $limiter->remaining('github', 99, 1));

        $limiter->clear('github', 99);
        $this->assertSame(1, $limiter->remaining('github', 99, 1));

        // After clear, attempting again must NOT throw.
        $limiter->attempt('github', 99, maxAttempts: 1, decaySeconds: 60);
        $this->assertSame(0, $limiter->remaining('github', 99, 1));
    }

    public function test_remaining_returns_full_quota_for_untouched_key(): void
    {
        $limiter = $this->makeLimiter();

        $this->assertSame(5, $limiter->remaining('never_touched', 'nobody', maxAttempts: 5));
    }

    public function test_gate_does_not_sleep_on_first_call(): void
    {
        $limiter = new RecordingExternalRateLimiter(
            $this->app->make(RateLimiter::class),
            $this->app->make(CacheContract::class),
            new NullLogger,
            defaultMinGapSeconds: 20,
        );

        $limiter->setNow(1_000_000);

        $limiter->gate('upstream-api', 'https://example.test/x');

        $this->assertSame(0, $limiter->totalSleepMs);
    }

    public function test_gate_sleeps_until_min_gap_elapsed(): void
    {
        $limiter = new RecordingExternalRateLimiter(
            $this->app->make(RateLimiter::class),
            $this->app->make(CacheContract::class),
            new NullLogger,
            defaultMinGapSeconds: 20,
        );

        // First call lands at t=1,000,000ms (clock anchor — see note below).
        $limiter->setNow(1_000_000);
        $limiter->gate('upstream-api');

        // Second call lands 5 seconds later — needs to sleep 15s to hit the
        // 20s minimum gap.
        $limiter->setNow(1_005_000);
        $limiter->gate('upstream-api');

        $this->assertSame(15_000, $limiter->totalSleepMs);
    }

    public function test_gate_does_not_sleep_when_min_gap_already_elapsed(): void
    {
        $limiter = new RecordingExternalRateLimiter(
            $this->app->make(RateLimiter::class),
            $this->app->make(CacheContract::class),
            new NullLogger,
            defaultMinGapSeconds: 20,
        );

        $limiter->setNow(1_000_000);
        $limiter->gate('upstream-api');

        // 25 seconds later — no sleep required.
        $limiter->setNow(1_025_000);
        $limiter->gate('upstream-api');

        $this->assertSame(0, $limiter->totalSleepMs);
    }

    public function test_gate_is_keyed_per_service(): void
    {
        $limiter = new RecordingExternalRateLimiter(
            $this->app->make(RateLimiter::class),
            $this->app->make(CacheContract::class),
            new NullLogger,
            defaultMinGapSeconds: 20,
        );

        $limiter->setNow(1_000_000);
        $limiter->gate('service-a');

        // service-b's clock is independent of service-a's, so this call
        // must NOT sleep even though only 1ms has passed on the wall clock.
        $limiter->setNow(1_000_001);
        $limiter->gate('service-b');

        $this->assertSame(0, $limiter->totalSleepMs);
    }
}

/**
 * Test-only subclass that exposes the clock + sleep hooks so gate()
 * timing can be asserted deterministically.
 */
final class RecordingExternalRateLimiter extends ExternalRateLimiter
{
    public int $totalSleepMs = 0;

    private int $now = 0;

    public function setNow(int $milliseconds): void
    {
        $this->now = $milliseconds;
    }

    protected function nowMilliseconds(): int
    {
        return $this->now;
    }

    protected function sleepMilliseconds(int $milliseconds): void
    {
        $this->totalSleepMs += $milliseconds;
        // Do NOT actually sleep — that would defeat the purpose.
    }
}
