<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Todo\TodoTaskTreeService;
use Polis\Models\User\UserPageComponent;

class UserPageComponentObserver
{
    public function __construct(
        protected TodoTaskTreeService $treeService,
    ) {}

    /**
     * When a new todo_task component is created with config_json (e.g. from templates or add-component),
     * populate the relational tables from the JSON.
     */
    public function created(UserPageComponent $component): void
    {
        if ($component->component_type !== 'todo_task') {
            return;
        }

        $configJson = $component->config_json;
        if (! is_array($configJson) || empty($configJson['root'])) {
            return;
        }

        $this->treeService->syncFromJson($component, $configJson);
    }
}
