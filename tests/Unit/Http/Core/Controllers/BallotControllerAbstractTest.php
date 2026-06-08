<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Mockery;
use Polis\Contracts\Repositories\Vote\BallotRepositoryContract;
use Polis\Tests\Fixtures\Controllers\BallotController;
use Polis\Tests\Fixtures\Models\Ballot as BallotFixture;

/**
 * Unit coverage for BallotControllerAbstract.
 *
 * Only exposes show() — confirms expand parsing flows into $model->load().
 */
final class BallotControllerAbstractTest extends ControllerTestCase
{
    public function test_show_loads_specified_relations_on_the_bound_ballot(): void
    {
        $repo = Mockery::mock(BallotRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Ballot\\ViewRequest', [
            'with' => ['items', 'items.options'],
        ]);

        $ballot = Mockery::mock(BallotFixture::class);
        $loaded = Mockery::mock(BallotFixture::class);
        $ballot->shouldReceive('load')->once()->with(['items', 'items.options'])->andReturn($loaded);

        $this->assertSame($loaded, (new BallotController($repo))->show($request, $ballot));
    }

    public function test_show_with_no_expand_passes_empty_array(): void
    {
        $repo = Mockery::mock(BallotRepositoryContract::class);
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Ballot\\ViewRequest');

        $ballot = Mockery::mock(BallotFixture::class);
        $loaded = Mockery::mock(BallotFixture::class);
        $ballot->shouldReceive('load')->once()->with([])->andReturn($loaded);

        $this->assertSame($loaded, (new BallotController($repo))->show($request, $ballot));
    }
}
