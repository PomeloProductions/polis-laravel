<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\User\InvitationTokenRepositoryContract;
use Polis\Tests\Fixtures\Controllers\InvitationTokenController;
use Polis\Tests\Fixtures\Models\InvitationToken as InvitationTokenFixture;

/**
 * Unit coverage for InvitationTokenControllerAbstract.
 *
 * Adds two specifics on top of the canonical CRUD: store() injects a
 * uniquely-generated token into the payload before create(), and show()
 * passes the model straight back (no expand).
 */
final class InvitationTokenControllerAbstractTest extends ControllerTestCase
{
    public function test_index_forwards_parsed_query_args(): void
    {
        $repo = Mockery::mock(InvitationTokenRepositoryContract::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest(
            'App\\Http\\Core\\Requests\\InvitationToken\\IndexRequest',
            ['limit' => 15, 'page' => 2],
        );

        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 15, [], 2)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new InvitationTokenController($repo))->index($request));
    }

    public function test_store_attaches_freshly_generated_token_to_payload(): void
    {
        $repo = Mockery::mock(InvitationTokenRepositoryContract::class);
        $payload = ['email' => 'invitee@example.test'];

        $repo->shouldReceive('generateUniqueToken')->once()->andReturn('uniq-tok-1');

        $created = Mockery::mock(InvitationTokenFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['email' => 'invitee@example.test', 'token' => 'uniq-tok-1'])
            ->andReturn($created);

        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\InvitationToken\\StoreRequest',
            $payload,
        );

        $response = (new InvitationTokenController($repo))->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function test_show_returns_bound_invitation_token_directly(): void
    {
        $repo = Mockery::mock(InvitationTokenRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\InvitationToken\\ViewRequest');
        $token = Mockery::mock(InvitationTokenFixture::class);

        $this->assertSame($token, (new InvitationTokenController($repo))->show($request, $token));
    }

    public function test_update_delegates_to_repository(): void
    {
        $repo = Mockery::mock(InvitationTokenRepositoryContract::class);
        $payload = ['revoked' => true];

        $request = $this->makeRequest('App\\Http\\Core\\Requests\\InvitationToken\\UpdateRequest', $payload);
        $token = Mockery::mock(InvitationTokenFixture::class);
        $updated = Mockery::mock(InvitationTokenFixture::class);

        $repo->shouldReceive('update')->once()->with($token, $payload)->andReturn($updated);

        $this->assertSame($updated, (new InvitationTokenController($repo))->update($request, $token));
    }

    public function test_destroy_deletes_and_returns_204(): void
    {
        $repo = Mockery::mock(InvitationTokenRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\InvitationToken\\DeleteRequest');

        $token = Mockery::mock(InvitationTokenFixture::class);
        $repo->shouldReceive('delete')->once()->with($token);

        $response = (new InvitationTokenController($repo))->destroy($request, $token);

        $this->assertSame(204, $response->getStatusCode());
    }
}
