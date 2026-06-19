<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Polis\Models\Source;
use Polis\Tests\Fixtures\Repository\RepositoryTestCase;
use Polis\Tests\Fixtures\Traits\ExternalSourcesOwnerModel;

/**
 * Exercises the HasExternalSources trait against a real (sqlite in-memory)
 * Eloquent database.
 *
 * The trait is the public contract for any model that wants to expose a
 * `sources()` morphMany plus the upsert / forget helpers — every public
 * method is hit here so regressions surface as test failures rather than
 * runtime breakage in consumer apps.
 *
 * Test setup:
 *   - RepositoryTestCase boots Testbench with the fixture-migration
 *     directory, which creates the `external_sources_owners` owner
 *     table used by ExternalSourcesOwnerModel.
 *   - We additionally require + run the production sources migration so
 *     the trait's morphMany has its real target table.
 *   - We register a morph alias for ExternalSourcesOwnerModel so the
 *     polymorphic `item_type` column gets a deterministic value
 *     (`external_sources_owner`) that's stable across PHP versions.
 */
final class HasExternalSourcesTest extends RepositoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load and run the production `sources` migration in-process so the
        // trait's morphMany has its table. We deliberately do NOT add this
        // file to tests/Fixtures/database/migrations/ — that directory is
        // for test-only schemas, and the production migration is the
        // source-of-truth we want to exercise here.
        $migration = require __DIR__.'/../../../database/migrations/2026_06_19_000001_create_sources_table.php';
        $migration->up();

        Relation::morphMap([
            'external_sources_owner' => ExternalSourcesOwnerModel::class,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sources');
        parent::tearDown();
    }

    private function makeOwner(string $name = 'owner'): ExternalSourcesOwnerModel
    {
        return ExternalSourcesOwnerModel::query()->create(['name' => $name]);
    }

    public function test_sources_relationship_returns_morph_many(): void
    {
        $owner = $this->makeOwner();

        $relation = $owner->sources();

        $this->assertInstanceOf(
            MorphMany::class,
            $relation,
        );
        $this->assertSame('item_type', $relation->getMorphType());
    }

    public function test_set_external_id_creates_a_new_source_row(): void
    {
        $owner = $this->makeOwner();

        $row = $owner->setExternalId('price_charting', '3457650');

        $this->assertInstanceOf(Source::class, $row);
        $this->assertSame('price_charting', $row->source);
        $this->assertSame('3457650', $row->foreign_id);
        $this->assertNull($row->url);
        $this->assertSame($owner->id, $row->item_id);
        $this->assertSame('external_sources_owner', $row->item_type);

        $this->assertSame(1, Source::query()->count());
    }

    public function test_set_external_id_with_url_persists_the_url(): void
    {
        $owner = $this->makeOwner();

        $row = $owner->setExternalId(
            'igdb',
            '12345',
            'https://www.igdb.com/games/12345',
        );

        $this->assertSame('https://www.igdb.com/games/12345', $row->url);
    }

    public function test_set_external_id_is_idempotent_on_same_source_and_foreign_id(): void
    {
        $owner = $this->makeOwner();

        $first = $owner->setExternalId('price_charting', '3457650');
        $second = $owner->setExternalId('price_charting', '3457650', 'https://example.com');

        $this->assertSame($first->id, $second->id);
        // The url update flowed through.
        $this->assertSame('https://example.com', $second->fresh()->url);
        $this->assertSame(1, Source::query()->count());
    }

    public function test_set_external_id_supports_multiple_foreign_ids_per_source(): void
    {
        $owner = $this->makeOwner();

        $owner->setExternalId('price_charting', '3457650');
        $owner->setExternalId('price_charting', '9999999');

        $this->assertSame(2, Source::query()->count());
        $this->assertSame(
            ['3457650', '9999999'],
            $owner->getExternalIds('price_charting'),
        );
    }

    public function test_get_external_id_returns_first_match(): void
    {
        $owner = $this->makeOwner();
        $owner->setExternalId('price_charting', '3457650');
        $owner->setExternalId('price_charting', '9999999');

        $this->assertSame('3457650', $owner->getExternalId('price_charting'));
    }

    public function test_get_external_id_returns_null_when_no_match(): void
    {
        $owner = $this->makeOwner();

        $this->assertNull($owner->getExternalId('price_charting'));
    }

    public function test_get_external_ids_returns_empty_array_when_no_match(): void
    {
        $owner = $this->makeOwner();

        $this->assertSame([], $owner->getExternalIds('price_charting'));
    }

    public function test_get_external_ids_only_returns_ids_for_requested_source(): void
    {
        $owner = $this->makeOwner();
        $owner->setExternalId('price_charting', '3457650');
        $owner->setExternalId('igdb', '99999');

        $this->assertSame(['3457650'], $owner->getExternalIds('price_charting'));
        $this->assertSame(['99999'], $owner->getExternalIds('igdb'));
    }

    public function test_forget_external_id_removes_only_the_requested_tuple(): void
    {
        $owner = $this->makeOwner();
        $owner->setExternalId('price_charting', '3457650');
        $owner->setExternalId('price_charting', '9999999');
        $owner->setExternalId('igdb', '111');

        $owner->forgetExternalId('price_charting', '3457650');

        $this->assertSame(['9999999'], $owner->getExternalIds('price_charting'));
        $this->assertSame(['111'], $owner->getExternalIds('igdb'));
    }

    public function test_forget_external_id_force_deletes_so_the_unique_slot_is_freed(): void
    {
        // Critical regression guard for the rationale in the trait's
        // docblock: a soft-deleted row leaves the unique slot occupied,
        // which would cause the next setExternalId for the same tuple
        // to throw a duplicate-key error. forceDelete() is what frees it.
        $owner = $this->makeOwner();
        $owner->setExternalId('price_charting', '3457650');

        $owner->forgetExternalId('price_charting', '3457650');

        // Row is gone from the table (including trashed rows).
        $this->assertSame(
            0,
            Source::query()->withTrashed()->count(),
        );

        // Re-adding the same tuple now succeeds.
        $recreated = $owner->setExternalId('price_charting', '3457650', 'https://x');
        $this->assertSame('https://x', $recreated->url);
    }

    public function test_forget_all_external_ids_removes_every_row_for_that_source(): void
    {
        $owner = $this->makeOwner();
        $owner->setExternalId('price_charting', '3457650');
        $owner->setExternalId('price_charting', '9999999');
        $owner->setExternalId('igdb', '111');

        $owner->forgetAllExternalIds('price_charting');

        $this->assertSame([], $owner->getExternalIds('price_charting'));
        $this->assertSame(['111'], $owner->getExternalIds('igdb'));
        $this->assertSame(
            0,
            Source::query()->withTrashed()->where('source', 'price_charting')->count(),
        );
    }

    public function test_forget_all_external_ids_does_not_touch_other_owners(): void
    {
        $alice = $this->makeOwner('alice');
        $bob = $this->makeOwner('bob');

        $alice->setExternalId('price_charting', '111');
        $bob->setExternalId('price_charting', '222');

        $alice->forgetAllExternalIds('price_charting');

        $this->assertSame([], $alice->getExternalIds('price_charting'));
        $this->assertSame(['222'], $bob->getExternalIds('price_charting'));
    }

    public function test_reverse_lookup_via_source_morph_to_returns_owner(): void
    {
        $owner = $this->makeOwner('the-owner');
        $owner->setExternalId('price_charting', '3457650');

        $row = Source::query()
            ->where('source', 'price_charting')
            ->where('foreign_id', '3457650')
            ->first();

        $this->assertNotNull($row);
        $resolved = $row->item;
        $this->assertInstanceOf(ExternalSourcesOwnerModel::class, $resolved);
        $this->assertSame($owner->id, $resolved->id);
        $this->assertSame('the-owner', $resolved->name);
    }
}
