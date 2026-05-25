<?php

declare(strict_types=1);

namespace Polis\Services\Indexing;

use Illuminate\Contracts\Foundation\Application;
use Polis\Contracts\Repositories\BaseRepositoryContract;
use Polis\Contracts\Services\Indexing\ResourceRepositoryServiceContract;

abstract class BaseResourceRepositoryService implements ResourceRepositoryServiceContract
{
    public function __construct(private Application $app) {}

    /**
     * All repo interfaces for enabled resources in this app
     *
     * @return array<class-string>
     */
    abstract public function availableResourceRepositories(): array;

    /**
     * Gets all resource repositories used in our app
     *
     * @return array<BaseRepositoryContract>
     */
    public function getResourceRepositories(): array
    {
        return array_map(
            fn (string $interfaceName) => $this->app->make($interfaceName),
            $this->availableResourceRepositories(),
        );
    }
}
