<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\Statistic;

use App\Models\Statistic\TargetStatistic;
use Mockery;
use Polis\Contracts\Models\CanBeStatisticTargetContract;
use Polis\Repositories\Statistic\TargetStatisticRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for TargetStatisticRepository — createForTarget (sets the
 * morph id+type before delegating to create), findAllForTarget and
 * findForTarget.
 */
final class TargetStatisticRepositoryTest extends TestCase
{
    private function buildTarget(int $id = 5, string $morph = 'article'): CanBeStatisticTargetContract
    {
        $target = Mockery::mock(CanBeStatisticTargetContract::class);
        $target->id = $id;
        $target->shouldReceive('morphRelationName')->andReturn($morph);

        return $target;
    }

    public function test_create_for_target_writes_target_id_and_target_type_then_creates(): void
    {
        $target = $this->buildTarget(5, 'article');

        $modelMock = Mockery::mock(TargetStatistic::class);
        $modelMock->shouldReceive('newInstance')
            ->once()
            ->andReturnUsing(function ($data) use ($modelMock) {
                $this->assertSame(5, $data['target_id']);
                $this->assertSame('article', $data['target_type']);

                return $modelMock;
            });
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(1);
        $modelMock->wasRecentlyCreated = true;

        $repo = new TargetStatisticRepository($modelMock, $this->getGenericLogMock());
        $repo->createForTarget($target, []);
    }

    public function test_find_all_for_target_filters_by_morph_id_and_type(): void
    {
        $target = $this->buildTarget(7, 'organization');

        $modelMock = Mockery::mock(TargetStatistic::class);
        $modelMock->shouldReceive('where')->ordered()->once()->with('target_type', 'organization')->andReturnSelf();
        $modelMock->shouldReceive('where')->ordered()->once()->with('target_id', 7)->andReturnSelf();
        $expected = new \Illuminate\Database\Eloquent\Collection;
        $modelMock->shouldReceive('get')->once()->andReturn($expected);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new TargetStatisticRepository($modelMock, $this->getGenericLogMock());
        $this->assertSame($expected, $repo->findAllForTarget($target));
    }

    public function test_find_for_target_filters_by_morph_and_statistic_id(): void
    {
        $target = $this->buildTarget(1, 'article');

        $modelMock = Mockery::mock(TargetStatistic::class);
        $modelMock->shouldReceive('where')->ordered()->once()->with('target_type', 'article')->andReturnSelf();
        $modelMock->shouldReceive('where')->ordered()->once()->with('target_id', 1)->andReturnSelf();
        $modelMock->shouldReceive('where')->ordered()->once()->with('statistic_id', 42)->andReturnSelf();
        $expected = new TargetStatistic;
        $modelMock->shouldReceive('first')->once()->andReturn($expected);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new TargetStatisticRepository($modelMock, $this->getGenericLogMock());
        $this->assertSame($expected, $repo->findForTarget($target, 42));
    }
}
