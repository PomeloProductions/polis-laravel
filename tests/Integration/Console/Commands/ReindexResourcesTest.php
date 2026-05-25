<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Console\Commands;

use App\Models\Resource;
use App\Models\User\User;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Polis\Console\Commands\ReindexResources;
use Polis\Contracts\Repositories\ResourceRepositoryContract;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Repositories\ResourceRepository;
use Polis\Repositories\User\UserRepository;
use Polis\Services\Indexing\BaseResourceRepositoryService;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\MocksConsoleOutput;

/**
 * Class ReindexResourcesTest
 */
final class ReindexResourcesTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, MocksConsoleOutput;

    /**
     * @var ReindexResources
     */
    private $command;

    private ResourceRepositoryContract $resourceRepository;

    private BaseResourceRepositoryService $resourceRepositoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();

        $this->resourceRepository = new ResourceRepository(new Resource, $this->getGenericLogMock());

        $app = mock(Application::class);

        $app->shouldReceive('make')->with(UserRepositoryContract::class)->andReturn(
            new UserRepository(
                new User,
                $this->getGenericLogMock(),
                mock(Hasher::class),
                mock(Repository::class),
            )
        );

        $this->resourceRepositoryService = new class($app) extends BaseResourceRepositoryService
        {
            /**
             * All repo interfaces for enabled resources in this app
             *
             * @return array<class-string>
             */
            public function availableResourceRepositories(): array
            {
                return [
                    UserRepositoryContract::class,
                ];
            }
        };

        $this->command = new ReindexResources(
            $this->resourceRepository,
            $this->resourceRepositoryService,
        );
        $this->mockConsoleOutput($this->command);
    }

    public function test_handle(): void
    {
        User::unsetEventDispatcher();

        User::factory()->create();

        Resource::factory()->count(3)->create();

        $this->assertCount(3, Resource::all());
        $this->assertCount(4, User::all());

        $this->command->handle();

        $this->assertCount(4, Resource::all());
    }
}
