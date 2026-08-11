<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\User\ActiveTimerRepositoryContract;
use App\Contracts\Repositories\User\TimeEntryRepositoryContract;
use App\Contracts\Repositories\User\TodoBalanceLogRepositoryContract;
use App\Contracts\Repositories\User\TodoBalanceRepositoryContract;
use App\Contracts\Repositories\User\TodoRotatingGroupRepositoryContract;
use App\Contracts\Repositories\User\TodoRotatingItemRepositoryContract;
use App\Contracts\Repositories\User\TodoSettingRepositoryContract;
use App\Contracts\Repositories\User\TodoSubItemRepositoryContract;
use App\Contracts\Repositories\User\TodoTaskNodeRepositoryContract;
use App\Contracts\Repositories\User\TodoTemplateRepositoryContract;
use App\Models\User\ActiveTimer;
use App\Models\User\TimeEntry;
use App\Models\User\TodoBalance;
use App\Models\User\TodoBalanceLog;
use App\Models\User\TodoRotatingGroup;
use App\Models\User\TodoRotatingItem;
use App\Models\User\TodoSetting;
use App\Models\User\TodoSubItem;
use App\Models\User\TodoTaskNode;
use App\Models\User\TodoTemplate;
use App\Repositories\User\ActiveTimerRepository;
use App\Repositories\User\TimeEntryRepository;
use App\Repositories\User\TodoBalanceLogRepository;
use App\Repositories\User\TodoBalanceRepository;
use App\Repositories\User\TodoRotatingGroupRepository;
use App\Repositories\User\TodoRotatingItemRepository;
use App\Repositories\User\TodoSettingRepository;
use App\Repositories\User\TodoSubItemRepository;
use App\Repositories\User\TodoTaskNodeRepository;
use App\Repositories\User\TodoTemplateRepository;
use Polis\Providers\BaseRepositoryProvider;

class AppRepositoryProvider extends BaseRepositoryProvider
{
    public function appProviders(): array
    {
        return [
            ActiveTimerRepositoryContract::class,
            TimeEntryRepositoryContract::class,
            TodoBalanceRepositoryContract::class,
            TodoBalanceLogRepositoryContract::class,
            TodoRotatingGroupRepositoryContract::class,
            TodoRotatingItemRepositoryContract::class,
            TodoSettingRepositoryContract::class,
            TodoSubItemRepositoryContract::class,
            TodoTaskNodeRepositoryContract::class,
            TodoTemplateRepositoryContract::class,
        ];
    }

    public function appMorphMaps(): array
    {
        return [];
    }

    public function registerApp(): void
    {
        $this->app->bind(ActiveTimerRepositoryContract::class, function () {
            return new ActiveTimerRepository(
                new ActiveTimer,
                $this->app->make('log')
            );
        });
        $this->app->bind(TimeEntryRepositoryContract::class, function () {
            return new TimeEntryRepository(
                new TimeEntry,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoBalanceRepositoryContract::class, function () {
            return new TodoBalanceRepository(
                new TodoBalance,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoBalanceLogRepositoryContract::class, function () {
            return new TodoBalanceLogRepository(
                new TodoBalanceLog,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoSettingRepositoryContract::class, function () {
            return new TodoSettingRepository(
                new TodoSetting,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoRotatingGroupRepositoryContract::class, function () {
            return new TodoRotatingGroupRepository(
                new TodoRotatingGroup,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoRotatingItemRepositoryContract::class, function () {
            return new TodoRotatingItemRepository(
                new TodoRotatingItem,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoSubItemRepositoryContract::class, function () {
            return new TodoSubItemRepository(
                new TodoSubItem,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoTaskNodeRepositoryContract::class, function () {
            return new TodoTaskNodeRepository(
                new TodoTaskNode,
                $this->app->make('log')
            );
        });
        $this->app->bind(TodoTemplateRepositoryContract::class, function () {
            return new TodoTemplateRepository(
                new TodoTemplate,
                $this->app->make('log')
            );
        });
    }
}
