<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Repository;

use Polis\Tests\TestCase;

/**
 * Base test case for repository tests that need a real (in-memory sqlite)
 * Eloquent database — i.e. tests that exercise SQL behaviour rather than
 * just method dispatch.
 *
 * Loads the test-only migrations from tests/Fixtures/database/migrations
 * via Testbench's loadMigrationsFrom() (non-recursive, so the nested
 * repo-test-tables/ subdirectory is NOT loaded here — see below). The
 * tables created are scoped to fixture models so tests don't depend on
 * consumer-app schema.
 *
 * Note: the generic BaseRepositoryAbstract SQL-behaviour tests (which need
 * the RepoParentModel/RepoChildModel/etc. tables in
 * database/migrations/repo-test-tables/) are DB-backed and therefore live in
 * the Integration suite (ApplicationTestCase), not here — the Unit suite is
 * pure/no-DB. This base remains for the handful of standalone trait tests
 * (e.g. HasExternalSourcesTest) that need the other fixture tables.
 *
 * For mock-only behavioural tests, prefer extending TestCase directly and
 * passing a Mockery double — there's no DB overhead and no schema to
 * maintain.
 */
abstract class RepositoryTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
