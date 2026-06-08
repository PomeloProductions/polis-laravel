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
 * via Testbench's loadMigrationsFrom(). The tables created are scoped to
 * the fixture models in this directory (RepoParentModel, RepoChildModel,
 * etc.) so tests don't depend on consumer-app schema.
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
