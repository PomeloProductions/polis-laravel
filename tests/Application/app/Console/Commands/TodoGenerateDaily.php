<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Services\Todo\TodoGenerationService;

class TodoGenerateDaily extends Command
{
    protected $signature = 'todo:generate-daily';

    protected $description = 'Generate today\'s todo pages for all users with a todo root page';

    public function __construct(
        protected TodoGenerationService $generationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $count = 0;

        User::chunk(100, function ($users) use ($today, &$count) {
            foreach ($users as $user) {
                $rootPage = $this->generationService->findRootTodoPage($user);
                if (! $rootPage) {
                    continue;
                }

                try {
                    $this->generationService->ensureCurrentPeriods($user, $today);
                    $count++;
                } catch (\Throwable $e) {
                    $this->error("Failed to generate for user {$user->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Generated todo pages for {$count} users.");

        return self::SUCCESS;
    }
}
