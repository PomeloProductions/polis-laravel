<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\User\TodoSettingRepositoryContract;
use App\Contracts\Repositories\User\TodoTemplateRepositoryContract;
use App\Services\Todo\TodoGenerationService;
use App\Services\Todo\TodoTaskTreeService;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Providers\BaseServiceProvider;

class AppServiceProvider extends BaseServiceProvider
{
    public function appProviders(): array
    {
        return [
            TodoGenerationService::class,
            TodoTaskTreeService::class,
        ];
    }

    public function registerApp(): void
    {
        $this->app->bind(TodoTaskTreeService::class, fn () => new TodoTaskTreeService());

        $this->app->bind(TodoGenerationService::class, fn () => new TodoGenerationService(
            $this->app->make(UserPageRepositoryContract::class),
            $this->app->make(UserPageComponentRepositoryContract::class),
            $this->app->make(TodoSettingRepositoryContract::class),
            $this->app->make(TodoTemplateRepositoryContract::class),
            $this->app->make(TodoTaskTreeService::class),
        ));
    }
}
