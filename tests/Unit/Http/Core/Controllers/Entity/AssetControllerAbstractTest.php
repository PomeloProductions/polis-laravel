<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mimey\MimeTypes;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\AssetRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Entity\AssetController;
use Polis\Tests\Fixtures\Models\Asset as AssetFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Entity\AssetControllerAbstract.
 *
 * Entity-scoped CRUD. index() injects owner_id/owner_type filters keyed
 * on the entity. store() decodes the file contents, resolves the MIME
 * extension, and stamps the entity as owner before creating.
 */
final class AssetControllerAbstractTest extends ControllerTestCase
{
    public function test_index_appends_owner_filters_for_the_bound_entity(): void
    {
        $repo = Mockery::mock(AssetRepositoryContract::class);
        $mimey = Mockery::mock(MimeTypes::class);

        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 7;
        $entity->shouldReceive('morphRelationName')->andReturn('users');

        $request = $this->makeIndexRequest(
            'Polis\\Http\\Core\\Requests\\Entity\\Asset\\IndexRequest',
        );

        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                Mockery::on(fn (array $filter) => in_array(['owner_id', '=', 7], $filter, true)
                    && in_array(['owner_type', '=', 'users'], $filter, true)),
                [], [], [], 10, [], 1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new AssetController($repo, $mimey))->index($request, $entity));
    }

    public function test_store_stamps_owner_and_decoded_file_payload_into_create(): void
    {
        $repo = Mockery::mock(AssetRepositoryContract::class);
        $mimey = Mockery::mock(MimeTypes::class);
        $mimey->shouldReceive('getExtension')->once()->with('image/png')->andReturn('png');

        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 11;
        $entity->shouldReceive('morphRelationName')->andReturn('organizations');

        // Real instance of the AssetUploadRequest stub so getDecodedContents +
        // getFileMimeType behave consistently.
        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\Entity\\Asset\\StoreRequest',
            ['caption' => 'Logo'],
        );
        $request = Mockery::mock($request)->makePartial();
        $request->shouldReceive('getDecodedContents')->once()->andReturn('rawbytes');
        $request->shouldReceive('getFileMimeType')->once()->andReturn('image/png');
        // Re-stub json() since partial mock loses upstream behavior.
        $request->shouldReceive('json->all')->andReturn(['caption' => 'Logo']);

        $created = Mockery::mock(AssetFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with([
                'caption' => 'Logo',
                'file_contents' => 'rawbytes',
                'file_extension' => 'png',
                'owner_id' => 11,
                'owner_type' => 'organizations',
            ])
            ->andReturn($created);

        $response = (new AssetController($repo, $mimey))->store($request, $entity);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(AssetRepositoryContract::class);
        $mimey = Mockery::mock(MimeTypes::class);
        $entity = Mockery::mock(IsAnEntityContract::class);

        $payload = ['caption' => 'Updated'];
        $request = $this->makeRequest(
            'Polis\\Http\\Core\\Requests\\Entity\\Asset\\UpdateRequest',
            $payload,
        );
        $asset = Mockery::mock(AssetFixture::class);
        $updated = Mockery::mock(AssetFixture::class);
        $repo->shouldReceive('update')->once()->with($asset, $payload)->andReturn($updated);

        $this->assertSame($updated, (new AssetController($repo, $mimey))->update($request, $entity, $asset));
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(AssetRepositoryContract::class);
        $mimey = Mockery::mock(MimeTypes::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Entity\\Asset\\DeleteRequest');

        $asset = Mockery::mock(AssetFixture::class);
        $repo->shouldReceive('delete')->once()->with($asset);

        $response = (new AssetController($repo, $mimey))->destroy($request, $entity, $asset);
        $this->assertSame(204, $response->getStatusCode());
    }
}
