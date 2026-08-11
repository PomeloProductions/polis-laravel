<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\TodoBalance;
use App\Models\User\TodoBalanceLog;
use App\Models\User\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TodoSeedBalances extends Command
{
    protected $signature = 'todo:seed-balances {--email=bryce13950@gmail.com}';

    protected $description = 'Seed balance records from Notion import starting values (March 28, 2026)';

    public function handle(): int
    {
        $user = User::where('email', $this->option('email'))->first();
        if (!$user) {
            $this->error('User not found');
            return self::FAILURE;
        }

        // Clear existing balance data for clean re-seed
        TodoBalanceLog::where('user_id', $user->id)->forceDelete();
        TodoBalance::where('user_id', $user->id)->forceDelete();

        $this->info('Seeding balances for user ' . $user->id);

        // Notion March 28, 2026 starting values
        // Hours-mode: balance = hour deficit (positive = behind, negative = surplus)
        // Units-mode: balance = unit count owed
        $seeds = [
            // Work Hours (hours mode)
            ['item_key' => 'TransformerLens Coding', 'tracking_mode' => 'hours', 'balance' => -83.2, 'time_budget_hours' => null, 'tally_step' => 0, 'schedule' => null],
            ['item_key' => 'Poseidon Research Coding', 'tracking_mode' => 'hours', 'balance' => -104.5, 'time_budget_hours' => null, 'tally_step' => 6, 'schedule' => [1,2,3,4,5]],
            ['item_key' => 'Lingwave', 'tracking_mode' => 'hours', 'balance' => -1.1, 'time_budget_hours' => null, 'tally_step' => 0, 'schedule' => null],

            // Life Admin (hours mode, everyday)
            ['item_key' => 'Work Out', 'tracking_mode' => 'hours', 'balance' => 0, 'time_budget_hours' => 0.25, 'tally_step' => 0.25, 'schedule' => [0,1,2,3,4,5,6]],
            ['item_key' => 'Chores', 'tracking_mode' => 'hours', 'balance' => 0.5, 'time_budget_hours' => 0.5, 'tally_step' => 0.5, 'schedule' => [0,1,2,3,4,5,6]],
            ['item_key' => 'Update Finances', 'tracking_mode' => 'hours', 'balance' => 7.75, 'time_budget_hours' => 0.25, 'tally_step' => 0.25, 'schedule' => [0,1,2,3,4,5,6]],

            // Work Admin (hours mode, weekdays)
            ['item_key' => 'Manage Stock', 'tracking_mode' => 'hours', 'balance' => 0, 'time_budget_hours' => 0.25, 'tally_step' => 0.25, 'schedule' => [1,2,3,4,5]],
            ['item_key' => 'Pomelo Productions Management', 'tracking_mode' => 'hours', 'balance' => 5.0, 'time_budget_hours' => 0.25, 'tally_step' => 0.25, 'schedule' => [1,2,3,4,5]],
            ['item_key' => 'Keeping Up', 'tracking_mode' => 'hours', 'balance' => 6.0, 'time_budget_hours' => 0.5, 'tally_step' => 0.5, 'schedule' => [1,2,3,4,5]],
            ['item_key' => 'TransformerLens Community Management', 'tracking_mode' => 'hours', 'balance' => 19.0, 'time_budget_hours' => 0.5, 'tally_step' => 0.5, 'schedule' => [1,2,3,4,5]],

            // Active Hobbies (units mode)
            ['item_key' => 'Language Study', 'tracking_mode' => 'units', 'balance' => 13, 'time_budget_hours' => 0.5, 'tally_step' => 1, 'schedule' => [0,1,2,3,4,5,6]],
            ['item_key' => 'Read', 'tracking_mode' => 'units', 'balance' => 66, 'time_budget_hours' => 0.25, 'tally_step' => 1, 'schedule' => [0,1,2,3,4,5,6]],
            ['item_key' => 'Write', 'tracking_mode' => 'units', 'balance' => 96, 'time_budget_hours' => 0.25, 'tally_step' => 1, 'schedule' => [0,1,2,3,4,5,6]],
            ['item_key' => 'Game', 'tracking_mode' => 'units', 'balance' => 1, 'time_budget_hours' => null, 'tally_step' => 1, 'schedule' => [0,1,2,3,4,5,6]],

            // Passive Hobbies (units mode)
            ['item_key' => 'Watch Serials', 'tracking_mode' => 'units', 'balance' => 29.2, 'time_budget_hours' => null, 'tally_step' => 2, 'schedule' => [0,1,2,3,4,5,6]],
            ['item_key' => 'Watch a Movie', 'tracking_mode' => 'units', 'balance' => 14, 'time_budget_hours' => null, 'tally_step' => 1, 'schedule' => [0,1,2,3,4,5,6]],
            ['item_key' => 'Listen to a Composition', 'tracking_mode' => 'units', 'balance' => 1, 'time_budget_hours' => null, 'tally_step' => 1, 'schedule' => [0,1,2,3,4,5,6]],
        ];

        $seedDate = Carbon::parse('2026-03-28');

        foreach ($seeds as $seed) {
            $balance = TodoBalance::create([
                'user_id' => $user->id,
                'item_key' => $seed['item_key'],
                'tracking_mode' => $seed['tracking_mode'],
                'balance' => $seed['balance'],
                'time_budget_hours' => $seed['time_budget_hours'],
                'tally_step' => $seed['tally_step'],
                'schedule' => $seed['schedule'],
            ]);

            TodoBalanceLog::create([
                'user_id' => $user->id,
                'todo_balance_id' => $balance->id,
                'reason' => TodoBalanceLog::REASON_SEED,
                'delta' => $seed['balance'],
                'balance_before' => 0,
                'balance_after' => $seed['balance'],
                'occurred_on' => $seedDate,
                'meta_json' => ['source' => 'notion_import', 'import_date' => '2026-03-28'],
            ]);

            $this->info("  {$seed['item_key']} = {$seed['balance']} ({$seed['tracking_mode']})");
        }

        $this->info("\nSeeded " . count($seeds) . ' balance records with starting values from March 28, 2026.');

        return self::SUCCESS;
    }
}
