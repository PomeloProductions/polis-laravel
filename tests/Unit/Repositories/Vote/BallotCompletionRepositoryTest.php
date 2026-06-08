<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Vote;

use App\Models\Vote\BallotCompletion;
use Mockery;
use Polis\Contracts\Repositories\Vote\VoteRepositoryContract;
use Polis\Exceptions\NotImplementedException;
use Polis\Repositories\Vote\BallotCompletionRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for BallotCompletionRepository — the create override that syncs
 * votes via VoteRepository, plus the NotImplemented update trait.
 */
final class BallotCompletionRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(BallotCompletion::class, false)) {
            eval('namespace App\\Models\\Vote; class BallotCompletion extends \\Polis\\Models\\BaseModelAbstract {}');
        }
    }

    public function test_create_syncs_votes_via_vote_repository(): void
    {
        $modelMock = Mockery::mock(BallotCompletion::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;

        $voteRepo = Mockery::mock(VoteRepositoryContract::class);
        $voteRepo->shouldReceive('create')
            ->once()
            ->withArgs(function ($data, $parent) use ($modelMock) {
                return $data === ['choice' => 'yes'] && $parent === $modelMock;
            });

        $repo = new BallotCompletionRepository($modelMock, $this->getGenericLogMock(), $voteRepo);
        $repo->create(['votes' => [['choice' => 'yes']]]);
    }

    public function test_create_with_no_votes_does_not_call_vote_repo(): void
    {
        $modelMock = Mockery::mock(BallotCompletion::class);
        $modelMock->shouldReceive('newInstance')->once()->andReturn($modelMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;

        $voteRepo = Mockery::mock(VoteRepositoryContract::class);
        $voteRepo->shouldNotReceive('create');

        $repo = new BallotCompletionRepository($modelMock, $this->getGenericLogMock(), $voteRepo);
        $repo->create();
    }

    public function test_update_throws_not_implemented(): void
    {
        $modelMock = Mockery::mock(BallotCompletion::class);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);

        $voteRepo = Mockery::mock(VoteRepositoryContract::class);

        $repo = new BallotCompletionRepository($modelMock, $this->getGenericLogMock(), $voteRepo);
        $this->expectException(NotImplementedException::class);
        $repo->update(Mockery::mock(\Polis\Models\BaseModelAbstract::class), []);
    }
}
