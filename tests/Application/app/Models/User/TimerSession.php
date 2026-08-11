<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Polis\Models\BaseModelAbstract;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $component_id
 * @property string|null $item_id
 * @property string $label
 * @property int $session_budget_seconds
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TimerSession extends BaseModelAbstract
{
    use SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';

    protected $casts = [
        'component_id' => 'integer',
        'session_budget_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Total elapsed seconds across all completed (stopped) entries in this session.
     */
    public function totalElapsedSeconds(): int
    {
        return (int) $this->timeEntries()
            ->whereNotNull('stopped_at')
            ->sum('duration_seconds');
    }

    /**
     * Get session data for API response.
     */
    public function toSessionData(): array
    {
        return [
            'id' => $this->id,
            'total_elapsed_seconds' => $this->totalElapsedSeconds(),
            'session_budget_seconds' => $this->session_budget_seconds,
            'status' => $this->status,
        ];
    }
}
