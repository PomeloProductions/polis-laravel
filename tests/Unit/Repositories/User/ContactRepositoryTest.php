<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Repositories\User;

use App\Models\User\Contact;
use App\Models\User\User;
use Mockery;
use Polis\Repositories\User\ContactRepository;
use Polis\Tests\TestCase;

/**
 * Coverage for ContactRepository::findAll — the custom override that adds
 * an OR-pair filter (initiated_by_id OR requested_id) for each user in
 * $belongsToArray.
 */
final class ContactRepositoryTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (! class_exists(Contact::class, false)) {
            class_alias(
                \Polis\Models\BaseModelAbstract::class,
                Contact::class,
            );
        }
    }

    public function test_find_all_paginates_with_no_belongs_to_filter(): void
    {
        $expected = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $builderMock = Mockery::mock(\AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder::class);
        $builderMock->shouldReceive('paginate')->once()->with(5, ['*'], 'page', 1)->andReturn($expected);

        $modelMock = Mockery::mock(Contact::class);
        $modelMock->shouldReceive('with')->once()->with([])->andReturn($builderMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new ContactRepository($modelMock, $this->getGenericLogMock());
        $result = $repo->findAll([], [], [], [], 5);
        $this->assertSame($expected, $result);
    }

    public function test_find_all_applies_or_pair_filter_per_user(): void
    {
        $user = new User;
        $user->id = 17;

        $expected = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $builderMock = Mockery::mock(\AdminUI\Laravel\EloquentJoin\EloquentJoinBuilder::class);
        $builderMock->shouldReceive('where')->once()->with('initiated_by_id', 17)->andReturnSelf();
        $builderMock->shouldReceive('orWhere')->once()->with('requested_id', 17)->andReturnSelf();
        $builderMock->shouldReceive('paginate')->once()->andReturn($expected);

        $modelMock = Mockery::mock(Contact::class);
        $modelMock->shouldReceive('with')->once()->andReturn($builderMock);
        $modelMock->shouldReceive('setAttribute');
        $modelMock->shouldReceive('getAttribute')->andReturn(0);

        $repo = new ContactRepository($modelMock, $this->getGenericLogMock());
        $result = $repo->findAll([], [], [], [], 10, [$user]);
        $this->assertSame($expected, $result);
    }
}
