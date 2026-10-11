<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models\Statistic;

use App\Models\Statistic\Statistic;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Integration coverage for the polymorphic owner on Statistic.
 *
 * A NULL owner means a GLOBAL / public statistic (the pre-owner behaviour);
 * a resolved owner means a per-user (or, later, per-org) statistic. These
 * tests exercise the real App\Models\Statistic\Statistic against the booted
 * schema, proving the columns persist, owner() resolves the morph, and the
 * owner scopes filter correctly while staying backward-compatible for the
 * existing global statistics.
 */
final class StatisticTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        User::unsetEventDispatcher();

        foreach (Statistic::withTrashed()->get() as $model) {
            $model->forceDelete();
        }
    }

    public function test_owner_relation_is_a_morph_to(): void
    {
        $relation = (new Statistic)->owner();

        $this->assertInstanceOf(MorphTo::class, $relation);
        $this->assertSame('owner_type', $relation->getMorphType());
        $this->assertSame('owner_id', $relation->getForeignKeyName());
    }

    public function test_owner_columns_persist(): void
    {
        $user = User::factory()->create();

        $statistic = Statistic::factory()->create([
            'owner_type' => 'user',
            'owner_id' => $user->id,
        ]);

        $fresh = Statistic::findOrFail($statistic->id);
        $this->assertSame('user', $fresh->owner_type);
        $this->assertSame($user->id, (int) $fresh->owner_id);
    }

    public function test_owner_resolves_the_morph_to_a_user(): void
    {
        $user = User::factory()->create();

        $statistic = Statistic::factory()->ownedByUser($user)->create();

        $this->assertInstanceOf(User::class, $statistic->owner);
        $this->assertSame($user->id, $statistic->owner->id);
    }

    public function test_global_statistic_has_null_owner_and_backward_compatible(): void
    {
        // The default factory state is a global statistic (NULL owner) — the
        // behaviour that existed before the owner column was added.
        $statistic = Statistic::factory()->create();

        $this->assertNull($statistic->owner_type);
        $this->assertNull($statistic->owner_id);
        $this->assertNull($statistic->owner);
    }

    public function test_scope_owned_by_returns_only_that_owners_statistics(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $mine = Statistic::factory()->ownedByUser($user)->create();
        Statistic::factory()->ownedByUser($otherUser)->create();
        Statistic::factory()->create(); // global

        $results = Statistic::ownedBy($user)->get();

        $this->assertCount(1, $results);
        $this->assertSame($mine->id, $results->first()->id);
    }

    public function test_scope_global_returns_only_null_owner_statistics(): void
    {
        $user = User::factory()->create();

        $global = Statistic::factory()->create();
        Statistic::factory()->ownedByUser($user)->create();

        $results = Statistic::global()->get();

        $this->assertCount(1, $results);
        $this->assertSame($global->id, $results->first()->id);
    }

    public function test_scope_global_or_owned_by_returns_global_plus_mine(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $global = Statistic::factory()->create();
        $mine = Statistic::factory()->ownedByUser($user)->create();
        Statistic::factory()->ownedByUser($otherUser)->create(); // excluded

        $ids = Statistic::globalOrOwnedBy($user)->pluck('id')->sort()->values()->all();

        $this->assertSame(
            collect([$global->id, $mine->id])->sort()->values()->all(),
            $ids,
        );
    }

    public function test_scope_global_or_owned_by_without_owner_returns_only_global(): void
    {
        $user = User::factory()->create();

        $global = Statistic::factory()->create();
        Statistic::factory()->ownedByUser($user)->create();

        $results = Statistic::globalOrOwnedBy(null)->get();

        $this->assertCount(1, $results);
        $this->assertSame($global->id, $results->first()->id);
    }
}
