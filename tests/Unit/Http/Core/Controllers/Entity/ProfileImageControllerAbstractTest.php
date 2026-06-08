<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Entity;

use Illuminate\Http\JsonResponse;
use Mimey\MimeTypes;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\User\ProfileImageRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Entity\ProfileImageController;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Entity\ProfileImageControllerAbstract.
 *
 * store() decodes the binary payload, resolves the extension, and stamps
 * the entity as owner before delegating to the repository. The repo's
 * create() takes the entity as its $relatedModel arg (rather than via
 * a $belongsToArray).
 */
final class ProfileImageControllerAbstractTest extends ControllerTestCase
{
    public function test_store_decodes_file_and_stamps_owner_then_creates(): void
    {
        $repo = Mockery::mock(ProfileImageRepositoryContract::class);
        $mimey = Mockery::mock(MimeTypes::class);
        $mimey->shouldReceive('getExtension')->once()->with('image/jpeg')->andReturn('jpg');

        // Mock User + IsAnEntityContract together so the entity is also a
        // BaseModelAbstract (UserFixture extends BaseModelAbstract). The
        // ProfileImageRepository::create signature requires that downstream.
        $entity = Mockery::mock(UserFixture::class, IsAnEntityContract::class);
        $entity->id = 4;
        $entity->shouldReceive('morphRelationName')->andReturn('users');

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Entity\\ProfileImage\\StoreRequest');
        $request = Mockery::mock($request)->makePartial();
        $request->shouldReceive('getDecodedContents')->once()->andReturn('binary');
        $request->shouldReceive('getFileMimeType')->once()->andReturn('image/jpeg');

        $created = Mockery::mock();
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(
                [
                    'file_contents' => 'binary',
                    'file_extension' => 'jpg',
                    'owner_id' => 4,
                    'owner_type' => 'users',
                ],
                $entity,
            )
            ->andReturn($created);

        $response = (new ProfileImageController($repo, $mimey))->store($request, $entity);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }
}
