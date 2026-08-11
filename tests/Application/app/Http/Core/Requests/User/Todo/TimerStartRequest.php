<?php

declare(strict_types=1);

namespace App\Http\Core\Requests\User\Todo;

use App\Models\User\TimeEntry;
use App\Policies\User\TimeEntryPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

class TimerStartRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    protected function getPolicyAction(): string
    {
        return TimeEntryPolicy::ACTION_CREATE;
    }

    protected function getPolicyModel(): string
    {
        return TimeEntry::class;
    }

    protected function getPolicyParameters(): array
    {
        return [$this->route('user')];
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'component_id' => ['integer'],
            'item_id' => ['string'],
            'started_at' => ['required', 'date'],
            'budget_hours' => ['numeric'],
            'session_budget_hours' => ['numeric', 'nullable'],
            'todo_balance_id' => ['integer', 'nullable'],
            'session_elapsed_seconds' => ['integer', 'min:0'],
        ];
    }
}
