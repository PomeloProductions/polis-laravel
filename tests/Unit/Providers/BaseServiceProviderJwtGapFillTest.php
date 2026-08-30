<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Illuminate\Config\Repository;
use Illuminate\Support\Env;
use Polis\Providers\BaseServiceProvider;
use Polis\Tests\TestCase;

/**
 * Tests for {@see BaseServiceProvider::applyJwtConfigGapFill()}.
 *
 * tymon/jwt-auth ships `blacklist_grace_period => 0`, meaning a token is
 * invalid the instant it is rotated on refresh. Two concurrent refreshes then
 * race — the second presents a token the first just blacklisted and gets a
 * 401, logging the user out. The gap-fill raises the platform default to 30s
 * so a just-rotated token keeps working briefly, while still honouring an
 * explicit JWT_BLACKLIST_GRACE_PERIOD env or a deliberate app value.
 *
 * Like the redis gap-fill tests, this is a pure config-repository mutator, so
 * it's exercised directly against a fresh Repository.
 */
final class BaseServiceProviderJwtGapFillTest extends TestCase
{
    private const JWT_ENV_KEYS = [
        'JWT_BLACKLIST_GRACE_PERIOD',
    ];

    /** @var array<string, mixed> */
    private array $envSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $repo = Env::getRepository();
        foreach (self::JWT_ENV_KEYS as $key) {
            $this->envSnapshot[$key] = $repo->has($key) ? $repo->get($key) : null;
            $repo->clear($key);
        }
    }

    protected function tearDown(): void
    {
        $repo = Env::getRepository();
        foreach (self::JWT_ENV_KEYS as $key) {
            $value = $this->envSnapshot[$key] ?? null;
            if ($value === null) {
                $repo->clear($key);
            } else {
                $repo->set($key, $value);
            }
        }

        parent::tearDown();
    }

    private function setEnv(string $key, string $value): void
    {
        Env::getRepository()->set($key, $value);
    }

    public function test_raises_stock_zero_grace_to_platform_default(): void
    {
        $config = new Repository(['jwt' => ['blacklist_grace_period' => 0]]);

        BaseServiceProvider::applyJwtConfigGapFill($config);

        $this->assertSame(
            BaseServiceProvider::DEFAULT_JWT_BLACKLIST_GRACE_PERIOD,
            $config->get('jwt.blacklist_grace_period'),
        );
    }

    public function test_env_override_wins(): void
    {
        $config = new Repository(['jwt' => ['blacklist_grace_period' => 0]]);
        $this->setEnv('JWT_BLACKLIST_GRACE_PERIOD', '90');

        BaseServiceProvider::applyJwtConfigGapFill($config);

        $this->assertSame(90, $config->get('jwt.blacklist_grace_period'));
    }

    public function test_env_can_explicitly_force_zero(): void
    {
        $config = new Repository(['jwt' => ['blacklist_grace_period' => 15]]);
        $this->setEnv('JWT_BLACKLIST_GRACE_PERIOD', '0');

        BaseServiceProvider::applyJwtConfigGapFill($config);

        $this->assertSame(0, $config->get('jwt.blacklist_grace_period'));
    }

    public function test_does_not_override_deliberate_app_value(): void
    {
        // App already chose a non-zero grace period (no env) — leave it alone.
        $config = new Repository(['jwt' => ['blacklist_grace_period' => 60]]);

        BaseServiceProvider::applyJwtConfigGapFill($config);

        $this->assertSame(60, $config->get('jwt.blacklist_grace_period'));
    }

    public function test_no_op_when_jwt_config_absent(): void
    {
        // jwt-auth not configured for this consumer — nothing to fill.
        $config = new Repository(['app' => ['name' => 'x']]);

        BaseServiceProvider::applyJwtConfigGapFill($config);

        $this->assertNull($config->get('jwt'));
    }
}
