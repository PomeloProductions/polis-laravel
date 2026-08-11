<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Todo\TodoTaskTreeService;
use Illuminate\Console\Command;
use Polis\Models\User\UserPageComponent;

class TodoNormalizeExistingData extends Command
{
    protected $signature = 'todo:normalize-existing {--dry-run : Show what would be migrated without making changes}';

    protected $description = 'Populate relational tables from existing config_json data for all todo_task components';

    public function __construct(
        protected TodoTaskTreeService $treeService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $components = UserPageComponent::where('component_type', 'todo_task')
            ->whereNotNull('config_json')
            ->get();

        $this->info("Found {$components->count()} todo_task components to normalize.");

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $success = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($components as $component) {
            $configJson = $component->config_json;
            if (! is_array($configJson) || empty($configJson['root'])) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $label = $configJson['root']['label'] ?? '(no label)';
                $this->line("  Would normalize component #{$component->id} (page #{$component->user_page_id}): {$label}");
                $success++;
                continue;
            }

            try {
                $this->treeService->syncFromJson($component, $configJson);
                $success++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  Failed component #{$component->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Normalized: {$success}, Skipped: {$skipped}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
