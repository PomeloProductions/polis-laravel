<?php

declare(strict_types=1);

namespace Polis\Services\Todo;

use App\Models\User\User;
use Illuminate\Support\Carbon;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\PeriodLadderContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Contracts\Services\PeriodGenerationServiceContract;
use Polis\Models\User\UserPage;

/**
 * Todo-facing facade over the generic {@see PeriodGenerationServiceContract}.
 * It preserves the User-centric API the Todo controller/commands rely on while
 * delegating the actual period-page generation to the generic engine driven by
 * a {@see TodoPeriodLadder}.
 *
 * The engine and ladder are entity-generic ({@see IsAnEntityContract}),
 * so an organization-owned Todo surface can reuse the same machinery; this
 * facade simply pins the owner to a User.
 */
class TodoGenerationService
{
    public function __construct(
        protected PeriodGenerationServiceContract $generator,
        protected TodoPeriodLadder $ladder,
        protected UserPageRepositoryContract $pageRepository,
    ) {}

    public function ladder(): PeriodLadderContract
    {
        return $this->ladder;
    }

    public function ensureCurrentPeriods(User $user, Carbon $date): UserPage
    {
        return $this->generator->ensureCurrentPeriods($this->ladder, $user, $date);
    }

    public function findRootTodoPage(User $user): ?UserPage
    {
        return $this->generator->findRootPage($this->ladder, $user);
    }

    public function getWeekStart(User $user, Carbon $date): Carbon
    {
        return $this->ladder->weekStart($user, $date);
    }

    /**
     * Find a todo page for the user whose config_json matches the given level
     * and additional key/value fingerprint.
     *
     * @param  array<string, scalar>  $configMatch
     */
    public function findPageByConfig(User $user, string $level, array $configMatch): ?UserPage
    {
        $pages = $this->pageRepository->findAll([
            ['user_id', '=', $user->id],
            ['page_type', '=', 'todo'],
        ], [], [], [], null);

        return $pages->first(function (UserPage $page) use ($level, $configMatch) {
            $config = $page->config_json ?? [];
            if (($config['todo_level'] ?? null) !== $level) {
                return false;
            }
            foreach ($configMatch as $key => $value) {
                if (($config[$key] ?? null) != $value) {
                    return false;
                }
            }

            return true;
        });
    }

    public function findPageBySlug(User $user, string $slug): ?UserPage
    {
        return $this->pageRepository->findAll([
            ['user_id', '=', $user->id],
            ['slug', '=', $slug],
            ['page_type', '=', 'todo'],
        ])->first();
    }
}
