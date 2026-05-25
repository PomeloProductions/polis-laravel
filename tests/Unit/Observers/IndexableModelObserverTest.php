<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Observers;

use App\Models\Resource;
use App\Models\User\User;
use Polis\Contracts\Repositories\ResourceRepositoryContract;
use Polis\Observers\IndexableModelObserver;
use Polis\Tests\CustomMockInterface;
use Polis\Tests\TestCase;

/**
 * Class IndexableModelObserverTest
 */
final class IndexableModelObserverTest extends TestCase
{
    /**
     * @var IndexableModelObserver
     */
    private $observer;

    /**
     * @var ResourceRepositoryContract|CustomMockInterface
     */
    private $resourceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourceRepository = mock(ResourceRepositoryContract::class);
        $this->observer = new IndexableModelObserver($this->resourceRepository);
    }

    public function test_created(): void
    {
        $model = new User([
            'id' => 123,
            'name' => 'Test User',
            'resource' => null,
        ]);

        $this->resourceRepository->shouldReceive('create')
            ->with([
                'content' => $model->getContentString(),
                'resource_id' => 123,
                'resource_type' => 'user',
            ])
            ->once();

        $this->observer->created($model);
    }

    public function test_updated(): void
    {
        $user = new User([
            'resource' => null,
            'name' => 'Someone',
        ]);

        $this->resourceRepository->shouldReceive('create')->once()->with(\Mockery::on(function ($data) {

            $this->assertArrayHasKey('content', $data);
            $this->assertArrayHasKey('resource_id', $data);
            $this->assertArrayHasKey('resource_type', $data);

            $this->assertEquals('user', $data['resource_type']);

            return true;
        }));

        $this->observer->updated($user);

        $resource = new Resource;
        $user = new User([
            'resource' => $resource,
            'name' => 'Someone',
        ]);

        $this->resourceRepository->shouldReceive('update')->once()->with($resource, \Mockery::on(function ($data) {

            $this->assertArrayHasKey('content', $data);
            $this->assertArrayHasKey('resource_id', $data);
            $this->assertArrayHasKey('resource_type', $data);

            $this->assertEquals('user', $data['resource_type']);

            return true;
        }));

        $this->observer->updated($user);
    }

    public function test_deleted(): void
    {
        $resource = new Resource;
        $user = new User([
            'resource' => $resource,
        ]);

        $this->resourceRepository->shouldReceive('delete')->once()->with($resource);

        $this->observer->deleted($user);
    }
}
