<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\CheckOff;
use App\Models\User\TodoBalance;
use App\Models\User\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Polis\Models\User\UserPage;

class TodoBackfillCheckOffs extends Command
{
    protected $signature = 'todo:backfill-check-offs {--dry-run : Show what would be created without saving}';

    protected $description = 'Backfill check_offs from page snapshot diffs';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $user = User::first();

        // Load all balances for units-mode items (rotating tasks)
        $balances = TodoBalance::where('user_id', $user->id)
            ->where('tracking_mode', 'units')
            ->get()
            ->keyBy('item_key');

        // Load all day pages ordered chronologically
        $pages = UserPage::where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->filter(fn ($p) => ($p->config_json['todo_level'] ?? null) === 'day');

        // Extract rotating node snapshots per page
        $snapshots = [];
        foreach ($pages as $page) {
            $date = $page->config_json['todo_date'] ?? null;
            if (!$date) continue;

            foreach ($page->components as $comp) {
                $root = ($comp->config_json)['root'] ?? null;
                if (!$root) continue;

                $this->collectRotatingNodes($root, $date, $snapshots);
            }
        }

        // Group snapshots by label and sort by date
        $byLabel = [];
        foreach ($snapshots as $snap) {
            $byLabel[$snap['label']][] = $snap;
        }

        $created = 0;

        foreach ($byLabel as $label => $labelSnapshots) {
            usort($labelSnapshots, fn ($a, $b) => strcmp($a['date'], $b['date']));

            $balance = $balances[$label] ?? null;
            if (!$balance) {
                $this->warn("No balance found for rotating task: {$label}");
                continue;
            }

            $this->info("\n{$label} (balance #{$balance->id}):");

            for ($i = 0; $i < count($labelSnapshots) - 1; $i++) {
                $prev = $labelSnapshots[$i];
                $curr = $labelSnapshots[$i + 1];
                $checkOffDate = $curr['date']; // check-off happened on the day showing the result

                // Actually, the check-off happened on the PREVIOUS day (prev snapshot shows the state
                // after the user interacted with it, and curr shows copy-forward result).
                // But group counts in curr come from prev's final state (copy-forward preserves counts).
                // So we need to compare: did any group count increase between prev's START and prev's END?
                // The snapshot IS the end-of-day state. The copy-forward creates curr from prev.
                // If count_this_group in curr > count in a fresh copy of prev, that means the user
                // checked off on curr's date.
                //
                // Simpler: compare curr's group counts to prev's group counts.
                // An increase in count_this_group means a check-off happened.
                // The date of the check-off is the date of the snapshot where the count increased.

                $checkOffs = $this->detectCheckOffs($prev, $curr);

                foreach ($checkOffs as $co) {
                    $this->line("  {$prev['date']}: G{$co['group_number']}"
                        . ($co['item_label'] ? " — {$co['item_label']}" : '')
                        . ($co['item_id'] ? " (id: {$co['item_id']})" : ''));

                    if (!$dryRun) {
                        CheckOff::create([
                            'user_id' => $user->id,
                            'todo_balance_id' => $balance->id,
                            'group_number' => $co['group_number'],
                            'item_id' => $co['item_id'],
                            'item_label' => $co['item_label'],
                            'occurred_on' => $prev['date'],
                            'meta_json' => [
                                'prev_count' => $co['prev_count'],
                                'new_count' => $co['new_count'],
                            ],
                        ]);
                    }
                    $created++;
                }
            }

            // Also check the last snapshot for same-day check-offs by looking at item last_date
            $last = end($labelSnapshots);
            $lastDate = $last['date'];
            $lastMD = Carbon::parse($lastDate)->format('n-j'); // M-D format

            foreach ($last['groups'] as $group) {
                foreach ($group['items'] ?? [] as $item) {
                    $itemLastDate = $item['last_date'] ?? null;
                    if ($itemLastDate === $lastMD) {
                        // Check if we already recorded this from the diff
                        $alreadyRecorded = false;
                        // This item was checked off today — but we can only know if it wasn't
                        // already captured by the diff. We'll skip if this is the first snapshot.
                        if (count($labelSnapshots) === 1) {
                            // Only snapshot — can't diff, but last_date tells us it was done on this day
                            $this->line("  {$lastDate}: G{$group['group_number']} — {$item['text']} (from last_date, single snapshot)");
                            if (!$dryRun) {
                                CheckOff::create([
                                    'user_id' => $user->id,
                                    'todo_balance_id' => $balance->id,
                                    'group_number' => $group['group_number'],
                                    'item_id' => $item['id'] ?? null,
                                    'item_label' => $item['text'] ?? null,
                                    'occurred_on' => $lastDate,
                                ]);
                            }
                            $created++;
                        }
                    }
                }
            }
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Created {$created} check-off records.");

        return self::SUCCESS;
    }

    /**
     * Compare two consecutive snapshots and detect check-offs.
     * A check-off is detected when:
     * - A group's count_this_group increased (not from copy-forward increment)
     * - An item's last_date changed to match the prev date
     * - An item's count increased
     */
    private function detectCheckOffs(array $prev, array $curr): array
    {
        $checkOffs = [];
        $prevGroups = collect($prev['groups'])->keyBy('group_number');
        $currGroups = collect($curr['groups'])->keyBy('group_number');
        $prevDate = $prev['date'];
        $prevMD = Carbon::parse($prevDate)->format('n-j');

        // Look at the PREV snapshot for items with last_date matching prev's date
        // This means the check-off happened on prevDate
        foreach ($prev['groups'] as $group) {
            $gNum = $group['group_number'];
            $prevGroup = $prevGroups[$gNum] ?? null;

            // Check items for last_date matching prevDate
            foreach ($group['items'] ?? [] as $item) {
                $itemLastDate = $item['last_date'] ?? null;
                if ($itemLastDate === $prevMD) {
                    $checkOffs[] = [
                        'group_number' => $gNum,
                        'item_id' => $item['id'] ?? null,
                        'item_label' => $item['text'] ?? null,
                        'prev_count' => null,
                        'new_count' => $item['count'] ?? null,
                    ];
                }
            }

            // Also check mark_done_on_group groups with last_date
            if (!empty($group['mark_done_on_group']) && ($group['last_date'] ?? null) === $prevMD) {
                // Only add if no items were already detected for this group
                $alreadyHasItem = collect($checkOffs)->contains('group_number', $gNum);
                if (!$alreadyHasItem) {
                    $checkOffs[] = [
                        'group_number' => $gNum,
                        'item_id' => null,
                        'item_label' => $group['label'] ?? null,
                        'prev_count' => null,
                        'new_count' => $group['count_this_group'] ?? null,
                    ];
                }
            }

            // Check sub_groups
            foreach ($group['sub_groups'] ?? [] as $subGroup) {
                foreach ($subGroup['items'] ?? [] as $subItem) {
                    $subItemLastDate = $subItem['last_date'] ?? null;
                    if ($subItemLastDate === $prevMD) {
                        $checkOffs[] = [
                            'group_number' => $gNum,
                            'item_id' => $subItem['id'] ?? null,
                            'item_label' => $subItem['text'] ?? null,
                            'prev_count' => null,
                            'new_count' => $subItem['count'] ?? null,
                        ];
                    }
                }

                if (!empty($subGroup['mark_done_on_group']) && ($subGroup['last_date'] ?? null) === $prevMD) {
                    $checkOffs[] = [
                        'group_number' => $gNum,
                        'item_id' => null,
                        'item_label' => ($group['label'] ?? '') . ' > ' . ($subGroup['label'] ?? ''),
                        'prev_count' => null,
                        'new_count' => $subGroup['count_this_group'] ?? null,
                    ];
                }
            }
        }

        return $checkOffs;
    }

    private function collectRotatingNodes(array $node, string $date, array &$snapshots): void
    {
        if (($node['task_type'] ?? '') === 'rotating') {
            $snapshots[] = [
                'label' => $node['label'] ?? '',
                'date' => $date,
                'tally' => $node['tally'] ?? 0,
                'groups' => $node['groups'] ?? [],
            ];
        }

        foreach ($node['children'] ?? [] as $child) {
            $this->collectRotatingNodes($child, $date, $snapshots);
        }
    }
}
