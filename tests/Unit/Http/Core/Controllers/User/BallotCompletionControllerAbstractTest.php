<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mockery;
use Polis\Contracts\Repositories\Vote\BallotCompletionRepositoryContract;
use Polis\Tests\Fixtures\Controllers\User\BallotCompletionController;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for User\BallotCompletionControllerAbstract.
 *
 * User-scoped read-only listing.
 */
final class BallotCompletionControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_find_all_to_parent_user(): void
    {
        $repo = Mockery::mock(BallotCompletionRepositoryContract::class);
        $user = Mockery::mock(UserFixture::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\User\\BallotCompletion\\IndexRequest');
        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$user], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new BallotCompletionController($repo))->index($request, $user));
    }
}
