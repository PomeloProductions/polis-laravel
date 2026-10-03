<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Plugins;

use Polis\Plugins\PluginContract;
use Polis\Plugins\PluginManager;
use Polis\Plugins\PluginRegistrar;
use Polis\Tests\TestCase;

/**
 * A minimal in-memory contract + concrete used to prove binding application.
 */
interface FakePluginServiceContract
{
    public function id(): string;
}

final class FakePluginService implements FakePluginServiceContract
{
    public function id(): string
    {
        return 'fake-service';
    }
}

/**
 * A fake plugin that exercises a binding, a listener, a migration path, and
 * namespaced config.
 */
final class FakePlugin implements PluginContract
{
    public const MIGRATION_PATH = '/tmp/polis-fake-plugin-migrations';

    public function key(): string
    {
        return 'fake';
    }

    public function name(): string
    {
        return 'Fake Plugin';
    }

    public function version(): string
    {
        return '1.2.3';
    }

    public function capabilities(): array
    {
        return ['bindings', 'listeners', 'migrations', 'config'];
    }

    public function register(PluginRegistrar $registrar): void
    {
        $registrar
            ->bindings([
                FakePluginServiceContract::class => FakePluginService::class,
            ])
            ->listeners([
                'fake.event' => ['FakeListenerOne', 'FakeListenerTwo'],
            ])
            ->migrations(self::MIGRATION_PATH)
            ->config('defaults', ['per_page' => 25]);
    }
}

final class PluginManagerTest extends TestCase
{
    private function manager(): PluginManager
    {
        return new PluginManager($this->app);
    }

    public function test_no_plugins_is_a_clean_no_op(): void
    {
        config(['plugins.enabled' => []]);

        $manager = $this->manager();

        $this->assertSame([], $manager->plugins());
        $this->assertSame([], $manager->manifest());
        $this->assertSame([], $manager->aggregatedListeners());
        $this->assertSame([], $manager->aggregatedObservers());

        // Applying everything against an empty config must not throw or bind.
        $manager->applyBindings();
        $manager->applyConfig();
        $loaded = [];
        $manager->applyMigrations(function (string $path) use (&$loaded): void {
            $loaded[] = $path;
        });

        $this->assertSame([], $loaded);
        $this->assertFalse($this->app->bound(FakePluginServiceContract::class));
    }

    public function test_discovers_plugin_from_config(): void
    {
        config(['plugins.enabled' => [FakePlugin::class]]);

        $manager = $this->manager();
        $plugins = $manager->plugins();

        $this->assertCount(1, $plugins);
        $this->assertInstanceOf(FakePlugin::class, $plugins[0]);

        $this->assertSame([[
            'key' => 'fake',
            'name' => 'Fake Plugin',
            'version' => '1.2.3',
            'capabilities' => ['bindings', 'listeners', 'migrations', 'config'],
        ]], $manager->manifest());
    }

    public function test_applies_bindings(): void
    {
        config(['plugins.enabled' => [FakePlugin::class]]);

        $manager = $this->manager();
        $manager->applyBindings();

        $this->assertTrue($this->app->bound(FakePluginServiceContract::class));
        $resolved = $this->app->make(FakePluginServiceContract::class);
        $this->assertInstanceOf(FakePluginService::class, $resolved);
        $this->assertSame('fake-service', $resolved->id());
    }

    public function test_aggregates_listeners(): void
    {
        config(['plugins.enabled' => [FakePlugin::class]]);

        $this->assertSame([
            'fake.event' => ['FakeListenerOne', 'FakeListenerTwo'],
        ], $this->manager()->aggregatedListeners());
    }

    public function test_applies_plugin_migration_paths(): void
    {
        config(['plugins.enabled' => [FakePlugin::class]]);

        $loaded = [];
        $this->manager()->applyMigrations(function (string $path) use (&$loaded): void {
            $loaded[] = $path;
        });

        $this->assertSame([FakePlugin::MIGRATION_PATH], $loaded);
    }

    public function test_applies_namespaced_config(): void
    {
        config(['plugins.enabled' => [FakePlugin::class]]);

        $manager = $this->manager();
        $manager->applyConfig();

        $this->assertSame(25, config('plugin.fake.defaults.per_page'));
    }

    public function test_consumer_config_wins_over_plugin_default(): void
    {
        config([
            'plugins.enabled' => [FakePlugin::class],
            'plugin.fake.defaults' => ['per_page' => 99],
        ]);

        $manager = $this->manager();
        $manager->applyConfig();

        // Consumer value is preserved; plugin default only fills gaps.
        $this->assertSame(99, config('plugin.fake.defaults.per_page'));
    }

    public function test_discovery_is_idempotent(): void
    {
        config(['plugins.enabled' => [FakePlugin::class]]);

        $manager = $this->manager();
        $first = $manager->discover();
        $second = $manager->discover();

        $this->assertSame($first, $second);
    }

    public function test_duplicate_keys_are_de_duplicated(): void
    {
        config(['plugins.enabled' => [FakePlugin::class, FakePlugin::class]]);

        $this->assertCount(1, $this->manager()->plugins());
    }
}
