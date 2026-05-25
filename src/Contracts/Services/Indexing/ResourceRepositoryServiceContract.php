<?php

declare(strict_types=1);

namespace Polis\Contracts\Services\Indexing;

use Polis\Contracts\Repositories\BaseRepositoryContract;

interface ResourceRepositoryServiceContract
{
    /**
     * Gets all resource repositories used in our app
     *
     * @return array<BaseRepositoryContract>
     */
    public function getResourceRepositories(): array;
}
