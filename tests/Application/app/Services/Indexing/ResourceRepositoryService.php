<?php
declare(strict_types=1);

namespace App\Services\Indexing;

use Polis\Services\Indexing\BaseResourceRepositoryService;

class ResourceRepositoryService extends BaseResourceRepositoryService
{
    /**
     * All repo interfaces for enabled resources in this app
     *
     * @return array<class-string>
     */
    public function availableResourceRepositories(): array
    {
        return [
        ];
    }
}