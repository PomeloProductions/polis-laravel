<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;

class TodoMigrateWidgets extends Command
{
    protected $signature = 'todo:migrate-widgets {--dry-run : Show what would be changed without modifying data}';

    protected $description = 'Migrate old todo widget types to unified todo_task format';

    private const MIGRATEABLE_TYPES = [
        'todo_bullet_list',
        'todo_ordered_list',
        'todo_categorized_list',
        'todo_time_tracker',
        'todo_priority_groups',
        'todo_tally_list',
    ];

    public function handle(UserPageComponentRepositoryContract $componentRepository): int
    {
        $dryRun = $this->option('dry-run');
        $migrated = 0;
        $errors = 0;

        foreach (self::MIGRATEABLE_TYPES as $type) {
            $components = $componentRepository->findAll([
                ['component_type', '=', $type],
            ]);

            foreach ($components as $component) {
                $oldConfig = $component->config_json ?? [];

                try {
                    $newRoot = $this->convertToTaskNode($type, $oldConfig);
                    $newConfig = [
                        'config_json_backup' => $oldConfig,
                        'root' => $newRoot,
                    ];

                    $label = $oldConfig['label'] ?? 'unnamed';

                    if ($dryRun) {
                        $this->info("Would migrate component #{$component->id} ({$type}): {$label}");
                    } else {
                        $componentRepository->update($component, [
                            'component_type' => 'todo_task',
                            'config_json' => $newConfig,
                        ]);
                        $this->info("Migrated component #{$component->id} ({$type}): {$label}");
                    }

                    $migrated++;
                } catch (\Throwable $e) {
                    $this->error("Failed to migrate component #{$component->id} ({$type}): {$e->getMessage()}");
                    $errors++;
                }
            }
        }

        $action = $dryRun ? 'Would migrate' : 'Migrated';
        $this->info("{$action} {$migrated} component(s), {$errors} error(s).");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function convertToTaskNode(string $type, array $config): array
    {
        return match ($type) {
            'todo_bullet_list' => $this->convertBulletList($config),
            'todo_ordered_list' => $this->convertOrderedList($config),
            'todo_categorized_list' => $this->convertCategorizedList($config),
            'todo_time_tracker' => $this->convertTimeTracker($config),
            'todo_priority_groups' => $this->convertPriorityGroups($config),
            'todo_tally_list' => $this->convertTallyList($config),
            default => throw new \InvalidArgumentException("Unknown type: {$type}"),
        };
    }

    private function convertBulletList(array $config): array
    {
        $children = [];
        foreach (($config['items'] ?? []) as $item) {
            $children[] = [
                'id' => $item['id'] ?? $this->makeId('tn'),
                'task_type' => 'line_item',
                'label' => $item['text'] ?? '',
                'completed' => $item['completed'] ?? false,
                'on_copy' => $item['on_copy'] ?? 'reset',
                'sub_items' => [],
            ];
        }

        return [
            'id' => $this->makeId('tn'),
            'task_type' => 'category',
            'label' => $config['label'] ?? 'Goals',
            'on_copy' => 'increment',
            'children' => $children,
        ];
    }

    private function convertOrderedList(array $config): array
    {
        $children = [];
        foreach (($config['items'] ?? []) as $item) {
            $subItems = [];
            foreach (($item['sub_items'] ?? []) as $sub) {
                $subItems[] = [
                    'id' => $sub['id'] ?? $this->makeId('si'),
                    'text' => $sub['text'] ?? '',
                ];
            }

            $children[] = [
                'id' => $item['id'] ?? $this->makeId('tn'),
                'task_type' => 'line_item',
                'label' => $item['text'] ?? '',
                'on_copy' => $item['on_copy'] ?? 'reset',
                'sub_items' => $subItems,
            ];
        }

        return [
            'id' => $this->makeId('tn'),
            'task_type' => 'category',
            'label' => $config['label'] ?? 'Work Priorities',
            'on_copy' => 'increment',
            'children' => $children,
        ];
    }

    private function convertCategorizedList(array $config): array
    {
        $children = [];
        foreach (($config['categories'] ?? []) as $category) {
            $catChildren = [];
            foreach (($category['items'] ?? []) as $item) {
                $subItems = [];
                foreach (($item['sub_items'] ?? []) as $sub) {
                    $subItems[] = [
                        'id' => $sub['id'] ?? $this->makeId('si'),
                        'text' => $sub['text'] ?? '',
                    ];
                }

                $child = [
                    'id' => $item['id'] ?? $this->makeId('tn'),
                    'task_type' => 'line_item',
                    'label' => $item['text'] ?? '',
                    'on_copy' => $item['on_copy'] ?? 'increment',
                    'sub_items' => $subItems,
                ];

                if (isset($item['tally'])) {
                    $child['tally'] = $item['tally'];
                }
                if (isset($item['time_hours']) && $item['time_hours'] > 0) {
                    $child['time_budget_hours'] = $item['time_hours'];
                    $child['logged_hours'] = $item['logged_hours'] ?? 0;
                }

                $catChildren[] = $child;
            }

            $catNode = [
                'id' => $category['id'] ?? $this->makeId('tn'),
                'task_type' => 'category',
                'label' => $category['label'] ?? 'Category',
                'on_copy' => 'increment',
                'children' => $catChildren,
            ];

            if (isset($category['deficit'])) {
                // Deficit will be computed from children, but preserve if they don't have it
            }

            $children[] = $catNode;
        }

        $root = [
            'id' => $this->makeId('tn'),
            'task_type' => 'category',
            'label' => $config['label'] ?? 'Categories',
            'on_copy' => 'increment',
            'children' => $children,
        ];

        if (isset($config['schedule'])) {
            $root['schedule'] = $config['schedule'];
        }

        return $root;
    }

    private function convertTimeTracker(array $config): array
    {
        $children = [];
        foreach (($config['projects'] ?? []) as $project) {
            $children[] = [
                'id' => $project['id'] ?? $this->makeId('tn'),
                'task_type' => 'line_item',
                'label' => $project['name'] ?? '',
                'time_budget_hours' => $project['budgeted_hours'] ?? 0,
                'logged_hours' => $project['logged_hours'] ?? 0,
                'deficit' => $project['deficit'] ?? 0,
                'on_copy' => 'increment',
            ];
        }

        return [
            'id' => $this->makeId('tn'),
            'task_type' => 'category',
            'label' => $config['label'] ?? 'Work Hours',
            'on_copy' => 'increment',
            'children' => $children,
        ];
    }

    private function convertPriorityGroups(array $config): array
    {
        $groups = [];
        foreach (($config['groups'] ?? []) as $group) {
            $items = [];
            foreach (($group['items'] ?? []) as $item) {
                $items[] = [
                    'id' => $item['id'] ?? $this->makeId('ri'),
                    'text' => $item['text'] ?? '',
                    'last_date' => $item['last_date'] ?? null,
                    'on_copy' => $item['on_copy'] ?? 'preserve',
                ];
            }

            $groups[] = [
                'group_number' => $group['group_number'] ?? 1,
                'label' => $group['label'] ?? 'Priority',
                'count_this_group' => $group['count_this_group'] ?? 0,
                'on_copy' => $group['on_copy'] ?? 'preserve',
                'items' => $items,
            ];
        }

        $node = [
            'id' => $this->makeId('tn'),
            'task_type' => 'rotating',
            'label' => $config['label'] ?? 'Priority Groups',
            'tally' => $config['tally'] ?? 0,
            'tally_step' => $config['tally_step'] ?? 1,
            'on_copy' => 'increment',
            'groups' => $groups,
            'custom_groups' => $config['custom_groups'] ?? false,
            'logged_time' => $config['logged_time'] ?? 0,
        ];

        if (isset($config['schedule'])) {
            $node['schedule'] = $config['schedule'];
        }
        if (isset($config['time_budget']) && is_array($config['time_budget'])) {
            $node['time_budget_hours'] = $config['time_budget']['hours'] ?? 0;
        }
        if (isset($config['description'])) {
            $node['description'] = $config['description'];
        }

        return $node;
    }

    private function convertTallyList(array $config): array
    {
        $children = [];
        foreach (($config['items'] ?? []) as $item) {
            $children[] = [
                'id' => $item['id'] ?? $this->makeId('tn'),
                'task_type' => 'line_item',
                'label' => $item['text'] ?? '',
                'tally' => $item['tally'] ?? 0,
                'on_copy' => $item['on_copy'] ?? 'increment',
                'last_date' => $item['last_date'] ?? null,
            ];
        }

        return [
            'id' => $this->makeId('tn'),
            'task_type' => 'category',
            'label' => $config['label'] ?? 'Tallies',
            'tally' => $config['tally'] ?? 0,
            'on_copy' => 'increment',
            'children' => $children,
        ];
    }

    private function makeId(string $prefix): string
    {
        return $prefix.'-'.time().'-'.substr(md5((string) mt_rand()), 0, 6);
    }
}
