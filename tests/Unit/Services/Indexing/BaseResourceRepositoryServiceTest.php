<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Indexing;

use Illuminate\Contracts\Foundation\Application;
use Polis\Contracts\Repositories\User\UserRepositoryContract;
use Polis\Repositories\User\UserRepository;
use Polis\Services\Indexing\BaseResourceRepositoryService;
use Polis\Tests\TestCase;

class BaseResourceRepositoryServiceTest extends TestCase
{
    public function test_get_resource_repositories()
    {
        $app = mock(Application::class);

        $userRepository = mock(UserRepository::class);

        $app->shouldReceive('make')->with(UserRepositoryContract::class)->andReturn($userRepository);

        $resourceRepositoryService = new class($app) extends BaseResourceRepositoryService
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

        $result = $resourceRepositoryService->getResourceRepositories();

        $this->assertEquals([$userRepository], $result);
    }
}
