<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Polis\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;

class TodoMigrateOldWidgets extends Command
{
    protected $signature = 'todo:migrate-old-widgets {--dry-run : Show what would be done without making changes}';

    protected $description = 'Convert old widget types to todo_task on all pre-migration day pages';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find a reference page that already uses todo_task (today's page)
        $refPage = UserPage::orderBy('id', 'desc')
            ->get()
            ->first(fn ($p) => ($p->config_json['todo_level'] ?? null) === 'day'
                && $p->components->contains('component_type', 'todo_task'));

        if (!$refPage) {
            $this->error('No reference todo_task page found.');
            return self::FAILURE;
        }

        $this->info("Reference page: {$refPage->config_json['todo_date']} (page {$refPage->id})");

        // Build reference templates keyed by label
        $refTemplates = [];
        foreach ($refPage->components as $comp) {
            if ($comp->component_type !== 'todo_task') continue;
            $root = $comp->config_json['root'] ?? null;
            if (!$root) continue;
            $refTemplates[$root['label']] = $root;
        }

        $this->info('Reference components: ' . implode(', ', array_keys($refTemplates)));

        // Map old component types + labels to reference labels
        $labelMap = [
            'Work Hours' => 'Work Hours',
            'Life Management' => 'Life Management',
            'Language Study' => 'Language Study',
            'Read' => 'Read',
            'Write' => 'Write',
            'Game' => 'Game',
            'Watch Serials' => 'Watch Serials',
            'Watch a Movie' => 'Watch a Movie',
            'Listen to a Composition' => 'Listen to a Composition',
        ];

        // Get all old day pages (non-todo_task)
        $oldPages = UserPage::orderBy('id')
            ->get()
            ->filter(fn ($p) => ($p->config_json['todo_level'] ?? null) === 'day'
                && !$p->components->contains('component_type', 'todo_task'));

        $this->info("Found {$oldPages->count()} old pages to migrate.");

        $migrated = 0;

        foreach ($oldPages as $page) {
            $date = $page->config_json['todo_date'] ?? '?';
            $oldComponents = $page->components;

            // Build old data map: label → old config
            $oldDataByLabel = [];
            foreach ($oldComponents as $comp) {
                $label = $comp->config_json['label'] ?? null;
                if ($label) {
                    $oldDataByLabel[$label] = [
                        'type' => $comp->component_type,
                        'config' => $comp->config_json,
                        'id' => $comp->id,
                    ];
                }
            }

            // For each reference component, create a migrated version
            $newComponents = [];
            foreach ($refTemplates as $label => $refRoot) {
                $oldData = $oldDataByLabel[$label] ?? null;
                if (!$oldData) {
                    // No old component for this label — use reference as-is with zeroed data
                    $newComponents[] = [
                        'label' => $label,
                        'root' => $this->zeroOutNode($refRoot),
                    ];
                    continue;
                }

                $mergedRoot = $this->mergeOldDataIntoTemplate($refRoot, $oldData['type'], $oldData['config']);
                $newComponents[] = [
                    'label' => $label,
                    'root' => $mergedRoot,
                    'backup' => $oldData['config'],
                ];
            }

            if ($dryRun) {
                $this->line("  {$date}: would migrate {$oldComponents->count()} old → " . count($newComponents) . " todo_task");
            } else {
                // Delete old components
                foreach ($oldComponents as $comp) {
                    $comp->delete();
                }

                // Create new todo_task components
                foreach ($newComponents as $i => $nc) {
                    UserPageComponent::create([
                        'user_page_id' => $page->id,
                        'component_type' => 'todo_task',
                        'display_order' => $i,
                        'config_json' => [
                            'config_json_backup' => $nc['backup'] ?? null,
                            'root' => $nc['root'],
                        ],
                    ]);
                }

                $this->line("  {$date}: migrated {$oldComponents->count()} → " . count($newComponents) . " todo_task");
            }

            $migrated++;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Migrated {$migrated} pages.");

        return self::SUCCESS;
    }

    /**
     * Merge old widget data into the reference todo_task template.
     * Preserves the reference structure (children, groups, etc.) but updates
     * tallies, logged hours, deficits, group counts, and last_dates from old data.
     */
    private function mergeOldDataIntoTemplate(array $refRoot, string $oldType, array $oldConfig): array
    {
        $root = $refRoot;

        switch ($oldType) {
            case 'todo_time_tracker':
                return $this->mergeTimeTracker($root, $oldConfig);

            case 'todo_categorized_list':
                return $this->mergeCategorizedList($root, $oldConfig);

            case 'todo_priority_groups':
                return $this->mergePriorityGroups($root, $oldConfig);

            case 'todo_tally_list':
                return $this->mergeTallyList($root, $oldConfig);

            default:
                return $root;
        }
    }

    /**
     * todo_time_tracker → category with line_item children (hours mode)
     * Old: { projects: [{ name, budgeted_hours, logged_hours, deficit }] }
     * New: category with line_item children, each with tally (= -deficit), logged_hours
     */
    private function mergeTimeTracker(array $root, array $oldConfig): array
    {
        $projectsByName = [];
        foreach ($oldConfig['projects'] ?? [] as $project) {
            $projectsByName[$project['name']] = $project;
        }

        if (!empty($root['children'])) {
            foreach ($root['children'] as &$child) {
                $oldProject = $projectsByName[$child['label']] ?? null;
                if ($oldProject) {
                    // deficit in old format is negative when behind, tally in new format = deficit value
                    $child['tally'] = $oldProject['deficit'] ?? 0;
                    $child['logged_hours'] = $oldProject['logged_hours'] ?? 0;
                }
            }
        }

        return $root;
    }

    /**
     * todo_categorized_list → category with category children (Life Management)
     * Old: { categories: [{ name, items: [{ text, tally, time_hours, logged_hours }] }] }
     * New: category > category children > line_item grandchildren
     */
    private function mergeCategorizedList(array $root, array $oldConfig): array
    {
        $categoriesByName = [];
        foreach ($oldConfig['categories'] ?? [] as $cat) {
            $name = $cat['label'] ?? $cat['name'] ?? '';
            $categoriesByName[$name] = $cat;
        }

        if (!empty($root['children'])) {
            foreach ($root['children'] as &$child) {
                $oldCat = $categoriesByName[$child['label']] ?? null;
                if (!$oldCat || empty($child['children'])) continue;

                $itemsByText = [];
                foreach ($oldCat['items'] ?? [] as $item) {
                    $itemsByText[$item['text']] = $item;
                }

                foreach ($child['children'] as &$grandchild) {
                    $oldItem = $itemsByText[$grandchild['label']] ?? null;
                    if ($oldItem) {
                        $grandchild['tally'] = $oldItem['tally'] ?? 0;
                        $grandchild['logged_hours'] = $oldItem['logged_hours'] ?? 0;
                    }
                }
            }
        }

        return $root;
    }

    /**
     * todo_priority_groups → rotating node
     * Old: { tally, groups: [{ group_number, count_this_group, items: [...] }], logged_time }
     * New: rotating with same structure
     */
    private function mergePriorityGroups(array $root, array $oldConfig): array
    {
        $root['tally'] = $oldConfig['tally'] ?? $root['tally'] ?? 0;
        $root['logged_time'] = $oldConfig['logged_time'] ?? 0;

        // Merge groups — use old group data for counts and items
        if (!empty($oldConfig['groups'])) {
            $oldGroupsByNum = [];
            foreach ($oldConfig['groups'] as $g) {
                $oldGroupsByNum[$g['group_number']] = $g;
            }

            // Use old groups directly if structure matches, otherwise merge
            if (!empty($root['groups'])) {
                foreach ($root['groups'] as &$group) {
                    $oldGroup = $oldGroupsByNum[$group['group_number']] ?? null;
                    if ($oldGroup) {
                        $group['count_this_group'] = $oldGroup['count_this_group'] ?? 0;
                        $group['last_date'] = $oldGroup['last_date'] ?? null;

                        // Merge items
                        if (!empty($oldGroup['items'])) {
                            $group['items'] = $oldGroup['items'];
                        }

                        // Merge sub_groups if present
                        if (!empty($oldGroup['sub_groups'])) {
                            $group['sub_groups'] = $oldGroup['sub_groups'];
                        }
                    }
                }
            } else {
                // No reference groups — use old groups directly
                $root['groups'] = $oldConfig['groups'];
            }
        }

        return $root;
    }

    /**
     * todo_tally_list → rotating node (Game, etc.)
     * Old: { tally, items: [{ text, tally, last_date }] }
     * New: rotating with groups
     */
    private function mergeTallyList(array $root, array $oldConfig): array
    {
        // Tally list items don't map cleanly to rotating groups.
        // Keep the reference structure but update the tally.
        $root['tally'] = $oldConfig['tally'] ?? $root['tally'] ?? 0;

        // The items in a tally_list are different from rotating group items.
        // Keep reference groups structure intact — the tally is the main data point.
        return $root;
    }

    /**
     * Zero out dynamic data in a node tree (for pages where no old component exists).
     */
    private function zeroOutNode(array $node): array
    {
        $node['tally'] = 0;
        $node['logged_hours'] = 0;
        $node['logged_time'] = 0;

        if (!empty($node['children'])) {
            foreach ($node['children'] as &$child) {
                $child = $this->zeroOutNode($child);
            }
        }

        if (!empty($node['groups'])) {
            foreach ($node['groups'] as &$group) {
                $group['count_this_group'] = 0;
            }
        }

        return $node;
    }
}
