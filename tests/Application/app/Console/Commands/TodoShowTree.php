<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Console\Command;
use Polis\Models\User\UserPage;

class TodoShowTree extends Command
{
    protected $signature = 'todo:show-tree
        {--date=2026-04-09 : Date to show}
        {--label= : Only show nodes matching this label}
        {--history : Show a given node across all day pages}';

    protected $description = 'Show the node tree for a todo day page';

    public function handle(): int
    {
        $label = $this->option('label');

        if ($this->option('history') && $label) {
            return $this->showHistory($label);
        }

        if ($label && $this->option('date')) {
            $this->showItems($label, $this->option('date'));
        }

        $date = $this->option('date');
        $user = User::first();

        $page = UserPage::where('user_id', $user->id)
            ->whereJsonContains('config_json->todo_date', $date)
            ->first();

        if (!$page) {
            $this->error("No page found for date: {$date}");
            return self::FAILURE;
        }

        $components = $page->components;
        foreach ($components as $comp) {
            $config = $comp->config_json;
            $root = $config['root'] ?? null;
            if (!$root) continue;

            if ($label && ($root['label'] ?? '') !== $label) continue;

            $this->info("Component {$comp->id} ({$comp->component_type}):");
            $this->printNode($root, 0);
            $this->newLine();
        }

        return self::SUCCESS;
    }

    private function showHistory(string $label): int
    {
        $user = User::first();
        $pages = UserPage::where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->filter(fn ($p) => ($p->config_json['todo_level'] ?? null) === 'day');

        $headers = ['Date', 'Day', 'Tally', 'Group Counts', 'Details'];
        $rows = [];

        foreach ($pages as $page) {
            $date = $page->config_json['todo_date'] ?? '?';
            $dayName = \Carbon\Carbon::parse($date)->format('D');

            foreach ($page->components as $comp) {
                $root = ($comp->config_json)['root'] ?? null;
                if (!$root) continue;

                $node = $this->findNode($root, $label);
                if (!$node) continue;

                $tally = $node['tally'] ?? 'null';
                $groups = $node['groups'] ?? [];
                $counts = [];
                foreach ($groups as $g) {
                    $counts[] = 'G' . $g['group_number'] . '=' . ($g['count_this_group'] ?? 0);
                }

                $details = '';
                if (!empty($node['tracking_mode'])) $details .= $node['tracking_mode'] . ' ';
                if (isset($node['logged_time'])) $details .= 'logged=' . round($node['logged_time'], 2) . ' ';

                $rows[] = [$date, $dayName, $tally, implode(', ', $counts), trim($details)];
            }
        }

        $this->table($headers, $rows);
        return self::SUCCESS;
    }

    private function showItems(string $label, string $date): int
    {
        $user = User::first();
        $page = UserPage::where('user_id', $user->id)
            ->whereJsonContains('config_json->todo_date', $date)
            ->first();

        if (!$page) {
            $this->error("No page for {$date}");
            return self::FAILURE;
        }

        foreach ($page->components as $comp) {
            $root = ($comp->config_json)['root'] ?? null;
            if (!$root) continue;

            $node = $this->findNode($root, $label);
            if (!$node) continue;

            foreach ($node['groups'] ?? [] as $g) {
                $this->info("G{$g['group_number']} ({$g['label']}) count={$g['count_this_group']}");
                foreach ($g['items'] ?? [] as $item) {
                    $this->line("  {$item['id']}: {$item['text']}  last_date=" . ($item['last_date'] ?? 'none') . "  count=" . ($item['count'] ?? 0));
                }
            }
        }

        return self::SUCCESS;
    }

    private function findNode(array $node, string $label): ?array
    {
        if (($node['label'] ?? '') === $label) return $node;
        foreach ($node['children'] ?? [] as $child) {
            $found = $this->findNode($child, $label);
            if ($found) return $found;
        }
        return null;
    }

    private function printNode(array $node, int $depth): void
    {
        $indent = str_repeat('  ', $depth);
        $label = $node['label'] ?? '?';
        $mode = $node['tracking_mode'] ?? 'units';
        $tally = $node['tally'] ?? 'null';
        $type = $node['task_type'] ?? '?';

        $balanceId = $node['todo_balance_id'] ?? null;
        $balancePart = $balanceId ? " balance_id={$balanceId}" : '';
        $decrementOnDone = $node['decrement_on_done'] ?? null;
        $decrementPart = $decrementOnDone === false ? ' decrement=OFF' : '';
        $extra = "[{$type}, {$mode}] tally={$tally}{$balancePart}{$decrementPart}";
        $this->line("{$indent}{$label} {$extra}");

        foreach ($node['children'] ?? [] as $child) {
            $this->printNode($child, $depth + 1);
        }
    }
}
