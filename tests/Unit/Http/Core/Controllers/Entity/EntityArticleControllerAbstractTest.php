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
 * The one behaviour that matters: whatever entity is bound to the route scopes
 * the listing to THAT entity's articles via Article's polymorphic
 * owner_id/owner_type columns. Because the scoping is column-based (not a
 * per-owner belongsTo relation), the SAME controller serves an
 * Organization-owned OR a User-owned (or any future entity) article listing
 * without a per-owner subclass.
 */
final class EntityArticleControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_listing_to_the_bound_entity_owner_columns(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('morphRelationName')->andReturn('organization');
        $entity->id = 42;
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'Polis\\Http\\Core\\Requests\\Organization\\Article\\IndexRequest',
            ['limit' => 7, 'page' => 3],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                [['owner_id', '=', 42], ['owner_type', '=', 'organization']],
                [],
                [],
                [],
                7,
                [],
                3,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new EntityArticleController($repo))->index($request, $entity));
    }

    public function test_index_works_for_any_entity_type_not_just_organization(): void
    {
        $repo = Mockery::mock(ArticleRepositoryContract::class);

        // A User entity (stands in for a User-owned article listing) — the
        // controller must scope by that entity's own owner columns.
        $userEntity = Mockery::mock(IsAnEntityContract::class);
        $userEntity->shouldReceive('morphRelationName')->andReturn('user');
        $userEntity->id = 9;
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\Organization\\Article\\IndexRequest');

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                [['owner_id', '=', 9], ['owner_type', '=', 'user']],
                [],
                [],
                [],
                10,
                [],
                1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new EntityArticleController($repo))->index($request, $userEntity));
    }
}
