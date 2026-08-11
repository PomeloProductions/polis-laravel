<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\TodoBalance;
use Illuminate\Console\Command;
use Polis\Models\User\UserPage;
use Polis\Models\User\UserPageComponent;

class TodoLinkBalances extends Command
{
    protected $signature = 'todo:link-balances {--dry-run : Show what would be done without making changes}';

    protected $description = 'Link todo_balance_id FK into task nodes in all todo_task components';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $balances = TodoBalance::all();

        // Build label → balance_id map and label → tracking_mode map
        $labelMap = [];
        $modeMap = [];
        foreach ($balances as $balance) {
            $labelMap[$balance->item_key] = $balance->id;
            $modeMap[$balance->item_key] = $balance->tracking_mode;
            $stripped = rtrim($balance->item_key, ' -');
            if ($stripped !== $balance->item_key) {
                $labelMap[$stripped] = $balance->id;
                $modeMap[$stripped] = $balance->tracking_mode;
            }
        }

        $components = UserPageComponent::where('component_type', 'todo_task')->get();
        $updated = 0;
        $linked = 0;
        $modeFixed = 0;

        foreach ($components as $component) {
            $config = $component->config_json;
            $root = $config['root'] ?? null;
            if (!$root) continue;

            $changed = false;
            $this->linkNode($root, $labelMap, $modeMap, $changed, $linked, $modeFixed);

            if ($changed) {
                $config['root'] = $root;
                if (!$dryRun) {
                    $component->config_json = $config;
                    $component->save();
                }
                $updated++;
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Updated {$updated} components, linked {$linked} nodes, fixed {$modeFixed} tracking modes.");

        return self::SUCCESS;
    }

    private function linkNode(array &$node, array $labelMap, array $modeMap, bool &$changed, int &$linked, int &$modeFixed): void
    {
        $label = $node['label'] ?? '';
        $taskType = $node['task_type'] ?? '';
        $stripped = rtrim($label, ' -');

        // Fix tracking_mode to match the authoritative TodoBalance record
        $correctMode = $modeMap[$label] ?? $modeMap[$stripped] ?? null;
        if ($correctMode && ($node['tracking_mode'] ?? '') !== $correctMode) {
            $oldMode = $node['tracking_mode'] ?? 'unset';
            $node['tracking_mode'] = $correctMode;
            $changed = true;
            $modeFixed++;
            $this->line("  Fixed mode: {$label} {$oldMode} → {$correctMode}");
        }

        // Link non-category nodes that don't have a balance FK yet
        if ($taskType !== 'category' && !isset($node['todo_balance_id'])) {
            $balanceId = $labelMap[$label] ?? $labelMap[$stripped] ?? null;

            if ($balanceId) {
                $node['todo_balance_id'] = $balanceId;
                $changed = true;
                $linked++;
                $this->line("  Linked: {$label} → balance #{$balanceId}");
            } else {
                $this->warn("  No balance found for: {$label}");
            }
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->linkNode($child, $labelMap, $modeMap, $changed, $linked, $modeFixed);
            }
        }
    }
}
