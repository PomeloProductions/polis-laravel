<?php

declare(strict_types=1);

namespace Polis\Services\Todo;

use Illuminate\Support\Carbon;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Contracts\Services\PeriodComponentCopierContract;
use Polis\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;
use Polis\Support\CopyRuleSet;

/**
 * Todo's {@see PeriodComponentCopierContract}: carries a component forward onto
 * a newly generated period page. `todo_task` components get a relational
 * tree copy (with balance sync) via {@see TodoTaskTreeService}; all other
 * component types get their config_json carried forward with Todo's copy rules
 * (tallies, deficits, group counts, completion resets) applied.
 *
 * The numeric increment|preserve|reset transform is delegated to the generic
 * {@see CopyRuleSet}; this class owns only the Todo-shaped config_json walk.
 */
class TodoPeriodComponentCopier implements PeriodComponentCopierContract
{
    protected CopyRuleSet $rules;

    public function __construct(
        protected UserPageComponentRepositoryContract $componentRepository,
        protected TodoTaskTreeService $treeService,
    ) {
        $this->rules = new CopyRuleSet;
    }

    public function copyComponent(UserPageComponent $source, UserPage $targetPage, ?Carbon $targetDate): void
    {
        if ($source->component_type === 'todo_task') {
            $newComponent = $this->componentRepository->create([
                'user_page_id' => $targetPage->id,
                'component_type' => $source->component_type,
                'display_order' => $source->display_order,
                'config_json' => [],
            ]);

            $targetDayOfWeek = $targetDate?->dayOfWeek;
            $this->treeService->copyForwardNodes($source, $newComponent, $targetDayOfWeek);

            return;
        }

        $config = $source->config_json ?? [];
        $newConfig = $this->applyCopyRules($config, $targetDate);

        $this->componentRepository->create([
            'user_page_id' => $targetPage->id,
            'component_type' => $source->component_type,
            'display_order' => $source->display_order,
            'config_json' => $newConfig,
        ]);
    }

    /**
     * Apply Todo carry-forward rules to a non-todo_task component's config_json.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function applyCopyRules(array $config, ?Carbon $targetDate = null): array
    {
        $schedule = $config['schedule'] ?? null;
        $isScheduledDay = true;
        if (is_array($schedule) && $targetDate) {
            $isScheduledDay = in_array($targetDate->dayOfWeek, $schedule, true);
        }

        if (isset($config['items']) && is_array($config['items'])) {
            $config['items'] = array_map(fn (array $item) => $this->applyCopyToItem($item), $config['items']);
        }

        if (isset($config['tally']) && isset($config['on_copy']) && ! isset($config['groups'])) {
            if ($isScheduledDay) {
                $config['tally'] = $this->rules->apply($config['tally'], $config['on_copy']);
            }
        }

        if (isset($config['groups']) && is_array($config['groups'])) {
            $tallyStep = $config['tally_step'] ?? 1;
            if ($isScheduledDay) {
                $config['tally'] = ($config['tally'] ?? 0) + $tallyStep;
            }

            $config['groups'] = array_map(function (array $group) {
                if (isset($group['items']) && is_array($group['items'])) {
                    $group['items'] = array_map(fn (array $item) => $this->applyCopyToItem($item), $group['items']);
                }
                $onCopy = $group['on_copy'] ?? CopyRuleSet::RULE_PRESERVE;
                $group['count_this_group'] = $this->rules->apply(
                    $group['count_this_group'] ?? 0,
                    $onCopy
                );

                return $group;
            }, $config['groups']);
        }

        if (array_key_exists('logged_time', $config)) {
            $config['logged_time'] = 0;
        }

        if (isset($config['projects']) && is_array($config['projects'])) {
            $config['projects'] = array_map(function (array $project) {
                $project['deficit'] = ($project['deficit'] ?? 0)
                    + ($project['budgeted_hours'] ?? 0)
                    - ($project['logged_hours'] ?? 0);
                $project['logged_hours'] = 0;

                return $project;
            }, $config['projects']);
        }

        if (isset($config['categories']) && is_array($config['categories'])) {
            $config['categories'] = array_map(function (array $category) {
                if (isset($category['items']) && is_array($category['items'])) {
                    $category['items'] = array_map(function (array $item) {
                        $item = $this->applyCopyToItem($item);
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

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function applyCopyToItem(array $item): array
    {
        $onCopy = $item['on_copy'] ?? CopyRuleSet::RULE_INCREMENT;

        if (isset($item['tally'])) {
            $item['tally'] = $this->rules->apply($item['tally'], $onCopy);
        }

        if (isset($item['completed'])) {
            $item['completed'] = false;
        }

        if (isset($item['sub_items']) && is_array($item['sub_items'])) {
            $item['sub_items'] = array_map(fn (array $sub) => $this->applyCopyToItem($sub), $item['sub_items']);
        }

        return $item;
    }
}
