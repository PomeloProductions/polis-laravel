<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Ballot;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Tests\Fixtures\Controllers\Ballot\BallotCompletionController;
use Polis\Tests\Fixtures\Models\Ballot as BallotFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Ballot\BallotCompletionControllerAbstract.
 *
 * store() builds a payload containing user_id (from auth) + the request
 * body, hands to repo->create with the parent Ballot, then eagerly loads
 * the votes relation before responding.
 */
final class BallotCompletionControllerAbstractTest extends ControllerTestCase
{
    public function test_store_injects_auth_user_id_and_returns_201_with_loaded_votes(): void
    {
        $repo = Mockery::mock(BallotCompletionRepositoryContract::class);
        $ballot = Mockery::mock(BallotFixture::class);

        $user = Mockery::mock(Authenticatable::class);
        $user->id = 5;
        Auth::shouldReceive('user')->once()->andReturn($user);

        $payload = ['votes' => [['ballot_item_id' => 1, 'value' => 'yes']]];

        $created = Mockery::mock(\Polis\Models\BaseModelAbstract::class);
        $created->shouldReceive('load')->once()->with('votes');
        $created->shouldReceive('toJson')->andReturn('{"id":1}');

        $repo->shouldReceive('create')
            ->once()
            ->with(['votes' => $payload['votes'], 'user_id' => 5], $ballot)
            ->andReturn($created);

        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Ballot\\BallotCompletion\\StoreRequest',
            $payload,
        );

        $response = (new BallotCompletionController($repo))->store($request, $ballot);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }
}
