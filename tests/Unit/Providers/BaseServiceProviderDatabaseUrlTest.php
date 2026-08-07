<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Providers;

use Illuminate\Support\ConfigurationUrlParser;
use Polis\Providers\BaseServiceProvider;
use Polis\Tests\TestCase;

/**
 * Tests for {@see BaseServiceProvider::resolveDatabaseUrlOverrides()}.
 *
 * The client-driver controller injects tenant DB credentials as a single
 * `DATABASE_URL`. Laravel only derives host/port/database/username/password
 * from such a URL when the target connection config carries a `url` key.
 * This helper decides, per connection, whether to backfill that key so the
 * framework honors `DATABASE_URL` without clobbering discrete `DB_*` config.
 *
 * Like {@see BaseServiceProviderResolveTest}, these are intentionally narrow
 * and Mockery-free: they exercise the pure decision logic directly.
 */
final class BaseServiceProviderDatabaseUrlTest extends TestCase
{
    private const URL = 'mysql://user:pass@db-host:25060/tenant_db';

    public function test_no_overrides_when_database_url_is_unset(): void
    {
        $result = BaseServiceProvider::resolveDatabaseUrlOverrides(
            null,
            ['mysql' => ['driver' => 'mysql', 'host' => '127.0.0.1']],
        );

        $this->assertSame([], $result);
    }

    public function test_no_overrides_when_database_url_is_empty_string(): void
    {
        $result = BaseServiceProvider::resolveDatabaseUrlOverrides(
            '',
            ['mysql' => ['driver' => 'mysql']],
        );

        $this->assertSame([], $result);
    }

    public function test_backfills_url_on_mysql_and_pgsql_when_present(): void
    {
        $result = BaseServiceProvider::resolveDatabaseUrlOverrides(
            self::URL,
            [
                'mysql' => ['driver' => 'mysql'],
                'pgsql' => ['driver' => 'pgsql'],
                'sqlite' => ['driver' => 'sqlite'],
            ],
        );

        // sqlite is intentionally left alone; only the standard SQL server
        // connections get the URL backfilled.
        $this->assertSame([
            'mysql' => self::URL,
            'pgsql' => self::URL,
        ], $result);
    }

    public function test_only_touches_connections_that_exist(): void
    {
        $result = BaseServiceProvider::resolveDatabaseUrlOverrides(
            self::URL,
            ['mysql' => ['driver' => 'mysql']],
        );

        $this->assertSame(['mysql' => self::URL], $result);
    }

    public function test_respects_an_explicit_url_already_configured(): void
    {
        $result = BaseServiceProvider::resolveDatabaseUrlOverrides(
            self::URL,
            [
                'mysql' => ['driver' => 'mysql', 'url' => 'mysql://other/db'],
                'pgsql' => ['driver' => 'pgsql'],
            ],
        );

        // mysql already had a url -> untouched; pgsql had none -> backfilled.
        $this->assertSame(['pgsql' => self::URL], $result);
    }

    public function test_treats_empty_existing_url_as_absent(): void
    {
        $result = BaseServiceProvider::resolveDatabaseUrlOverrides(
            self::URL,
            ['mysql' => ['driver' => 'mysql', 'url' => '']],
        );

        $this->assertSame(['mysql' => self::URL], $result);
    }

    public function test_backfilled_url_is_parseable_by_laravel_into_discrete_parts(): void
    {
        $overrides = BaseServiceProvider::resolveDatabaseUrlOverrides(
            self::URL,
            ['mysql' => ['driver' => 'mysql']],
        );

        // Prove the whole point: a connection config carrying this `url`
        // resolves into the tenant host/database via Laravel's own parser
        // (the exact mechanism the framework uses at connection time).
        $parsed = (new ConfigurationUrlParser)->parseConfiguration([
            'driver' => 'mysql',
            'url' => $overrides['mysql'],
        ]);

        $this->assertSame('db-host', $parsed['host']);
        $this->assertSame(25060, $parsed['port']);
        $this->assertSame('tenant_db', $parsed['database']);
        $this->assertSame('user', $parsed['username']);
        $this->assertSame('pass', $parsed['password']);
    }
}
