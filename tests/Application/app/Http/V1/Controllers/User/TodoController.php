<?php

declare(strict_types=1);

namespace App\Http\V1\Controllers\User;

use App\Contracts\Repositories\User\TimeEntryRepositoryContract;
use App\Contracts\Repositories\User\TodoSettingRepositoryContract;
use App\Contracts\Repositories\User\TodoTemplateRepositoryContract;
use App\Http\Core\Requests\User\Todo;
use App\Http\Core\Requests\User\TodoTemplate;
use App\Models\User\TimeEntry;
use App\Models\User\TodoBalance;
use App\Models\User\TimerSession;
use App\Models\User\TodoCalendar;
use App\Models\User\TodoBalanceLog;
use App\Models\User\TodoRotatingGroup;
use App\Models\User\TodoRotatingItem;
use App\Models\User\TodoSubItem;
use App\Models\User\TodoTaskNode;
use App\Models\User\TodoTemplate as TodoTemplateModel;
use App\Models\User\TodoVacationPeriod;
use App\Models\User\User;
use App\Services\Todo\TodoGenerationService;
use App\Services\Todo\TodoTaskTreeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Models\BaseModelAbstract;

class TodoController extends BaseControllerAbstract
{
    use HasIndexRequests;

    public function __construct(
        protected TodoGenerationService $generationService,
        protected TodoTaskTreeService $treeService,
        protected TimeEntryRepositoryContract $timeEntryRepository,
        protected TodoSettingRepositoryContract $settingRepository,
        protected TodoTemplateRepositoryContract $templateRepository,
    ) {}

    public function today(Todo\ViewRequest $request, User $user): JsonResponse
    {
        // Use the user's timezone to determine "today"
        $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
        $timezone = $settings->timezone ?? 'UTC';
        $today = Carbon::now($timezone)->startOfDay();

        $dayPage = $this->generationService->ensureCurrentPeriods($user, $today);

        return $this->pageWithBalances($dayPage, $user);
    }

    public function resolve(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $slug = $request->input('slug', '');
        $page = $this->generationService->findPageBySlug($user, $slug);

        if (! $page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        return $this->pageWithBalances($page, $user);
    }

    public function navigate(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $level = $request->input('level', 'day');
        $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
        $timezone = $settings->timezone ?? 'UTC';
        $defaultDate = Carbon::now($timezone)->toDateString();
        $date = Carbon::parse($request->input('date', $defaultDate));

        // For day-level navigation, auto-generate if the page doesn't exist yet
        if ($level === 'day') {
            $page = $this->findPageByDateConfig($user, 'day', 'todo_date', $date->toDateString());
            if (! $page) {
                $page = $this->generationService->ensureCurrentPeriods($user, $date);
            }

            return $this->pageWithBalances($page, $user);
        }

        if ($level === 'week') {
            $weekStart = $this->generationService->getWeekStart($user, $date);
            $page = $this->findPageByDateConfig($user, 'week', 'todo_week_start', $weekStart->toDateString());
        } elseif ($level === 'month') {
            $page = $this->findPageByMonthYear($user, $date->month, $date->year);
        } elseif ($level === 'year') {
            $page = $this->findPageByYear($user, $date->year);
        } else {
            $page = null;
        }

        if (! $page) {
            return response()->json(['message' => 'No page found for the given level and date.'], 404);
        }

        return $this->pageWithBalances($page, $user);
    }

    public function hierarchy(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $year = (int) $request->input('year', Carbon::today()->year);

        $rootPage = $this->generationService->findRootTodoPage($user);
        if (! $rootPage) {
            return response()->json(['message' => 'No todo root page found.'], 404);
        }

        $allPages = $rootPage->childPages()->with('childPages.childPages.childPages')->get();

        $yearPage = $allPages->first(function ($page) use ($year) {
            return ($page->config_json['todo_year'] ?? null) === $year;
        });

        if (! $yearPage) {
            return response()->json(['year' => $year, 'months' => []]);
        }

        $tree = $this->buildHierarchyTree($yearPage);

        return response()->json($tree);
    }

    public function generate(Todo\GenerateRequest $request, User $user): JsonResponse
    {
        $throughDate = Carbon::parse($request->input('through_date'));
        $current = Carbon::today()->copy();

        $pages = [];
        while ($current->lte($throughDate)) {
            $pages[] = $this->generationService->ensureCurrentPeriods($user, $current);
            $current->addDay();
        }

        return response()->json([
            'generated_count' => count($pages),
            'through_date' => $throughDate->toDateString(),
        ]);
    }

    public function settings(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $setting = $this->settingRepository->findAll([
            ['user_id', '=', $user->id],
        ])->first();

        if (! $setting) {
            $setting = $this->settingRepository->create([
                'user_id' => $user->id,
                'week_start_day' => 0,
            ]);
        }

        return response()->json($setting);
    }

    public function updateSettings(Todo\UpdateSettingsRequest $request, User $user): JsonResponse
    {
        $setting = $this->settingRepository->findAll([
            ['user_id', '=', $user->id],
        ])->first();

        if (! $setting) {
            $setting = $this->settingRepository->create(array_merge(
                $request->json()->all(),
                ['user_id' => $user->id]
            ));
        } else {
            $setting = $this->settingRepository->update($setting, $request->json()->all());
        }

        return response()->json($setting);
    }

    public function timerShow(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $running = TimeEntry::where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->first();

        if (! $running) {
            return response()->json(null);
        }

        $sessionData = null;
        if ($running->timer_session_id) {
            $session = TimerSession::find($running->timer_session_id);
            if ($session) {
                $sessionData = $session->toSessionData();
            }
        }

        // For hours-mode tasks, include the current balance so the frontend can rebuild the
        // balance progress bar after a reload (balanceHours isn't persisted on the entry).
        $balance = null;
        if ($running->todo_balance_id) {
            $bal = TodoBalance::find($running->todo_balance_id);
            if ($bal && ($bal->tracking_mode ?? 'units') === 'hours') {
                $balance = (float) $bal->balance;
            }
        }

        return response()->json([
            'entry' => $running,
            'session' => $sessionData,
            'balance' => $balance,
        ]);
    }

    public function timerStart(Todo\TimerStartRequest $request, User $user): JsonResponse
    {
        // Stop any existing running entry first
        $existing = TimeEntry::where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->get();

        foreach ($existing as $entry) {
            $now = Carbon::now();
            $started = Carbon::parse($entry->started_at);
            $durationSeconds = (int) abs($now->diffInSeconds($started));

            if ($durationSeconds < 2) {
                $entry->forceDelete();
                continue;
            }

            // Guarded transition: only the request that actually flips running->stopped may log
            // the time. A concurrent stop (timerStop racing this auto-stop) would otherwise
            // double-count the entry into the balance.
            $stopped = TimeEntry::where('id', $entry->id)
                ->whereNull('stopped_at')
                ->update([
                    'stopped_at' => $now,
                    'duration_seconds' => $durationSeconds,
                ]);
            if (! $stopped) {
                continue;
            }
            $entry->refresh();
            $hours = $durationSeconds / 3600;
            $this->logBalanceChange($user, $entry->label, $hours, TodoBalanceLog::REASON_TIMER_LOGGED, $entry, $this->userLocalDate($user, $entry->started_at));
            $this->syncLoggedHoursForEntry($user, $entry->label, $entry->started_at, $hours);
        }

        $requestData = $request->json()->all();
        $componentId = $requestData['component_id'] ?? null;
        $itemId = $requestData['item_id'] ?? null;

        // Find or create an active session for this task.
        // Look up by item_id only — component_id is per-day-page and changes on day rollover,
        // but the same task continues across days until mark-done.
        $session = null;
        if ($itemId) {
            $session = TimerSession::where('user_id', $user->id)
                ->where('item_id', $itemId)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->first();
            // Update component_id to the latest day's component for accurate session-completion lookups
            if ($session && $componentId && $session->component_id !== $componentId) {
                $session->update(['component_id' => $componentId]);
            }
        }
        if (! $session) {
            $session = TimerSession::create([
                'user_id' => $user->id,
                'component_id' => $componentId,
                'item_id' => $itemId,
                'label' => $requestData['label'] ?? '',
                'session_budget_seconds' => (int) (($requestData['session_budget_hours'] ?? 0) * 3600),
                'status' => TimerSession::STATUS_ACTIVE,
            ]);
        }

        // Create a new time entry linked to the session
        $entry = $this->timeEntryRepository->create(array_merge(
            $requestData,
            [
                'user_id' => $user->id,
                'timer_session_id' => $session->id,
                'stopped_at' => null,
                'duration_seconds' => 0,
            ]
        ));

        return response()->json([
            'entry' => $entry,
            'session' => $session->toSessionData(),
        ], 201);
    }

    public function timerUpdate(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $running = TimeEntry::where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->first();

        if (! $running) {
            return response()->json(null, 204);
        }

        $updates = [];
        $startedAt = $request->input('started_at');
        if ($startedAt) {
            $updates['started_at'] = Carbon::parse($startedAt);
        }

        $label = $request->input('label');
        $componentId = $request->input('component_id');
        $itemId = $request->input('item_id');
        $taskChanged = false;
        if ($label !== null && $label !== $running->label) {
            $updates['label'] = $label;
            $taskChanged = true;
        }
        if ($componentId !== null && (int) $componentId !== (int) $running->component_id) {
            $updates['component_id'] = $componentId;
            $taskChanged = true;
        }
        if ($itemId !== null && $itemId !== $running->item_id) {
            $updates['item_id'] = $itemId;
            $taskChanged = true;
        }

        // If task changed, link to a (possibly new) active session for the new task.
        // Look up by item_id only — component_id is per-day-page and changes on day rollover.
        if ($taskChanged) {
            $newComponentId = $componentId ?? $running->component_id;
            $newItemId = $itemId ?? $running->item_id;
            $newLabel = $label ?? $running->label;
            $session = TimerSession::where('user_id', $user->id)
                ->where('item_id', $newItemId)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->first();
            if ($session && $session->component_id !== $newComponentId) {
                $session->update(['component_id' => $newComponentId]);
            }
            if (! $session) {
                $session = TimerSession::create([
                    'user_id' => $user->id,
                    'component_id' => $newComponentId,
                    'item_id' => $newItemId,
                    'label' => $newLabel,
                    'session_budget_seconds' => $running->session_budget_hours
                        ? (int) ((float) $running->session_budget_hours * 3600)
                        : 0,
                    'status' => TimerSession::STATUS_ACTIVE,
                ]);
            }
            $updates['timer_session_id'] = $session->id;
        }

        if (! empty($updates)) {
            $running->update($updates);
        }

        $sessionData = null;
        if ($running->timer_session_id) {
            $session = TimerSession::find($running->timer_session_id);
            if ($session) {
                $sessionData = $session->toSessionData();
            }
        }

        return response()->json([
            'entry' => $running->fresh(),
            'session' => $sessionData,
        ]);
    }

    public function timerStop(Todo\ViewRequest $request, User $user): JsonResponse
    {
        // The client identifies WHICH entry it means to stop. A stop-then-switch fired two
        // requests, and when the new task's start was processed first, an untargeted stop
        // zeroed out the freshly-created entry (observed live: a 6-minute entry stopped at
        // ~0s, below the balance-logging threshold, so its time silently vanished). An
        // untargeted stop is kept as fallback for stale clients.
        $entryId = (int) $request->input('entry_id', 0);
        $itemId = $request->input('item_id');

        $query = TimeEntry::where('user_id', $user->id)
            ->whereNull('stopped_at');
        if ($entryId > 0) {
            $query->where('id', $entryId);
        } elseif ($itemId) {
            $query->where('item_id', $itemId);
        }
        $running = $query->first();

        if (! $running) {
            return response()->json(null, 204);
        }

        $now = Carbon::now();
        $started = Carbon::parse($running->started_at);
        $durationSeconds = (int) abs($now->diffInSeconds($started));

        // Guarded transition (see timerStart): if another request stopped it in the meantime,
        // that request logged the time — bail without double-counting.
        $stopped = TimeEntry::where('id', $running->id)
            ->whereNull('stopped_at')
            ->update([
                'stopped_at' => $now,
                'duration_seconds' => $durationSeconds,
            ]);
        if (! $stopped) {
            return response()->json(null, 204);
        }
        $running->refresh();

        $hours = $durationSeconds / 3600;
        $this->logBalanceChange($user, $running->label, $hours, TodoBalanceLog::REASON_TIMER_LOGGED, $running, $this->userLocalDate($user, $running->started_at));
        $this->syncLoggedHoursForEntry($user, $running->label, $running->started_at, $hours);

        // Return session data so frontend knows accumulated session time
        $sessionData = null;
        if ($running->timer_session_id) {
            $session = TimerSession::find($running->timer_session_id);
            if ($session) {
                $sessionData = $session->toSessionData();
            }
        }

        return response()->json([
            'entry' => $running->fresh(),
            'session' => $sessionData,
        ]);
    }

    public function timeEntryIndex(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $query = TimeEntry::where('user_id', $user->id)
            ->whereNotNull('stopped_at')
            ->orderBy('started_at', 'desc');

        if ($from) {
            $query->where('started_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to) {
            $query->where('started_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $limit = min((int) ($request->input('limit', 200)), 5000);
        $entries = $query->limit($limit)->get();

        return response()->json(['data' => $entries]);
    }

    public function timeEntryStore(Todo\TimeEntryStoreRequest $request, User $user): JsonResponse
    {
        $entry = $this->timeEntryRepository->create(array_merge(
            $request->json()->all(),
            ['user_id' => $user->id]
        ));

        // Only sync logged hours for completed entries
        if ($entry->stopped_at && $entry->duration_seconds > 0) {
            $hours = $entry->duration_seconds / 3600;
            $this->logBalanceChange($user, $entry->label, $hours, TodoBalanceLog::REASON_TIMER_CREATED, $entry, $this->userLocalDate($user, $entry->started_at));
            $this->syncLoggedHoursForEntry($user, $entry->label, $entry->started_at, $hours);
        }

        return response()->json($entry, 201);
    }

    public function timeEntryUpdate(Todo\TimeEntryStoreRequest $request, User $user, TimeEntry $timeEntry): JsonResponse
    {
        if ($timeEntry->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $oldHours = $timeEntry->duration_seconds / 3600;
        $oldLabel = $timeEntry->label;
        $oldDate = $timeEntry->started_at;

        $timeEntry->update($request->json()->all());
        $timeEntry->refresh();

        // Subtract old hours, add new hours
        if ($oldLabel === $timeEntry->label) {
            $diff = ($timeEntry->duration_seconds / 3600) - $oldHours;
            if (abs($diff) > 0.0001) {
                $this->logBalanceChange($user, $timeEntry->label, $diff, TodoBalanceLog::REASON_TIMER_UPDATED, $timeEntry, $this->userLocalDate($user, $timeEntry->started_at));
                $this->syncLoggedHoursForEntry($user, $timeEntry->label, $timeEntry->started_at, $diff);
            }
        } else {
            // Label changed — subtract from old, add to new
            $this->logBalanceChange($user, $oldLabel, -$oldHours, TodoBalanceLog::REASON_TIMER_UPDATED, $timeEntry, $this->userLocalDate($user, $oldDate));
            $this->syncLoggedHoursForEntry($user, $oldLabel, $oldDate, -$oldHours);
            $newHours = $timeEntry->duration_seconds / 3600;
            $this->logBalanceChange($user, $timeEntry->label, $newHours, TodoBalanceLog::REASON_TIMER_UPDATED, $timeEntry, $this->userLocalDate($user, $timeEntry->started_at));
            $this->syncLoggedHoursForEntry($user, $timeEntry->label, $timeEntry->started_at, $newHours);
        }

        return response()->json($timeEntry);
    }

    public function timeEntryDestroy(Todo\ViewRequest $request, User $user, TimeEntry $timeEntry): JsonResponse
    {
        if ($timeEntry->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $hours = $timeEntry->duration_seconds / 3600;
        $this->logBalanceChange($user, $timeEntry->label, -$hours, TodoBalanceLog::REASON_TIMER_DELETED, $timeEntry, $this->userLocalDate($user, $timeEntry->started_at));
        $this->syncLoggedHoursForEntry($user, $timeEntry->label, $timeEntry->started_at, -$hours);

        $timeEntry->delete();

        return response()->json(null, 204);
    }

    /**
     * Sync logged hours delta to the matching task node for the day the entry falls on.
     */
    /**
     * Return the user's local date (YYYY-MM-DD) for a given UTC timestamp.
     */
    protected function userLocalDate(User $user, $timestamp): string
    {
        $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
        $timezone = $settings->timezone ?? 'UTC';
        return Carbon::parse($timestamp)->setTimezone($timezone)->toDateString();
    }

    protected function syncLoggedHoursForEntry(User $user, string $label, $entryDate, float $hoursDelta): void
    {
        if (abs($hoursDelta) < 0.0001) {
            return;
        }

        $dayPage = $this->generationService->findPageByConfig($user, 'day', [
            'todo_date' => $this->userLocalDate($user, $entryDate),
        ]);

        if (! $dayPage) {
            return;
        }

        $dayPage->load('components');

        // Find the matching task node by label across all todo_task components on this day page
        $node = $this->findNodeByLabel($dayPage, $label);

        if (! $node) {
            return;
        }

        $key = $node->task_type === TodoTaskNode::TASK_TYPE_ROTATING ? 'logged_time' : 'logged_hours';
        $updates = [
            $key => max(0, (float) $node->$key + $hoursDelta),
        ];

        $trackingMode = $node->tracking_mode ?? 'units';
        if ($trackingMode === 'hours') {
            // For hours-mode: sync tally from the authoritative balance (which is updated by logBalanceChange)
            if ($node->todo_balance_id) {
                $balance = TodoBalance::find($node->todo_balance_id);
                if ($balance) {
                    $updates['tally'] = (float) $balance->balance;
                }
            } else {
                $updates['tally'] = ($node->tally ?? 0) - $hoursDelta;
            }
        } elseif ($node->deficit !== null && (float) $node->deficit !== 0.0 || $node->time_budget_hours !== null) {
            $updates['deficit'] = (float) $node->deficit - $hoursDelta;
        }

        $node->updateQuietly($updates);
    }

    /**
     * Find a TodoTaskNode by label, supporting "Parent — Child" format.
     */
    protected function findNodeByLabel($dayPage, string $label): ?TodoTaskNode
    {
        $componentIds = $dayPage->components
            ->filter(fn ($c) => in_array($c->component_type, ['todo_task', 'todo']))
            ->pluck('id');

        if ($componentIds->isEmpty()) {
            return null;
        }

        // Try direct label match first
        $node = TodoTaskNode::whereIn('user_page_component_id', $componentIds)
            ->where('label', $label)
            ->first();

        if ($node) {
            return $node;
        }

        // Try "Parent — Child" format
        if (str_contains($label, ' — ')) {
            $parts = explode(' — ', $label);
            $childLabel = end($parts);
            $parentLabel = $parts[count($parts) - 2] ?? '';

            $node = TodoTaskNode::whereIn('user_page_component_id', $componentIds)
                ->where('label', $childLabel)
                ->whereHas('parent', fn ($q) => $q->where('label', $parentLabel))
                ->first();

            if ($node) {
                return $node;
            }

            // Fallback: match just the child label
            $node = TodoTaskNode::whereIn('user_page_component_id', $componentIds)
                ->where('label', $childLabel)
                ->first();
        }

        return $node;
    }

    public function templateIndex(TodoTemplate\IndexRequest $request, User $user): JsonResponse
    {
        $templates = $this->templateRepository->findAll([
            ['user_id', '=', $user->id],
        ]);

        return response()->json(['data' => $templates]);
    }

    public function templateStore(TodoTemplate\StoreRequest $request, User $user): JsonResponse
    {
        $data = $request->json()->all();
        $data['user_id'] = $user->id;

        $template = $this->templateRepository->create($data);

        return response()->json($template, 201);
    }

    public function templateUpdate(TodoTemplate\UpdateRequest $request, User $user, TodoTemplateModel $template): BaseModelAbstract
    {
        return $this->templateRepository->update($template, $request->json()->all());
    }

    public function templateDestroy(TodoTemplate\DeleteRequest $request, User $user, TodoTemplateModel $template): JsonResponse
    {
        $this->templateRepository->delete($template);

        return response()->json(null, 204);
    }

    public function balanceIndex(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $balances = TodoBalance::where('user_id', $user->id)->get();

        return response()->json(['data' => $balances]);
    }

    /**
     * PATCH a single todo task node directly in the relational tables.
     * Accepts any partial node fields + nested groups/sub_items.
     */
    public function patchNode(Todo\ViewRequest $request, User $user, string $clientId): JsonResponse
    {
        $componentId = $request->input('component_id');

        $query = TodoTaskNode::where('client_id', $clientId)
            ->whereHas('component.page', fn ($q) => $q->where('user_id', $user->id));

        if ($componentId) {
            $query->where('user_page_component_id', $componentId);
        } else {
            // Fall back to most recent (highest ID)
            $query->orderByDesc('id');
        }

        $node = $query->firstOrFail();

        $data = $request->json()->all();

        // Handle cross-component move
        if (isset($data['_move']) && is_array($data['_move'])) {
            $move = $data['_move'];
            $result = $this->treeService->moveNode(
                $node->client_id,
                $node->user_page_component_id,
                $move['target_component_id'] ?? null,
                $move['target_parent_client_id'] ?? null,
                $move['target_sort_order'] ?? 0,
                $node->component->user_page_id,
                $user->id,
            );

            $targetComp = $result['target'];
            $tree = $this->treeService->buildTree($targetComp);
            return response()->json($tree);
        }

        // Track tally change for balance logging
        $oldTally = (float) $node->tally;

        // Scalar node fields
        $scalarFields = [
            'label', 'description', 'collapsed', 'tally', 'tally_step', 'schedule',
            'on_copy', 'time_budget_hours', 'logged_hours', 'logged_time', 'deficit',
            'tracking_mode', 'decrement_on_done', 'time_tracking_mode', 'completed',
            'last_date', 'custom_groups', 'cascade_ratio', 'task_type', 'show_checkmark',
            'count_this_group',
        ];

        $nodeUpdates = [];
        $isManualEdit = ! empty($data['_manual_balance_edit']);
        $trackingMode = $node->tracking_mode ?? 'units';

        foreach ($scalarFields as $field) {
            if (array_key_exists($field, $data)) {
                // logged_hours/logged_time are maintained authoritatively by the timer flow
                // (syncLoggedHoursForEntry adds each entry's elapsed on stop). Applying them from a
                // PATCH body too double-counts — the timer's onStop sends the same delta, so the
                // value inflated (this is why units/rotating logged_time drifted far above the real
                // entries). Never apply these from a PATCH unless it's an explicit manual edit.
                if (! $isManualEdit && in_array($field, ['logged_hours', 'logged_time'])) {
                    continue;
                }
                // Hours-mode tally is derived from the balance; skip non-manual tally patches.
                if ($trackingMode === 'hours' && ! $isManualEdit && $field === 'tally') {
                    continue;
                }
                $nodeUpdates[$field] = $data[$field];
            }
        }

        if (! empty($nodeUpdates)) {
            $node->update($nodeUpdates);
        }

        // Sync node.tally_step → balance.tally_step (so cron uses the right per-day budget).
        if (array_key_exists('tally_step', $data) && $node->todo_balance_id) {
            $balance = TodoBalance::find($node->todo_balance_id);
            if ($balance) {
                $oldStep = (float) $balance->tally_step;
                $newStep = (float) $data['tally_step'];
                $balance->updateQuietly(['tally_step' => $newStep]);

                // When the user chooses to apply the new daily allotment starting TODAY, and
                // today's increment was already accrued at the old rate, add a manual adjustment
                // for the difference so today reflects the new rate. If today's increment hasn't
                // run yet (weekend, vacation, or the cron simply hasn't fired), there's nothing to
                // correct — the daily job will accrue the new rate directly. "Tomorrow" is the
                // default: just changing tally_step already takes effect on the next increment.
                if (! empty($data['_allotment_change_today'])) {
                    $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
                    $timezone = $settings->timezone ?? 'UTC';
                    $today = Carbon::now($timezone)->toDateString();

                    $accruedToday = TodoBalanceLog::where('todo_balance_id', $balance->id)
                        ->where('reason', TodoBalanceLog::REASON_DAILY_INCREMENT)
                        ->where('occurred_on', $today)
                        ->exists();
                    $delta = round($newStep - $oldStep, 4);

                    if ($accruedToday && abs($delta) > 0.0001) {
                        $before = (float) $balance->balance;
                        $after = round($before + $delta, 4);
                        TodoBalanceLog::create([
                            'user_id' => $user->id,
                            'todo_balance_id' => $balance->id,
                            'reason' => TodoBalanceLog::REASON_MANUAL_EDIT,
                            'delta' => $delta,
                            'balance_before' => $before,
                            'balance_after' => $after,
                            'occurred_on' => $today,
                            'meta_json' => ['allotment_change' => true, 'from' => $oldStep, 'to' => $newStep],
                        ]);
                        $balance->updateQuietly(['balance' => $after]);
                        // Keep the hours-mode node snapshot in sync with the authoritative balance.
                        if (($node->tracking_mode ?? 'units') === 'hours') {
                            $node->updateQuietly(['tally' => $after]);
                        }
                    }
                }
            }
        }

        // If mark-done occurred (last_date was set), complete active sessions for this task.
        // Order matters: stop+log any running entry FIRST (so its time is banked by the reset),
        // then complete the session and reset, then continue the timer into a fresh session.
        if (array_key_exists('last_date', $data) && $data['last_date']) {
            $splitEntry = $this->splitRunningEntryAtMarkOff($user, $node);
            $completed = TimerSession::where('user_id', $user->id)
                ->where('item_id', $node->client_id)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->update(['status' => TimerSession::STATUS_COMPLETED]);
            // Completing a session banks its time against the finished item, so the next session
            // must start fresh — reset the node's logged time (matches the timer's session reset).
            if ($completed > 0) {
                $node->updateQuietly(['logged_time' => 0, 'logged_hours' => 0]);
            }
            if ($splitEntry) {
                $this->continueTimerAfterMarkOff($user, $node, $splitEntry);
            }
        }

        // Auto-create TodoBalance when tracking_mode is set to 'hours' and no balance exists
        $node = $node->fresh();
        if (($node->tracking_mode ?? 'units') === 'hours' && ! $node->todo_balance_id) {
            $balance = TodoBalance::create([
                'user_id' => $user->id,
                'item_key' => $node->label,
                'tracking_mode' => 'hours',
                'balance' => 0,
                'tally_step' => (float) ($node->tally_step ?? 0),
                'schedule' => $node->schedule,
            ]);
            TodoBalanceLog::create([
                'user_id' => $user->id,
                'todo_balance_id' => $balance->id,
                'reason' => TodoBalanceLog::REASON_SEED,
                'delta' => 0,
                'balance_before' => 0,
                'balance_after' => 0,
                'occurred_on' => Carbon::today()->toDateString(),
            ]);
            $node->update(['todo_balance_id' => $balance->id]);
        }

        // Handle groups update (rotating nodes)
        if (array_key_exists('groups', $data) && is_array($data['groups'])) {
            $this->syncGroups($node, $data['groups']);

            // Complete active sessions for this task (mark-done on group items).
            // Stop+log any running entry FIRST so the reset banks it, then continue the timer
            // into a fresh session (see splitRunningEntryAtMarkOff).
            $splitEntry = $this->splitRunningEntryAtMarkOff($user, $node);
            $completed = TimerSession::where('user_id', $user->id)
                ->where('item_id', $node->client_id)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->update(['status' => TimerSession::STATUS_COMPLETED]);
            // A completed session's time is banked against the finished item — reset the node's
            // logged time so the next session starts from a clean slate.
            if ($completed > 0) {
                $node->updateQuietly(['logged_time' => 0, 'logged_hours' => 0]);
            }
            if ($splitEntry) {
                $this->continueTimerAfterMarkOff($user, $node, $splitEntry);
            }
        }

        // Handle calendar_rules update — sync the todo_node_calendars junction table
        if (array_key_exists('calendar_rules', $data)) {
            $rules = is_array($data['calendar_rules']) ? $data['calendar_rules'] : [];
            $syncData = [];
            foreach ($rules as $idx => $rule) {
                if (isset($rule['calendar_id'])) {
                    $syncData[(int) $rule['calendar_id']] = [
                        'mode' => $rule['mode'] ?? 'add',
                        'sort_order' => $idx,
                    ];
                }
            }
            $node->calendars()->sync($syncData);
        }

        // Handle sub_items update (line_item nodes)
        if (array_key_exists('sub_items', $data) && is_array($data['sub_items'])) {
            $this->syncSubItems($node, $data['sub_items']);
        }

        // Handle children update (categories AND rotating slots — add/remove/reorder/re-home)
        if (array_key_exists('children', $data) && is_array($data['children'])) {
            $this->syncChildren($node, $data['children']);
        }

        // Explicit mark-off flag (sent by mark-done children patches). Runs the same atomic
        // timer/session sequence as the legacy groups and last_date blocks: stop+log any running
        // entry FIRST (so the reset banks it), complete the session, reset logged time, continue
        // the timer into a fresh session. Deliberately NOT keyed on `children` presence — drawer
        // and category edits also send children without any mark-off semantics.
        if (! empty($data['_mark_off'])) {
            $splitEntry = $this->splitRunningEntryAtMarkOff($user, $node);
            $completed = TimerSession::where('user_id', $user->id)
                ->where('item_id', $node->client_id)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->update(['status' => TimerSession::STATUS_COMPLETED]);
            if ($completed > 0) {
                $node->updateQuietly(['logged_time' => 0, 'logged_hours' => 0]);
            }
            if ($splitEntry) {
                $this->continueTimerAfterMarkOff($user, $node, $splitEntry);
            }
        }

        // Log tally change to balance.
        // For hours-mode: only update balance on explicit manual edits (_manual_balance_edit flag).
        // Timer-triggered tally changes are handled by timerStop/syncLoggedHoursForEntry.
        // For units-mode: always update balance on tally change.
        // Skip balance logging when structural fields are being modified to prevent accidental balance changes.
        $node = $node->fresh();
        $newTally = (float) $node->tally;
        $trackingMode = $node->tracking_mode ?? 'units';
        $isManualEdit = ! empty($data['_manual_balance_edit']);
        $isStructuralChange = array_key_exists('groups', $data)
            || array_key_exists('children', $data)
            || array_key_exists('task_type', $data)
            || array_key_exists('tracking_mode', $data);

        if ($node->todo_balance_id && ! $isStructuralChange) {
            $balance = TodoBalance::find($node->todo_balance_id);
            if ($balance) {
                $before = (float) $balance->balance;
                $after = null;

                if ($trackingMode === 'hours') {
                    // Hours mode: the balance is authoritative and node.tally can drift out of
                    // sync with it. A manual balance edit sets the balance directly to the target
                    // value (node.tally holds what the user typed), computing the delta from the
                    // balance — never from node.tally. Non-manual (timer) changes are handled by
                    // syncLoggedHoursForEntry, not here.
                    if ($isManualEdit && array_key_exists('tally', $data)) {
                        $after = $newTally;
                    }
                } else {
                    // Units mode: balance follows the change in tally (count).
                    $after = round($before + round($newTally - $oldTally, 4), 4);
                }

                if ($after !== null) {
                    $delta = round($after - $before, 4);
                    if (abs($delta) > 0.001) {
                        $reason = $isManualEdit
                            ? TodoBalanceLog::REASON_MANUAL_EDIT
                            : ($delta < 0 ? TodoBalanceLog::REASON_MARK_DONE : TodoBalanceLog::REASON_MANUAL_EDIT);
                        TodoBalanceLog::create([
                            'user_id' => $user->id,
                            'todo_balance_id' => $balance->id,
                            'reason' => $reason,
                            'delta' => $delta,
                            'balance_before' => $before,
                            'balance_after' => $after,
                            'occurred_on' => Carbon::today()->toDateString(),
                        ]);
                        $balance->updateQuietly(['balance' => $after]);
                    }
                }
            }
        }

        // Return the rebuilt node as JSON
        $node = $node->fresh();
        $tree = $this->treeService->buildTree($node->component);

        return response()->json($tree);
    }

    /**
     * Sync rotating groups from the frontend patch data.
     */
    protected function syncGroups(TodoTaskNode $node, array $groupsData): void
    {
        $existingGroups = TodoRotatingGroup::where('todo_task_node_id', $node->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('group_number');

        $incomingGroupNums = [];
        // Track every child client_id present anywhere in the payload, and which groups actually
        // provided a children array. Children absent from the payload must be DELETED after all
        // groups are processed (not per-group — an item may have moved between groups in the same
        // patch). Without this, removing an item from a group was silently ignored server-side
        // and the item resurrected on the next render/daily copy.
        $incomingChildIds = [];
        $groupIdsWithChildrenPayload = [];

        foreach ($groupsData as $gData) {
            foreach (($gData['children'] ?? []) as $childData) {
                if (! empty($childData['id'])) {
                    $incomingChildIds[] = $childData['id'];
                }
            }
        }

        foreach ($groupsData as $idx => $gData) {
            $groupNum = $gData['group_number'] ?? ($idx + 1);
            $incomingGroupNums[] = $groupNum;
            $group = $existingGroups->get($groupNum);

            if ($group) {
                // Update existing group
                $groupUpdates = ['sort_order' => $idx];
                foreach (['count_this_group', 'label', 'on_copy', 'last_date', 'mark_done_on_group', 'cascade_ratio'] as $f) {
                    if (array_key_exists($f, $gData)) {
                        $groupUpdates[$f] = $gData[$f];
                    }
                }
                $group->update($groupUpdates);
            } else {
                // Create new group
                $group = TodoRotatingGroup::create([
                    'todo_task_node_id' => $node->id,
                    'group_number' => $groupNum,
                    'label' => $gData['label'] ?? '#' . $groupNum . ' Priority',
                    'count_this_group' => $gData['count_this_group'] ?? 0,
                    'on_copy' => $gData['on_copy'] ?? 'preserve',
                    'last_date' => $gData['last_date'] ?? null,
                    'mark_done_on_group' => $gData['mark_done_on_group'] ?? false,
                    'cascade_ratio' => $gData['cascade_ratio'] ?? 2,
                    'sort_order' => $idx,
                ]);
            }

            // Sync child nodes within group
            if (isset($gData['children']) && is_array($gData['children'])) {
                $this->syncGroupChildren($group, $gData['children']);
                $groupIdsWithChildrenPayload[] = $group->id;
            }
        }

        // Delete groups that are no longer in the data — but preserve any balances
        $toDelete = $existingGroups->filter(fn ($g) => ! in_array($g->group_number, $incomingGroupNums));
        foreach ($toDelete as $g) {
            // Nullify the FK but don't touch the balance records themselves
            $childNodes = TodoTaskNode::where('todo_rotating_group_id', $g->id)->get();
            foreach ($childNodes as $childNode) {
                $childNode->forceDelete();
            }
            $g->forceDelete();
        }

        // Delete children removed from the payload. Runs after ALL groups synced, so items moved
        // between groups have already been re-homed (and are in $incomingChildIds regardless).
        // Only groups that explicitly provided a children array participate — a partial patch
        // that omits children must not wipe them.
        foreach ($groupIdsWithChildrenPayload as $groupId) {
            $stale = TodoTaskNode::where('todo_rotating_group_id', $groupId)
                ->whereNotIn('client_id', $incomingChildIds)
                ->get();
            foreach ($stale as $staleChild) {
                $staleChild->forceDelete();
            }
        }
    }

    /**
     * Sync child nodes within a rotating group.
     */
    protected function syncGroupChildren(TodoRotatingGroup $group, array $childrenData): void
    {
        $existing = TodoTaskNode::where('todo_rotating_group_id', $group->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('client_id');

        foreach ($childrenData as $idx => $childData) {
            $clientId = $childData['id'] ?? null;
            $child = $clientId ? $existing->get($clientId) : null;

            // If not found in this group, try to find it globally (may have been moved from another group)
            if (!$child && $clientId) {
                $child = TodoTaskNode::where('client_id', $clientId)
                    ->where('user_page_component_id', $group->taskNode->user_page_component_id ?? 0)
                    ->first();
                if ($child) {
                    // Move it to this group
                    $child->update(['todo_rotating_group_id' => $group->id]);
                }
            }

            if ($child) {
                $updates = ['sort_order' => $idx];
                foreach (['label', 'last_date', 'on_copy', 'tally', 'completed', 'task_type', 'tracking_mode', 'tally_step', 'time_budget_hours', 'cascade_ratio', 'schedule'] as $f) {
                    if (array_key_exists($f, $childData)) {
                        $updates[$f] = $childData[$f];
                    }
                }
                $child->update($updates);
            } else {
                // Brand-new item added to the group — create it (previously silently dropped,
                // the mirror image of removals being silently ignored).
                $child = TodoTaskNode::create([
                    'user_page_component_id' => $group->taskNode->user_page_component_id ?? 0,
                    'todo_rotating_group_id' => $group->id,
                    'sort_order' => $idx,
                    'client_id' => $clientId ?? ('tn-' . time() . '-' . substr(md5((string) rand()), 0, 6)),
                    'task_type' => $childData['task_type'] ?? 'line_item',
                    'label' => $childData['label'] ?? '',
                    'tally' => $childData['tally'] ?? null,
                    'tally_step' => $childData['tally_step'] ?? 1,
                    'on_copy' => $childData['on_copy'] ?? 'increment',
                    'tracking_mode' => $childData['tracking_mode'] ?? 'units',
                    'time_budget_hours' => $childData['time_budget_hours'] ?? null,
                    'last_date' => $childData['last_date'] ?? null,
                    'completed' => (bool) ($childData['completed'] ?? false),
                    'cascade_ratio' => (int) ($childData['cascade_ratio'] ?? 2),
                ]);
            }

            // If this child is a rotating node, sync its groups recursively
            if ($child->task_type === 'rotating' && isset($childData['groups'])) {
                $this->syncGroups($child, $childData['groups']);
            }
        }
    }

    // syncRotatingItems and syncSubGroups removed — replaced by syncGroupChildren

    /**
     * Sync sub_items for a line_item node.
     */
    protected function syncSubItems(TodoTaskNode $node, array $subItemsData): void
    {
        $existing = TodoSubItem::where('todo_task_node_id', $node->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('client_id');

        foreach ($subItemsData as $idx => $siData) {
            $clientId = $siData['id'] ?? null;
            $item = $clientId ? $existing->get($clientId) : null;

            if ($item) {
                $updates = ['sort_order' => $idx];
                foreach (['text', 'completed'] as $f) {
                    if (array_key_exists($f, $siData)) {
                        $updates[$f] = $siData[$f];
                    }
                }
                $item->update($updates);
            } else {
                // New sub_item
                TodoSubItem::create([
                    'todo_task_node_id' => $node->id,
                    'client_id' => $clientId ?? ('si-' . time() . '-' . substr(md5((string) rand()), 0, 6)),
                    'text' => $siData['text'] ?? '',
                    'completed' => (bool) ($siData['completed'] ?? false),
                    'sort_order' => $idx,
                ]);
            }
        }

        // Delete removed sub_items
        $keepIds = collect($subItemsData)->pluck('id')->filter()->toArray();
        TodoSubItem::where('todo_task_node_id', $node->id)
            ->whereNotIn('client_id', $keepIds)
            ->delete();
    }

    /**
     * Sync children of a category node (add new, remove deleted, reorder).
     */
    /**
     * Fields a children payload may write on a node. logged_hours/logged_time are deliberately
     * absent — the timer flow is their single writer (double-count corruption otherwise).
     */
    protected const CHILD_SYNC_FIELDS = [
        'label', 'task_type', 'schedule', 'description', 'collapsed',
        'tally', 'tally_step', 'time_budget_hours', 'tracking_mode',
        'last_date', 'on_copy', 'completed', 'decrement_on_done',
        'cascade_ratio', 'show_checkmark', 'count_this_group',
    ];

    /**
     * Recursively sync a node's children subtree from a PATCH payload — the single write path for
     * categories, rotating slots (priority_group / bare task / nested rotating), and slot items.
     * Two-phase like syncGroups: upsert (match under parent → re-home within component → create)
     * across the WHOLE payload first, then delete stale children — so an item moved between
     * parents/slots in one patch is re-homed, never mistaken for a removal.
     */
    protected function syncChildren(TodoTaskNode $node, array $childrenData): void
    {
        // Every client_id anywhere in the payload subtree survives deletion.
        $incomingIds = [];
        $collect = function (array $items) use (&$collect, &$incomingIds) {
            foreach ($items as $item) {
                if (! empty($item['id'])) {
                    $incomingIds[] = $item['id'];
                }
                if (! empty($item['children']) && is_array($item['children'])) {
                    $collect($item['children']);
                }
            }
        };
        $collect($childrenData);

        // Parents whose payload explicitly provided a children array — only these participate in
        // stale deletion (a partial patch omitting children must not wipe them).
        $parentsWithChildrenPayload = [];
        $this->upsertChildLevel($node, $childrenData, $parentsWithChildrenPayload);

        foreach ($parentsWithChildrenPayload as $parentId) {
            $stale = TodoTaskNode::where('parent_id', $parentId)
                ->whereNotIn('client_id', $incomingIds)
                ->get();
            foreach ($stale as $staleChild) {
                $staleChild->forceDelete();
            }
        }
    }

    /**
     * Upsert one level of children under $parent and recurse into provided grandchildren.
     */
    protected function upsertChildLevel(TodoTaskNode $parent, array $childrenData, array &$parentsWithChildrenPayload): void
    {
        $parentsWithChildrenPayload[] = $parent->id;

        $existing = TodoTaskNode::where('parent_id', $parent->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('client_id');

        foreach ($childrenData as $idx => $childData) {
            $clientId = $childData['id'] ?? null;
            $child = $clientId ? $existing->get($clientId) : null;

            // Moved from another parent/slot within this component — re-home instead of recreate,
            // preserving identity (balances, sessions, history key on the node/client_id).
            if (! $child && $clientId) {
                $child = TodoTaskNode::where('client_id', $clientId)
                    ->where('user_page_component_id', $parent->user_page_component_id)
                    ->first();
                if ($child) {
                    $child->update(['parent_id' => $parent->id, 'todo_rotating_group_id' => null]);
                }
            }

            if ($child) {
                $updates = ['sort_order' => $idx];
                foreach (self::CHILD_SYNC_FIELDS as $f) {
                    if (array_key_exists($f, $childData)) {
                        $updates[$f] = $childData[$f];
                    }
                }
                $child->update($updates);
            } else {
                $child = TodoTaskNode::create([
                    'user_page_component_id' => $parent->user_page_component_id,
                    'parent_id' => $parent->id,
                    'sort_order' => $idx,
                    'client_id' => $clientId ?? ('tn-' . time() . '-' . substr(md5((string) rand()), 0, 6)),
                    'task_type' => $childData['task_type'] ?? 'line_item',
                    'label' => $childData['label'] ?? '',
                    'schedule' => $childData['schedule'] ?? $parent->schedule,
                    'tally' => $childData['tally'] ?? null,
                    'tally_step' => $childData['tally_step'] ?? 1,
                    'time_budget_hours' => $childData['time_budget_hours'] ?? null,
                    'tracking_mode' => $childData['tracking_mode'] ?? 'units',
                    'last_date' => $childData['last_date'] ?? null,
                    'on_copy' => $childData['on_copy'] ?? 'increment',
                    'completed' => (bool) ($childData['completed'] ?? false),
                    'cascade_ratio' => (int) ($childData['cascade_ratio'] ?? 2),
                    'show_checkmark' => (bool) ($childData['show_checkmark'] ?? false),
                    'count_this_group' => isset($childData['count_this_group']) ? (int) $childData['count_this_group'] : null,
                    'description' => $childData['description'] ?? null,
                ]);
            }

            // Recurse into provided grandchildren (priority_group items, nested containers)
            if (isset($childData['children']) && is_array($childData['children'])) {
                $this->upsertChildLevel($child, $childData['children'], $parentsWithChildrenPayload);
            }

            // Legacy: nested rotating children may still carry a groups payload until cleanup
            if ($child->task_type === TodoTaskNode::TASK_TYPE_ROTATING && isset($childData['groups'])) {
                $this->syncGroups($child, $childData['groups']);
            }
        }
    }

    protected function pageWithBalances($page, User $user): JsonResponse
    {
        $page->load('components');
        $balances = TodoBalance::where('user_id', $user->id)->get();

        $data = $page->toArray();

        // Rebuild config_json from relational tables for todo_task components
        if (isset($data['components'])) {
            foreach ($data['components'] as &$componentData) {
                if ($componentData['component_type'] === 'todo_task') {
                    $component = $page->components->firstWhere('id', $componentData['id']);
                    if ($component) {
                        $tree = $this->treeService->buildTree($component);
                        if ($tree !== null) {
                            $componentData['config_json'] = $tree;
                        }
                    }
                }
            }
            unset($componentData);
        }

        $data['balances'] = $balances->toArray();

        // For day-level pages, include each balance "as of" the page's date, derived from the
        // authoritative balance log (the last balance_after with occurred_on <= the page date).
        // Historical day pages render hours-mode values from this rather than the per-node `tally`
        // snapshot, which is written by multiple paths with inconsistent sign conventions and is
        // unreliable for any task without timer activity on a given day.
        $config = $page->config_json ?? [];
        if (($config['todo_level'] ?? null) === 'day' && ! empty($config['todo_date'])) {
            $asOfDate = $config['todo_date'];
            $asOf = [];
            foreach ($balances as $balance) {
                $log = TodoBalanceLog::where('todo_balance_id', $balance->id)
                    ->where('occurred_on', '<=', $asOfDate)
                    ->orderByDesc('occurred_on')
                    ->orderByDesc('id')
                    ->first();
                $asOf[$balance->id] = $log ? (float) $log->balance_after : 0.0;
            }
            $data['balances_asof'] = $asOf;
        }

        return response()->json($data);
    }

    protected function buildHierarchyTree($yearPage): array
    {
        $tree = [
            'id' => $yearPage->id,
            'name' => $yearPage->name,
            'slug' => $yearPage->slug,
            'config_json' => $yearPage->config_json,
            'months' => [],
        ];

        foreach ($yearPage->childPages as $monthPage) {
            $month = [
                'id' => $monthPage->id,
                'name' => $monthPage->name,
                'slug' => $monthPage->slug,
                'config_json' => $monthPage->config_json,
                'weeks' => [],
            ];

            foreach ($monthPage->childPages as $weekPage) {
                $week = [
                    'id' => $weekPage->id,
                    'name' => $weekPage->name,
                    'slug' => $weekPage->slug,
                    'config_json' => $weekPage->config_json,
                    'days' => [],
                ];

                foreach ($weekPage->childPages as $dayPage) {
                    $week['days'][] = [
                        'id' => $dayPage->id,
                        'name' => $dayPage->name,
                        'slug' => $dayPage->slug,
                        'config_json' => $dayPage->config_json,
                    ];
                }

                $month['weeks'][] = $week;
            }

            $tree['months'][] = $month;
        }

        return $tree;
    }

    // =========================================================================
    // CALENDARS
    // =========================================================================

    public function calendarIndex(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $calendars = TodoCalendar::where('user_id', $user->id)->orderBy('name')->get();
        return response()->json(['data' => $calendars]);
    }

    public function calendarStore(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $calendar = TodoCalendar::create(array_merge(
            $request->only(['name', 'days_of_week', 'specific_dates', 'is_exclusion', 'active_on_vacation']),
            ['user_id' => $user->id]
        ));
        return response()->json($calendar, 201);
    }

    public function calendarUpdate(Todo\ViewRequest $request, User $user, TodoCalendar $calendar): JsonResponse
    {
        if ($calendar->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $calendar->update($request->only(['name', 'days_of_week', 'specific_dates', 'is_exclusion', 'active_on_vacation']));
        return response()->json($calendar);
    }

    public function calendarDestroy(Todo\ViewRequest $request, User $user, TodoCalendar $calendar): JsonResponse
    {
        if ($calendar->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $calendar->delete();
        return response()->json(null, 204);
    }

    // =========================================================================
    // VACATION
    // =========================================================================

    /**
     * Current vacation status: whether a period is in effect today (start <= today and end is
     * either unset or still in the future), plus that period — which may carry a scheduled end
     * date so vacation auto-ends without the user toggling it off.
     */
    public function vacationShow(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $current = $this->currentVacationPeriod($user);

        return response()->json([
            'on_vacation' => $current !== null,
            'current_period' => $current,
        ]);
    }

    /**
     * The vacation period in effect for the user's "today": started on/before today and not yet
     * ended (no end_date, or an end_date today or later).
     */
    protected function currentVacationPeriod(User $user): ?TodoVacationPeriod
    {
        $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
        $timezone = $settings->timezone ?? 'UTC';
        $today = Carbon::now($timezone)->toDateString();

        return TodoVacationPeriod::where('user_id', $user->id)
            ->where('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Update vacation state. Body:
     *   - on_vacation (bool): false ends the active period today; true starts one (if none) or
     *     leaves the active one in place.
     *   - end_date (string 'YYYY-MM-DD' | null, optional): schedules when vacation ends. Pass null
     *     to make it open-ended. Only applied while turning/keeping vacation on.
     */
    public function vacationUpdate(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $onVacation = (bool) $request->input('on_vacation');
        $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
        $timezone = $settings->timezone ?? 'UTC';
        $today = Carbon::now($timezone)->toDateString();

        $current = $this->currentVacationPeriod($user);
        $hasEndDate = $request->has('end_date');
        $endDate = $request->input('end_date') ?: null; // '' -> null (open-ended)

        if (! $onVacation) {
            // End the vacation now.
            if ($current) {
                $current->update(['end_date' => $today]);
            }
        } elseif (! $current) {
            // Start a new vacation, optionally with a scheduled end date.
            TodoVacationPeriod::create([
                'user_id' => $user->id,
                'start_date' => $today,
                'end_date' => $hasEndDate ? $endDate : null,
            ]);
        } elseif ($hasEndDate) {
            // Already on vacation — (re)schedule the end date.
            $current->update(['end_date' => $endDate]);
        }

        return $this->vacationShow($request, $user);
    }

    /**
     * Split a running time entry at a mark-off boundary. The pre-mark-off portion is stopped and
     * logged NOW — before the caller completes the session and banks/resets the node's logged
     * time — so the ordering is deterministic within one request. Previously the frontend issued
     * separate stop/start calls concurrently with the mark-off PATCH; depending on which landed
     * first, already-banked time was re-credited onto the node and/or the continuation entry got
     * attached to the just-completed session (loose credit belonging to no visible session).
     */
    protected function splitRunningEntryAtMarkOff(User $user, TodoTaskNode $node): ?TimeEntry
    {
        $running = TimeEntry::where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->where('item_id', $node->client_id)
            ->first();
        if (! $running) {
            return null;
        }

        $now = Carbon::now();
        $durationSeconds = (int) abs($now->diffInSeconds(Carbon::parse($running->started_at)));
        // Guarded transition (see timerStart): a concurrent stop already logged this entry —
        // splitting it again would double-count, and continuing a user-stopped timer is wrong.
        $stopped = TimeEntry::where('id', $running->id)
            ->whereNull('stopped_at')
            ->update(['stopped_at' => $now, 'duration_seconds' => $durationSeconds]);
        if (! $stopped) {
            return null;
        }
        $running->refresh();

        if ($durationSeconds >= 2) {
            $hours = $durationSeconds / 3600;
            $this->logBalanceChange($user, $running->label, $hours, TodoBalanceLog::REASON_TIMER_LOGGED, $running, $this->userLocalDate($user, $running->started_at));
            $this->syncLoggedHoursForEntry($user, $running->label, $running->started_at, $hours);
        }

        return $running;
    }

    /**
     * Continue the timer after a mark-off split: open a fresh ACTIVE session and a new running
     * entry carrying the same target/budgets as the entry that was just closed, so time after
     * the mark-off accrues to the next session — consistently visible in both the session bar
     * and the node's displayed value.
     */
    protected function continueTimerAfterMarkOff(User $user, TodoTaskNode $node, TimeEntry $closed): void
    {
        $session = TimerSession::create([
            'user_id' => $user->id,
            'component_id' => $closed->component_id,
            'item_id' => $closed->item_id,
            'label' => $node->label,
            'session_budget_seconds' => (int) (((float) ($closed->session_budget_hours ?? 0)) * 3600),
            'status' => TimerSession::STATUS_ACTIVE,
        ]);

        $this->timeEntryRepository->create([
            'user_id' => $user->id,
            'timer_session_id' => $session->id,
            'label' => $closed->label,
            'component_id' => $closed->component_id,
            'item_id' => $closed->item_id,
            'budget_hours' => $closed->budget_hours,
            'session_budget_hours' => $closed->session_budget_hours,
            'todo_balance_id' => $closed->todo_balance_id,
            'started_at' => Carbon::now(),
            'stopped_at' => null,
            'duration_seconds' => 0,
        ]);
    }

    /**
     * Log a balance change for an hours-mode item.
     * Extracts the item_key from the timer label (strips "Parent — " prefix).
     */
    protected function logBalanceChange(User $user, string $label, float $hoursDelta, string $reason, ?TimeEntry $sourceEntry = null, ?string $date = null): void
    {
        if (abs($hoursDelta) < 0.0001) {
            return;
        }

        // Normalize label: "Work Hours — Poseidon Research Coding" -> "Poseidon Research Coding"
        $itemKey = $label;
        if (str_contains($itemKey, ' — ')) {
            $itemKey = explode(' — ', $itemKey)[1];
        }
        $itemKey = rtrim($itemKey, ' -');

        $balance = TodoBalance::where('user_id', $user->id)
            ->where('item_key', $itemKey)
            ->where('tracking_mode', TodoBalance::TRACKING_MODE_HOURS)
            ->first();

        if (!$balance) {
            return;
        }

        // Atomic update to prevent race conditions from concurrent requests
        $balance->refresh();
        $before = (float) $balance->balance;
        $after = round($before - $hoursDelta, 4);

        TodoBalanceLog::create([
            'user_id' => $user->id,
            'todo_balance_id' => $balance->id,
            'reason' => $reason,
            'delta' => round(-$hoursDelta, 4),
            'balance_before' => $before,
            'balance_after' => $after,
            'occurred_on' => $date ?? Carbon::today()->toDateString(),
            'source_type' => $sourceEntry ? 'time_entry' : null,
            'source_id' => $sourceEntry?->id,
        ]);
        $balance->updateQuietly(['balance' => $after]);
    }

    /**
     * Convert a balance record between tracking modes.
     *
     * Units → Hours: new_balance = old_balance * time_budget_hours (one entry)
     * Hours → Units: new_balance = old_balance / time_budget_hours,
     *   then a correction entry to remove the fractional remainder (two entries)
     */
    protected function convertBalanceMode(TodoBalance $balance, string $newMode, float $timeBudgetHours, ?string $date = null): void
    {
        $date = $date ?? Carbon::today()->toDateString();
        $before = (float) $balance->balance;

        if ($newMode === TodoBalance::TRACKING_MODE_HOURS) {
            // Units → Hours: multiply
            $after = round($before * $timeBudgetHours, 4);
            $delta = round($after - $before, 4);

            TodoBalanceLog::create([
                'user_id' => $balance->user_id,
                'todo_balance_id' => $balance->id,
                'reason' => TodoBalanceLog::REASON_CONVERSION,
                'delta' => $delta,
                'balance_before' => $before,
                'balance_after' => $after,
                'occurred_on' => $date,
                'meta_json' => ['from' => 'units', 'to' => 'hours', 'multiplier' => $timeBudgetHours],
            ]);

            $balance->update(['tracking_mode' => $newMode]);
            // Balance recalculation handled by RecalcTodoBalanceJob via observer
        } else {
            // Hours → Units: divide, then correct remainder
            $rawUnits = $before / $timeBudgetHours;
            $wholeUnits = floor($rawUnits);
            $remainder = round($before - ($wholeUnits * $timeBudgetHours), 4);

            // Entry 1: conversion to whole units
            $convertedBalance = round($wholeUnits, 4);
            $conversionDelta = round($convertedBalance - $before, 4);

            TodoBalanceLog::create([
                'user_id' => $balance->user_id,
                'todo_balance_id' => $balance->id,
                'reason' => TodoBalanceLog::REASON_CONVERSION,
                'delta' => $conversionDelta,
                'balance_before' => $before,
                'balance_after' => $convertedBalance,
                'occurred_on' => $date,
                'meta_json' => ['from' => 'hours', 'to' => 'units', 'divisor' => $timeBudgetHours],
            ]);

            // Entry 2: correction to shave off fractional remainder
            if (abs($remainder) > 0.001) {
                $correctionDelta = round(-$remainder / $timeBudgetHours, 4);
                $finalBalance = round($convertedBalance + $correctionDelta, 4);

                TodoBalanceLog::create([
                    'user_id' => $balance->user_id,
                    'todo_balance_id' => $balance->id,
                    'reason' => TodoBalanceLog::REASON_CORRECTION,
                    'delta' => $correctionDelta,
                    'balance_before' => $convertedBalance,
                    'balance_after' => $finalBalance,
                    'occurred_on' => $date,
                    'meta_json' => ['remainder_hours' => $remainder, 'shaved_units' => abs($correctionDelta)],
                ]);

                $balance->update(['tracking_mode' => $newMode]);
            } else {
                $balance->update(['tracking_mode' => $newMode]);
            }
        }
    }

    protected function findPageByDateConfig(User $user, string $level, string $configKey, string $value): ?object
    {
        return $this->generationService->findPageByConfig($user, $level, [$configKey => $value]);
    }

    protected function findPageByMonthYear(User $user, int $month, int $year): ?object
    {
        return $this->generationService->findPageByConfig($user, 'month', [
            'todo_month' => $month,
            'todo_year' => $year,
        ]);
    }

    protected function findPageByYear(User $user, int $year): ?object
    {
        return $this->generationService->findPageByConfig($user, 'year', [
            'todo_year' => $year,
        ]);
    }
}
