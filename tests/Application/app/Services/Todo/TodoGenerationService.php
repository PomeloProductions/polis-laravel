<?php

declare(strict_types=1);

namespace App\Services\Todo;

use App\Contracts\Repositories\User\TodoSettingRepositoryContract;
use App\Contracts\Repositories\User\TodoTemplateRepositoryContract;
use App\Models\User\User;
use Illuminate\Support\Carbon;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Contracts\Repositories\User\UserPageRepositoryContract;
use Polis\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;

class TodoGenerationService
{
    public function __construct(
        protected UserPageRepositoryContract $pageRepository,
        protected UserPageComponentRepositoryContract $componentRepository,
        protected TodoSettingRepositoryContract $settingRepository,
        protected TodoTemplateRepositoryContract $templateRepository,
        protected TodoTaskTreeService $treeService,
    ) {}

    public function ensureCurrentPeriods(User $user, Carbon $date): UserPage
    {
        $rootPage = $this->findRootTodoPage($user);
        if (! $rootPage) {
            throw new \RuntimeException('User does not have a todo root page.');
        }

        $yearPage = $this->ensureYearPage($user, $date->year, $rootPage);
        $monthPage = $this->ensureMonthPage($user, $date, $yearPage);
        $weekPage = $this->ensureWeekPage($user, $date, $monthPage);
        $dayPage = $this->ensureDayPage($user, $date, $weekPage);

        return $dayPage;
    }

    public function findRootTodoPage(User $user): ?UserPage
    {
        $pages = $this->pageRepository->findAll([
            ['user_id', '=', $user->id],
            ['page_type', '=', 'todo'],
        ], [], [], [], null);

        return $pages->first(function (UserPage $page) {
            $config = $page->config_json ?? [];
            return ($config['todo_level'] ?? null) === 'root';
        });
    }

    public function ensureYearPage(User $user, int $year, UserPage $rootPage): UserPage
    {
        $existing = $this->findChildByConfig($rootPage, 'year', ['todo_year' => $year]);
        if ($existing) {
            return $existing;
        }

        return $this->generateYearPage($user, $year, $rootPage);
    }

    public function generateYearPage(User $user, int $year, UserPage $rootPage): UserPage
    {
        $previousYear = $this->findMostRecentPage($user, 'year');

        $slug = (string) $year;
        $page = $this->pageRepository->create([
            'user_id' => $user->id,
            'slug' => $this->ensureUniqueSlug($user, $slug),
            'name' => (string) $year,
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'page_type' => 'todo',
            'display_order' => $year,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $rootPage->id,
            'config_json' => [
                'todo_level' => 'year',
                'todo_year' => $year,
            ],
        ]);

        if ($previousYear) {
            $this->copyForwardComponents($previousYear, $page);
        } else {
            $this->createFromTemplate($user, 'year', $page);
        }

        return $page;
    }

    public function ensureMonthPage(User $user, Carbon $date, UserPage $yearPage): UserPage
    {
        $existing = $this->findChildByConfig($yearPage, 'month', ['todo_month' => $date->month]);
        if ($existing) {
            return $existing;
        }

        return $this->generateMonthPage($user, $date, $yearPage);
    }

    public function generateMonthPage(User $user, Carbon $date, UserPage $yearPage): UserPage
    {
        $previousMonth = $this->findMostRecentPage($user, 'month');

        $slug = "{$date->year}/{$date->format('m')}";
        $page = $this->pageRepository->create([
            'user_id' => $user->id,
            'slug' => $this->ensureUniqueSlug($user, $slug),
            'name' => $date->format('F'),
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'page_type' => 'todo',
            'display_order' => $date->month,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $yearPage->id,
            'config_json' => [
                'todo_level' => 'month',
                'todo_month' => $date->month,
                'todo_year' => $date->year,
            ],
        ]);

        if ($previousMonth) {
            $this->copyForwardComponents($previousMonth, $page);
        } else {
            $this->createFromTemplate($user, 'month', $page);
        }

        return $page;
    }

    public function ensureWeekPage(User $user, Carbon $date, UserPage $monthPage): UserPage
    {
        $weekStart = $this->getWeekStart($user, $date);

        $existing = $this->findChildByConfig($monthPage, 'week', ['todo_week_start' => $weekStart->toDateString()]);
        if ($existing) {
            return $existing;
        }

        return $this->generateWeekPage($user, $date, $monthPage);
    }

    public function generateWeekPage(User $user, Carbon $date, UserPage $monthPage): UserPage
    {
        $weekStart = $this->getWeekStart($user, $date);
        $weekEnd = $this->getWeekEnd($user, $weekStart);
        $weekNumber = $this->getWeekNumber($weekStart);
        $previousWeek = $this->findMostRecentPage($user, 'week');

        $slug = "{$weekStart->year}/{$weekStart->format('m')}/week-{$weekNumber}";
        $name = "Week {$weekNumber} ({$weekStart->format('n/j')} - {$weekEnd->format('n/j')})";

        $page = $this->pageRepository->create([
            'user_id' => $user->id,
            'slug' => $this->ensureUniqueSlug($user, $slug),
            'name' => $name,
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'page_type' => 'todo',
            'display_order' => $weekNumber,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $monthPage->id,
            'config_json' => [
                'todo_level' => 'week',
                'todo_week_start' => $weekStart->toDateString(),
                'todo_week_end' => $weekEnd->toDateString(),
                'todo_year' => $weekStart->year,
                'todo_month' => $weekStart->month,
            ],
        ]);

        if ($previousWeek) {
            $this->copyForwardComponents($previousWeek, $page);
        } else {
            $this->createFromTemplate($user, 'week', $page);
        }

        return $page;
    }

    public function ensureDayPage(User $user, Carbon $date, UserPage $weekPage): UserPage
    {
        $existing = $this->findChildByConfig($weekPage, 'day', ['todo_date' => $date->toDateString()]);
        if ($existing) {
            return $existing;
        }

        return $this->generateDayPage($user, $date, $weekPage);
    }

    public function generateDayPage(User $user, Carbon $date, UserPage $weekPage): UserPage
    {
        $previousDay = $this->findMostRecentPage($user, 'day');

        $weekStart = $this->getWeekStart($user, $date);
        $weekNumber = $this->getWeekNumber($weekStart);
        $slug = "{$date->year}/{$date->format('m')}/week-{$weekNumber}/{$date->day}";
        $name = $date->format('l n/j');

        $page = $this->pageRepository->create([
            'user_id' => $user->id,
            'slug' => $this->ensureUniqueSlug($user, $slug),
            'name' => $name,
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'page_type' => 'todo',
            'display_order' => $date->day,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'parent_page_id' => $weekPage->id,
            'config_json' => [
                'todo_level' => 'day',
                'todo_date' => $date->toDateString(),
                'todo_year' => $date->year,
                'todo_month' => $date->month,
            ],
        ]);

        if ($previousDay) {
            $this->copyForwardComponents($previousDay, $page, $date);
        } else {
            $this->createFromTemplate($user, 'day', $page);
        }

        return $page;
    }

    public function copyForwardComponents(UserPage $source, UserPage $target, ?Carbon $targetDate = null): void
    {
        $source->load('components');

        foreach ($source->components as $component) {
            if ($component->component_type === 'todo_task') {
                // Create the new component shell
                $newComponent = $this->componentRepository->create([
                    'user_page_id' => $target->id,
                    'component_type' => $component->component_type,
                    'display_order' => $component->display_order,
                    'config_json' => [],
                ]);

                // Copy relational nodes with copy rules applied
                $targetDayOfWeek = $targetDate ? $targetDate->dayOfWeek : null;
                $this->treeService->copyForwardNodes($component, $newComponent, $targetDayOfWeek);
            } else {
                $config = $component->config_json ?? [];
                $newConfig = $this->applyCopyRules($config, $targetDate);

                $this->componentRepository->create([
                    'user_page_id' => $target->id,
                    'component_type' => $component->component_type,
                    'display_order' => $component->display_order,
                    'config_json' => $newConfig,
                ]);
            }
        }
    }

    public function applyCopyRules(array $config, ?Carbon $targetDate = null): array
    {
        // Check if today is a scheduled day for this component
        $schedule = $config['schedule'] ?? null; // array of day numbers (0=Sun..6=Sat)
        $isScheduledDay = true;
        if (is_array($schedule) && $targetDate) {
            $isScheduledDay = in_array($targetDate->dayOfWeek, $schedule, true);
        }

        if (isset($config['items']) && is_array($config['items'])) {
            $config['items'] = array_map(fn (array $item) => $this->applyCopyToItem($item), $config['items']);
        }

        // Apply on_copy to main tally for non-group components (e.g. tally_list)
        if (isset($config['tally']) && isset($config['on_copy']) && ! isset($config['groups'])) {
            if ($isScheduledDay) {
                $config['tally'] = $this->applyCopyValue($config['tally'], $config['on_copy']);
            }
        }

        if (isset($config['groups']) && is_array($config['groups'])) {
            // Priority groups: tally is a lifetime accumulator, add tally_step on scheduled days
            $tallyStep = $config['tally_step'] ?? 1;
            if ($isScheduledDay) {
                $config['tally'] = ($config['tally'] ?? 0) + $tallyStep;
            }

            $config['groups'] = array_map(function (array $group) {
                if (isset($group['items']) && is_array($group['items'])) {
                    $group['items'] = array_map(fn (array $item) => $this->applyCopyToItem($item), $group['items']);
                }
                // Default to 'preserve' for group done counts (lifetime totals)
                $onCopy = $group['on_copy'] ?? 'preserve';
                $group['count_this_group'] = $this->applyCopyValue(
                    $group['count_this_group'] ?? 0,
                    $onCopy
                );
                return $group;
            }, $config['groups']);
        }

        // Reset logged_time for priority groups (daily timer resets)
        if (array_key_exists('logged_time', $config)) {
            $config['logged_time'] = 0;
        }

        if (isset($config['projects']) && is_array($config['projects'])) {
            $config['projects'] = array_map(function (array $project) {
                // Accumulate deficit: deficit = deficit + (budgeted - logged)
                $project['deficit'] = ($project['deficit'] ?? 0)
                    + ($project['budgeted_hours'] ?? 0)
                    - ($project['logged_hours'] ?? 0);
                $project['logged_hours'] = 0;
                return $project;
            }, $config['projects']);
        }

        // Category items: reset logged_hours, apply copy rules
        if (isset($config['categories']) && is_array($config['categories'])) {
            $config['categories'] = array_map(function (array $category) {
                if (isset($category['items']) && is_array($category['items'])) {
                    $category['items'] = array_map(function (array $item) {
                        $item = $this->applyCopyToItem($item);
                        // Reset daily logged hours
                        if (array_key_exists('logged_hours', $item)) {
                            $item['logged_hours'] = 0;
                        }
                        return $item;
                    }, $category['items']);
                }
                return $category;
            }, $config['categories']);
        }

        return $config;
    }

    public function applyCopyRulesToTaskConfig(array $config, ?Carbon $targetDate = null): array
    {
        if (isset($config['root']) && is_array($config['root'])) {
            $targetDayOfWeek = $targetDate ? $targetDate->dayOfWeek : null;
            $config['root'] = $this->applyCopyRulesToTaskNode($config['root'], $targetDayOfWeek);
        }

        return $config;
    }

    protected function applyCopyRulesToTaskNode(array $node, ?int $targetDayOfWeek): array
    {
        $onCopy = $node['on_copy'] ?? 'increment';
        $schedule = $node['schedule'] ?? [0, 1, 2, 3, 4, 5, 6];
        $nodeIsScheduled = $targetDayOfWeek === null || in_array($targetDayOfWeek, $schedule, true);
        $taskType = $node['task_type'] ?? 'line_item';

        $trackingMode = $node['tracking_mode'] ?? 'units';

        if ($trackingMode === 'hours') {
            // Hours mode: tally IS the hour balance
            // Add daily hours to balance on scheduled days
            if ($nodeIsScheduled && $onCopy === 'increment') {
                $node['tally'] = ($node['tally'] ?? 0) + ($node['tally_step'] ?? 0);
            }
            // Reset daily logged hours (already subtracted from tally in real-time via syncLoggedHoursForEntry)
            if (array_key_exists('logged_hours', $node)) {
                $node['logged_hours'] = 0;
            }
            if (array_key_exists('logged_time', $node)) {
                $node['logged_time'] = 0;
            }
        } else {
            // Units mode: tally is a count, time_budget_hours is per-unit
            if ($nodeIsScheduled && $onCopy === 'increment' && isset($node['tally'])) {
                $node['tally'] = $node['tally'] + ($node['tally_step'] ?? 1);
            }

            // Reset daily logged hours, accumulate deficit for non-category nodes
            if ($taskType !== 'category') {
                if (isset($node['time_budget_hours']) && $node['time_budget_hours'] > 0) {
                    $tallyMultiplier = $node['tally'] ?? 1;
                    $budgeted = $tallyMultiplier * $node['time_budget_hours'];
                    $logged = $node['logged_hours'] ?? $node['logged_time'] ?? 0;
                    $node['deficit'] = ($node['deficit'] ?? 0) + ($budgeted - $logged);
                }
                if (array_key_exists('logged_hours', $node)) {
                    $node['logged_hours'] = 0;
                }
                if (array_key_exists('logged_time', $node)) {
                    $node['logged_time'] = 0;
                }
            }
        }

        // Recurse into children (category)
        if (! empty($node['children'])) {
            foreach ($node['children'] as &$child) {
                $child = $this->applyCopyRulesToTaskNode($child, $targetDayOfWeek);
            }
            unset($child);
        }

        // Handle rotating groups
        if (! empty($node['groups'])) {
            foreach ($node['groups'] as &$group) {
                // Preserve count_this_group by default
                $groupOnCopy = $group['on_copy'] ?? 'preserve';
                $group['count_this_group'] = $this->applyCopyValue(
                    $group['count_this_group'] ?? 0,
                    $groupOnCopy
                );
                if (isset($group['items']) && is_array($group['items'])) {
                    foreach ($group['items'] as &$item) {
                        $item = $this->applyCopyToItem($item);
                    }
                    unset($item);
                }
                // Recurse into nested sub_groups on the group
                if (! empty($group['sub_groups'])) {
                    foreach ($group['sub_groups'] as &$subGroup) {
                        $subGroupOnCopy = $subGroup['on_copy'] ?? 'preserve';
                        $subGroup['count_this_group'] = $this->applyCopyValue(
                            $subGroup['count_this_group'] ?? 0,
                            $subGroupOnCopy
                        );
                        if (isset($subGroup['items']) && is_array($subGroup['items'])) {
                            foreach ($subGroup['items'] as &$subItem) {
                                $subItem = $this->applyCopyToItem($subItem);
                            }
                            unset($subItem);
                        }
                    }
                    unset($subGroup);
                }
            }
            unset($group);
        }

        // Handle line item
        if ($taskType === 'line_item') {
            $node['completed'] = false;
            // Reset sub_item completion
            if (! empty($node['sub_items'])) {
                foreach ($node['sub_items'] as &$subItem) {
                    if (isset($subItem['completed'])) {
                        $subItem['completed'] = false;
                    }
                }
                unset($subItem);
            }
        }

        return $node;
    }

    protected function applyCopyToItem(array $item): array
    {
        $onCopy = $item['on_copy'] ?? 'increment';

        if (isset($item['tally'])) {
            $item['tally'] = $this->applyCopyValue($item['tally'], $onCopy);
        }

        if (isset($item['completed'])) {
            $item['completed'] = false;
        }

        if (isset($item['sub_items']) && is_array($item['sub_items'])) {
            $item['sub_items'] = array_map(fn (array $sub) => $this->applyCopyToItem($sub), $item['sub_items']);
        }

        return $item;
    }

    protected function applyCopyValue(int|float $value, string $onCopy): int|float
    {
        return match ($onCopy) {
            'increment' => $value + 1,
            'preserve' => $value,
            default => 0,
        };
    }

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

    public function findMostRecentPage(User $user, string $level): ?UserPage
    {
        $pages = $this->pageRepository->findAll([
            ['user_id', '=', $user->id],
            ['page_type', '=', 'todo'],
        ], [], [], [], null);

        $filtered = $pages->filter(function (UserPage $page) use ($level) {
            return ($page->config_json['todo_level'] ?? null) === $level;
        });

        return $filtered->sortByDesc('id')->first();
    }

    public function createFromTemplate(User $user, string $level, UserPage $page): void
    {
        $templates = $this->templateRepository->findAll([
            ['user_id', '=', $user->id],
            ['level', '=', $level],
        ]);

        $template = $templates->first();
        if (! $template) {
            return;
        }

        $sections = $template->sections_json ?? [];
        foreach ($sections as $order => $section) {
            $this->componentRepository->create([
                'user_page_id' => $page->id,
                'component_type' => $section['type'] ?? 'todo_bullet_list',
                'display_order' => $order,
                'config_json' => $section['config'] ?? [
                    'label' => $section['label'] ?? 'Section',
                    'items' => [],
                ],
            ]);
        }
    }

    public function getWeekStart(User $user, Carbon $date): Carbon
    {
        $settings = $this->settingRepository->findAll([
            ['user_id', '=', $user->id],
        ])->first();

        $weekStartDay = $settings ? $settings->week_start_day : 0;

        $current = $date->copy();
        $monthStart = $date->copy()->startOfMonth();

        while ($current->dayOfWeek !== $weekStartDay) {
            $current->subDay();
        }

        if ($current->lt($monthStart)) {
            $current = $monthStart->copy();
        }

        return $current->startOfDay();
    }

    public function getWeekEnd(User $user, Carbon $weekStart): Carbon
    {
        $settings = $this->settingRepository->findAll([
            ['user_id', '=', $user->id],
        ])->first();

        $weekStartDay = $settings ? $settings->week_start_day : 0;

        $endDay = ($weekStartDay + 6) % 7;
        $current = $weekStart->copy();
        while ($current->dayOfWeek !== $endDay) {
            $current->addDay();
        }

        $monthEnd = $weekStart->copy()->endOfMonth()->startOfDay();
        if ($current->gt($monthEnd)) {
            $current = $monthEnd;
        }

        return $current->startOfDay();
    }

    public function getWeekNumber(Carbon $weekStart): int
    {
        if ($weekStart->day === 1) {
            return 1;
        }

        $count = 1;
        $monthStart = $weekStart->copy()->startOfMonth();
        $current = $monthStart->copy()->addDay();

        while ($current->lte($weekStart)) {
            if ($current->dayOfWeek === 0) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    protected function findChildByConfig(UserPage $parent, string $level, array $configMatch): ?UserPage
    {
        $children = $this->pageRepository->findAll([
            ['parent_page_id', '=', $parent->id],
            ['page_type', '=', 'todo'],
        ], [], [], [], null);

        return $children->first(function (UserPage $page) use ($level, $configMatch) {
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

    protected function ensureUniqueSlug(User $user, string $base): string
    {
        $slug = $base;
        $original = $slug;
        $counter = 1;

        while ($this->pageRepository->findAll([
            ['user_id', '=', $user->id],
            ['slug', '=', $slug],
        ])->isNotEmpty()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
