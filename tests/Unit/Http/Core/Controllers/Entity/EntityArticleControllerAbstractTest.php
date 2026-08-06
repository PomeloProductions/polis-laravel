<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Wiki\ArticleRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Entity\EntityArticleController;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Entity\EntityArticleControllerAbstract — the reusable,
 * entity-generic Article ("contract") listing behind
 * GET /{entity}/{entity_id}/articles.
 *
 * The one behaviour that matters: whatever entity is bound to the route is
 * handed to the repository's `belongsToArray` slot (the 6th findAll() arg), so
 * the repository scopes the listing to THAT entity's articles by resolving the
 * relation off the entity's class. This is what makes the same controller serve
 * an Organization-owned OR a User-owned (or any future entity) article listing
 * without a per-owner subclass.
 */
final class EntityArticleControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_listing_to_the_bound_entity_via_belongs_to_array(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\Organization\\Article\\IndexRequest',
            ['limit' => 7, 'page' => 3],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 7, [$entity], 3)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new EntityArticleController($repo))->index($request, $entity));
    }

    public function test_index_works_for_any_entity_type_not_just_organization(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);

        // A completely different entity instance (stands in for a User-owned
        // article listing) — the controller must forward it unchanged.
        $userEntity = Mockery::mock(IsAnEntityContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\Organization\\Article\\IndexRequest');

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$userEntity], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new EntityArticleController($repo))->index($request, $userEntity));
    }
}
