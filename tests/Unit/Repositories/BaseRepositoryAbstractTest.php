<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories;

use AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder;
use App\Models\User\User;
use Polis\Repositories\BaseRepositoryAbstract;
use Polis\Tests\TestCase;

/**
 * Class BaseRepositoryAbstractTest
 */
final class BaseRepositoryAbstractTest extends TestCase
{
    public function test_find_or_fail_passes_proper_parameters(): void
    {
        $withArgs = ['with' => 'args'];
        $id = 123;

        $mockModel = mock(User::class)->shouldAllowMockingMethod('findOrFail')->shouldAllowMockingMethod('with');
        $mockModel->shouldReceive('with')->once()->with($withArgs)->andReturn(\Mockery::self());
        $mockModel->shouldReceive('findOrFail')->once()->with($id)->andReturn($mockModel);

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->findOrFail($id, $withArgs);
    }

    public function test_find_or_fail_default_parameters(): void
    {
        $id = 123;

        $mockModel = mock(User::class)
            ->shouldAllowMockingMethod('findOrFail')
            ->shouldAllowMockingMethod('with');
        $mockModel->shouldReceive('with')->once()->with([])->andReturn(\Mockery::self());
        $mockModel->shouldReceive('findOrFail')->once()->with($id)->andReturn($mockModel);

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->findOrFail($id);
    }

    public function test_find_all_passes_proper_parameters(): void
    {
        $whereArgs = ['where' => 'args'];
        $withArgs = ['with' => 'args'];
        $limitArg = 22;

        $paginator = mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $mockModel = mock(EloquentJoinBuilder::class)
            ->shouldAllowMockingMethod('with')
            ->shouldAllowMockingMethod('where')
            ->shouldAllowMockingMethod('whereJoin')
            ->shouldAllowMockingMethod('appends')
            ->shouldAllowMockingMethod('paginate');
        $mockModel->shouldReceive('with')->once()->with($withArgs)->andReturn(\Mockery::self());
        $mockModel->shouldReceive('whereJoin')->once()->with('where', '=', 'args')->andReturn(\Mockery::self());
        $mockModel->shouldReceive('paginate')->once()->with($limitArg, ['*'], 'page', 1)->andReturn($paginator);

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->findAll($whereArgs, [], [], $withArgs, $limitArg);
    }

    public function test_find_all_default_parameters(): void
    {
        $paginator = mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $mockModel = mock(EloquentJoinBuilder::class)
            ->shouldAllowMockingMethod('with')
            ->shouldAllowMockingMethod('appends')
            ->shouldAllowMockingMethod('paginate');
        $mockModel->shouldReceive('with')->once()->with([])->andReturn(\Mockery::self());
        $mockModel->shouldReceive('paginate')->once()->with(10, ['*'], 'page', 1)->andReturn($paginator);

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->findAll();
    }

    public function test_create_passes_proper_parameters(): void
    {
        $args = ['some' => 'args'];

        $mockModel = mock(User::class)->shouldAllowMockingMethod('create');
        $mockModel->shouldReceive('newInstance')->once()->with($args)->andReturn(\Mockery::self());
        $mockModel->shouldReceive('save')->once();
        $mockModel->shouldReceive('getAttribute')->once();

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->create($args);
    }

    public function test_create_default_parameters(): void
    {
        $mockModel = mock(User::class)->shouldAllowMockingMethod('create');
        $mockModel->shouldReceive('newInstance')->once()->with([])->andReturn(\Mockery::self());
        $mockModel->shouldReceive('getAttribute')->once();
        $mockModel->shouldReceive('save')->once();

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->create();
    }

    public function test_create_passes_forced_values(): void
    {
        $mockModel = mock(User::class)->shouldAllowMockingMethod('create');
        $mockModel->shouldReceive('newInstance')->once()->with([])->andReturn(\Mockery::self());
        $mockModel->shouldReceive('setAttribute')->once()->with('test', 'chicken');
        $mockModel->shouldReceive('getAttribute')->once();
        $mockModel->shouldReceive('save')->once();

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->create([], null, ['test' => 'chicken']);
    }

    public function test_update_passes_proper_parameters(): void
    {
        $args = ['some' => 'args'];

        $mockModel = mock(User::class);
        $mockModel->shouldReceive('update')->once()->with($args)->andReturn(true);
        $mockModel->shouldReceive('getAttribute')->once();

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->update($mockModel, $args);
    }

    public function test_update_passes_forced_values(): void
    {
        $args = ['some' => 'args'];
        $forcedArgs = ['other' => 'forced'];

        $mockModel = mock(User::class);
        $mockModel->shouldReceive('forceFill')->once()->with(['other' => 'forced']);
        $mockModel->shouldReceive('update')->once()->with($args)->andReturn(true);
        $mockModel->shouldReceive('getAttribute')->once();

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->update($mockModel, $args, $forcedArgs);
    }

    public function test_update_throws_exception_when_fails(): void
    {
        $this->expectException(\DomainException::class);
        $args = ['some' => 'args'];

        $mockModel = mock(User::class);
        $mockModel->shouldReceive('update')->once()->with($args)->andReturn(false);
        $mockModel->shouldReceive('getAttribute')->andReturn('something');

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->update($mockModel, $args);
    }

    public function test_delete_is_called(): void
    {
        $mockModel = mock(User::class);
        $mockModel->shouldReceive('delete')->once()->andReturn(true);
        $mockModel->shouldReceive('getAttribute')->once();

        $repository = new class($mockModel, $this->getGenericLogMock()) extends BaseRepositoryAbstract {};
        $repository->delete($mockModel);
    }
}
