<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Illuminate\Config\Repository;
use Illuminate\Support\Env;
use Polis\Providers\BaseServiceProvider;
use Polis\Tests\TestCase;

/**
 * Tests for {@see BaseServiceProvider::applyRedisConfigGapFill()}.
 *
 * The gap-fill exists because the Athenia-based consumer apps ship a
 * stripped config/database.php whose `redis` section has ONLY a `default`
 * connection and NO options.prefix, while config/cache.php points the redis
 * cache store at a `cache` connection. Without the gap-fill, setting
 * CACHE_STORE=redis throws "Redis connection [cache] not configured" and
 * REDIS_PREFIX is silently ignored (no tenant key isolation).
 *
 * The method is a pure config-repository mutator, so these tests call it
 * directly against a fresh Repository (no full BaseServiceProvider boot,
 * which would drag in consumer-app FQNs — matching the pattern used by the
 * other Base*ProviderResolveTest cases in this suite).
 */
final class BaseServiceProviderRedisGapFillTest extends TestCase
{
    /**
     * Env keys the gap-fill reads. Snapshotted in setUp and restored in
     * tearDown so tests don't leak env state into each other.
     */
    private const REDIS_ENV_KEYS = [
        'REDIS_PREFIX',
        'REDIS_URL',
        'REDIS_HOST',
        'REDIS_USERNAME',
        'REDIS_PASSWORD',
        'REDIS_PORT',
        'REDIS_DB',
        'REDIS_CACHE_DB',
    ];

    /** @var array<string, mixed> */
    private array $envSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $repo = Env::getRepository();
        foreach (self::REDIS_ENV_KEYS as $key) {
            $this->envSnapshot[$key] = $repo->has($key) ? $repo->get($key) : null;
            $repo->clear($key);
        }
    }

    protected function tearDown(): void
    {
        $repo = Env::getRepository();
        foreach (self::REDIS_ENV_KEYS as $key) {
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

    public function test_fills_cache_connection_and_prefix_when_app_only_has_default(): void
    {
        // Simulate the Athenia stripped config: only a `default` redis
        // connection, no options.prefix, no `cache` connection.
        $config = new Repository([
            'database' => [
                'redis' => [
                    'default' => [
                        'host' => '10.0.0.1',
                        'port' => '6379',
                        'database' => '0',
                    ],
                ],
            ],
        ]);

        // Platform-provided redis env.
        $this->setEnv('REDIS_PREFIX', 'tenant_42_');
        $this->setEnv('REDIS_HOST', 'redis.internal');
        $this->setEnv('REDIS_PASSWORD', 's3cret');
        $this->setEnv('REDIS_PORT', '6380');
        $this->setEnv('REDIS_CACHE_DB', '3');

        BaseServiceProvider::applyRedisConfigGapFill($config);

        // Prefix now reflects REDIS_PREFIX so tenant keys are isolated.
        $this->assertSame('tenant_42_', $config->get('database.redis.options.prefix'));

        // The `cache` connection is now populated from env.
        $cache = $config->get('database.redis.cache');
        $this->assertIsArray($cache);
        $this->assertSame('redis.internal', $cache['host']);
        $this->assertSame('s3cret', $cache['password']);
        $this->assertSame('6380', $cache['port']);
        $this->assertSame('3', $cache['database']);

        // The existing `default` connection is untouched.
        $this->assertSame('10.0.0.1', $config->get('database.redis.default.host'));
    }

    public function test_uses_env_defaults_when_env_is_absent(): void
    {
        $config = new Repository([
            'database' => ['redis' => ['default' => ['host' => '127.0.0.1']]],
        ]);

        BaseServiceProvider::applyRedisConfigGapFill($config);

        // Empty prefix default (no isolation, but no crash) and the standard
        // Laravel defaults for the cache connection on logical DB 1.
        $this->assertSame('', $config->get('database.redis.options.prefix'));
        $this->assertSame('127.0.0.1', $config->get('database.redis.cache.host'));
        $this->assertSame('6379', $config->get('database.redis.cache.port'));
        $this->assertSame('1', $config->get('database.redis.cache.database'));
        $this->assertNull($config->get('database.redis.cache.password'));
    }

    public function test_fills_default_connection_when_missing(): void
    {
        // No redis connections at all.
        $config = new Repository(['database' => ['redis' => []]]);

        $this->setEnv('REDIS_HOST', 'redis.internal');
        $this->setEnv('REDIS_DB', '5');

        BaseServiceProvider::applyRedisConfigGapFill($config);

        $default = $config->get('database.redis.default');
        $this->assertIsArray($default);
        $this->assertSame('redis.internal', $default['host']);
        $this->assertSame('5', $default['database']);
    }

    public function test_does_not_override_app_provided_cache_connection_and_prefix(): void
    {
        // Simulate an app (e.g. PolisOS #40) that ALREADY defines everything
        // explicitly. The gap-fill must leave it completely untouched.
        $config = new Repository([
            'database' => [
                'redis' => [
                    'options' => ['prefix' => 'app_defined_prefix_'],
                    'default' => ['host' => 'app-default-host', 'database' => '0'],
                    'cache' => ['host' => 'app-cache-host', 'database' => '9'],
                ],
            ],
        ]);

        // Different env values — these must NOT win over the app config.
        $this->setEnv('REDIS_PREFIX', 'env_prefix_');
        $this->setEnv('REDIS_HOST', 'env-host');
        $this->setEnv('REDIS_CACHE_DB', '1');

        BaseServiceProvider::applyRedisConfigGapFill($config);

        $this->assertSame('app_defined_prefix_', $config->get('database.redis.options.prefix'));
        $this->assertSame('app-cache-host', $config->get('database.redis.cache.host'));
        $this->assertSame('9', $config->get('database.redis.cache.database'));
        $this->assertSame('app-default-host', $config->get('database.redis.default.host'));
    }

    public function test_empty_string_prefix_is_treated_as_unset_and_filled(): void
    {
        // Laravel's stock config often ships options.prefix = '' (or a
        // computed empty value). Treat empty as "not set" so REDIS_PREFIX
        // still takes effect for tenant isolation.
        $config = new Repository([
            'database' => [
                'redis' => [
                    'options' => ['prefix' => ''],
                    'default' => ['host' => '127.0.0.1'],
                ],
            ],
        ]);

        $this->setEnv('REDIS_PREFIX', 'tenant_7_');

        BaseServiceProvider::applyRedisConfigGapFill($config);

        $this->assertSame('tenant_7_', $config->get('database.redis.options.prefix'));
    }
}
