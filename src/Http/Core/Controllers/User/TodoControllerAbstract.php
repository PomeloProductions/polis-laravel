<?php

declare(strict_types=1);

namespace Polis\Http\Core\Controllers\User;

use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Polis\Contracts\Repositories\User\TimeEntryRepositoryContract;
use Polis\Contracts\Repositories\User\TodoSettingRepositoryContract;
use Polis\Contracts\Repositories\User\TodoTemplateRepositoryContract;
use Polis\Http\Core\Controllers\BaseControllerAbstract;
use Polis\Http\Core\Controllers\Traits\HasIndexRequests;
use Polis\Http\Core\Requests\User\Todo;
use Polis\Http\Core\Requests\User\TodoTemplate;
use Polis\Models\BaseModelAbstract;
use Polis\Models\User\TimeEntry;
use Polis\Models\User\TimerSession;
use Polis\Models\User\TodoBalance;
use Polis\Models\User\TodoBalanceLog;
use Polis\Models\User\TodoCalendar;
use Polis\Models\User\TodoRotatingGroup;
use Polis\Models\User\TodoSubItem;
use Polis\Models\User\TodoTaskNode;
use Polis\Models\User\TodoTemplate as TodoTemplateModel;
use Polis\Models\User\TodoVacationPeriod;
use Polis\Services\Todo\TodoGenerationService;
use Polis\Services\Todo\TodoTaskTreeService;

/**
 * Abstract Todo controller. A consuming application extends this, adds the route
 * bindings and any consumer-specific overrides, and wires the concrete class to
 * routes — mirroring how the package exposes ArticleNote/Ballot/Collection
 * controllers. All behaviour is ported verbatim from PolisOS; only namespaces
 * were retargeted so the module lives in the package.
 */
abstract class TodoControllerAbstract extends BaseControllerAbstract
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

        return response()->json([
            'entry' => $running,
            'session' => $sessionData,
        ]);
    }

    public function timerStart(Todo\TimerStartRequest $request, User $user): JsonResponse
    {
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

            $entry->update([
                'stopped_at' => $now,
                'duration_seconds' => $durationSeconds,
            ]);
            $hours = $durationSeconds / 3600;
            $this->logBalanceChange($user, $entry->label, $hours, TodoBalanceLog::REASON_TIMER_LOGGED, $entry, $this->userLocalDate($user, $entry->started_at));
            $this->syncLoggedHoursForEntry($user, $entry->label, $entry->started_at, $hours);
        }

        $requestData = $request->json()->all();
        $componentId = $requestData['component_id'] ?? null;
        $itemId = $requestData['item_id'] ?? null;

        $session = null;
        if ($itemId) {
            $session = TimerSession::where('user_id', $user->id)
                ->where('item_id', $itemId)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->orderByDesc('id')
                ->first();
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
        $running = TimeEntry::where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->first();

        if (! $running) {
            return response()->json(null, 204);
        }

        $now = Carbon::now();
        $started = Carbon::parse($running->started_at);
        $durationSeconds = (int) abs($now->diffInSeconds($started));

        $running->update([
            'stopped_at' => $now,
            'duration_seconds' => $durationSeconds,
        ]);

        $hours = $durationSeconds / 3600;
        $this->logBalanceChange($user, $running->label, $hours, TodoBalanceLog::REASON_TIMER_LOGGED, $running, $this->userLocalDate($user, $running->started_at));
        $this->syncLoggedHoursForEntry($user, $running->label, $running->started_at, $hours);

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

        if ($oldLabel === $timeEntry->label) {
            $diff = ($timeEntry->duration_seconds / 3600) - $oldHours;
            if (abs($diff) > 0.0001) {
                $this->logBalanceChange($user, $timeEntry->label, $diff, TodoBalanceLog::REASON_TIMER_UPDATED, $timeEntry, $this->userLocalDate($user, $timeEntry->started_at));
                $this->syncLoggedHoursForEntry($user, $timeEntry->label, $timeEntry->started_at, $diff);
            }
        } else {
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
     * Return the user's local date (YYYY-MM-DD) for a given UTC timestamp.
     */
    protected function userLocalDate(User $user, $timestamp): string
    {
        $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
        $timezone = $settings->timezone ?? 'UTC';

        return Carbon::parse($timestamp)->setTimezone($timezone)->toDateString();
    }

    /**
     * Sync logged hours delta to the matching task node for the day the entry falls on.
     */
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

        $node = TodoTaskNode::whereIn('user_page_component_id', $componentIds)
            ->where('label', $label)
            ->first();

        if ($node) {
            return $node;
        }

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
     */
    public function patchNode(Todo\ViewRequest $request, User $user, string $clientId): JsonResponse
    {
        $componentId = $request->input('component_id');

        $query = TodoTaskNode::where('client_id', $clientId)
            ->whereHas('component.page', fn ($q) => $q->where('user_id', $user->id));

        if ($componentId) {
            $query->where('user_page_component_id', $componentId);
        } else {
            $query->orderByDesc('id');
        }

        $node = $query->firstOrFail();

        $data = $request->json()->all();

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

        $oldTally = (float) $node->tally;

        $scalarFields = [
            'label', 'description', 'collapsed', 'tally', 'tally_step', 'schedule',
            'on_copy', 'time_budget_hours', 'logged_hours', 'logged_time', 'deficit',
            'tracking_mode', 'decrement_on_done', 'time_tracking_mode', 'completed',
            'last_date', 'custom_groups', 'cascade_ratio', 'task_type', 'show_checkmark',
        ];

        $nodeUpdates = [];
        $isManualEdit = ! empty($data['_manual_balance_edit']);
        $trackingMode = $node->tracking_mode ?? 'units';

        foreach ($scalarFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($trackingMode === 'hours' && ! $isManualEdit) {
                    if (in_array($field, ['tally', 'logged_hours', 'logged_time'])) {
                        continue;
                    }
                }
                $nodeUpdates[$field] = $data[$field];
            }
        }

        if (! empty($nodeUpdates)) {
            $node->update($nodeUpdates);
        }

        if (array_key_exists('tally_step', $data) && $node->todo_balance_id) {
            $balance = TodoBalance::find($node->todo_balance_id);
            if ($balance) {
                $balance->updateQuietly(['tally_step' => (float) $data['tally_step']]);
            }
        }

        if (array_key_exists('last_date', $data) && $data['last_date']) {
            TimerSession::where('user_id', $user->id)
                ->where('item_id', $node->client_id)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->update(['status' => TimerSession::STATUS_COMPLETED]);
        }

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

        if (array_key_exists('groups', $data) && is_array($data['groups'])) {
            $this->syncGroups($node, $data['groups']);

            TimerSession::where('user_id', $user->id)
                ->where('item_id', $node->client_id)
                ->where('status', TimerSession::STATUS_ACTIVE)
                ->update(['status' => TimerSession::STATUS_COMPLETED]);
        }

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

        if (array_key_exists('sub_items', $data) && is_array($data['sub_items'])) {
            $this->syncSubItems($node, $data['sub_items']);
        }

        if (array_key_exists('children', $data) && is_array($data['children'])) {
            $this->syncChildren($node, $data['children']);
        }

        $node = $node->fresh();
        $newTally = (float) $node->tally;
        $tallyDelta = round($newTally - $oldTally, 4);
        $trackingMode = $node->tracking_mode ?? 'units';
        $isManualEdit = ! empty($data['_manual_balance_edit']);
        $isStructuralChange = array_key_exists('groups', $data)
            || array_key_exists('children', $data)
            || array_key_exists('task_type', $data)
            || array_key_exists('tracking_mode', $data);
        $shouldLogBalance = abs($tallyDelta) > 0.001
            && $node->todo_balance_id
            && ($trackingMode !== 'hours' || $isManualEdit)
            && ! $isStructuralChange;
        if ($shouldLogBalance) {
            $balance = TodoBalance::find($node->todo_balance_id);
            if ($balance) {
                $reason = $tallyDelta < 0 ? TodoBalanceLog::REASON_MARK_DONE : TodoBalanceLog::REASON_MANUAL_EDIT;
                $before = (float) $balance->balance;
                $after = round($before + $tallyDelta, 4);
                TodoBalanceLog::create([
                    'user_id' => $user->id,
                    'todo_balance_id' => $balance->id,
                    'reason' => $reason,
                    'delta' => $tallyDelta,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'occurred_on' => Carbon::today()->toDateString(),
                ]);
                $balance->updateQuietly(['balance' => $after]);
            }
        }

        $node = $node->fresh();
        $tree = $this->treeService->buildTree($node->component);

        return response()->json($tree);
    }

    /**
     * Sync rotating groups from the frontend patch data.
     *
     * @param  array<int, array<string, mixed>>  $groupsData
     */
    protected function syncGroups(TodoTaskNode $node, array $groupsData): void
    {
        $existingGroups = TodoRotatingGroup::where('todo_task_node_id', $node->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('group_number');

        $incomingGroupNums = [];

        foreach ($groupsData as $idx => $gData) {
            $groupNum = $gData['group_number'] ?? ($idx + 1);
            $incomingGroupNums[] = $groupNum;
            $group = $existingGroups->get($groupNum);

            if ($group) {
                $groupUpdates = ['sort_order' => $idx];
                foreach (['count_this_group', 'label', 'on_copy', 'last_date', 'mark_done_on_group', 'cascade_ratio'] as $f) {
                    if (array_key_exists($f, $gData)) {
                        $groupUpdates[$f] = $gData[$f];
                    }
                }
                $group->update($groupUpdates);
            } else {
                $group = TodoRotatingGroup::create([
                    'todo_task_node_id' => $node->id,
                    'group_number' => $groupNum,
                    'label' => $gData['label'] ?? '#'.$groupNum.' Priority',
                    'count_this_group' => $gData['count_this_group'] ?? 0,
                    'on_copy' => $gData['on_copy'] ?? 'preserve',
                    'last_date' => $gData['last_date'] ?? null,
                    'mark_done_on_group' => $gData['mark_done_on_group'] ?? false,
                    'cascade_ratio' => $gData['cascade_ratio'] ?? 2,
                    'sort_order' => $idx,
                ]);
            }

            if (isset($gData['children']) && is_array($gData['children'])) {
                $this->syncGroupChildren($group, $gData['children']);
            }
        }

        $toDelete = $existingGroups->filter(fn ($g) => ! in_array($g->group_number, $incomingGroupNums));
        foreach ($toDelete as $g) {
            $childNodes = TodoTaskNode::where('todo_rotating_group_id', $g->id)->get();
            foreach ($childNodes as $childNode) {
                $childNode->forceDelete();
            }
            $g->forceDelete();
        }
    }

    /**
     * Sync child nodes within a rotating group.
     *
     * @param  array<int, array<string, mixed>>  $childrenData
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

            if (! $child && $clientId) {
                $child = TodoTaskNode::where('client_id', $clientId)
                    ->where('user_page_component_id', $group->taskNode->user_page_component_id ?? 0)
                    ->first();
                if ($child) {
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

                if ($child->task_type === 'rotating' && isset($childData['groups'])) {
                    $this->syncGroups($child, $childData['groups']);
                }
            }
        }
    }

    /**
     * Sync sub_items for a line_item node.
     *
     * @param  array<int, array<string, mixed>>  $subItemsData
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
                TodoSubItem::create([
                    'todo_task_node_id' => $node->id,
                    'client_id' => $clientId ?? ('si-'.time().'-'.substr(md5((string) rand()), 0, 6)),
                    'text' => $siData['text'] ?? '',
                    'completed' => (bool) ($siData['completed'] ?? false),
                    'sort_order' => $idx,
                ]);
            }
        }

        $keepIds = collect($subItemsData)->pluck('id')->filter()->toArray();
        TodoSubItem::where('todo_task_node_id', $node->id)
            ->whereNotIn('client_id', $keepIds)
            ->delete();
    }

    /**
     * Sync children of a category node (add new, remove deleted, reorder).
     *
     * @param  array<int, array<string, mixed>>  $childrenData
     */
    protected function syncChildren(TodoTaskNode $node, array $childrenData): void
    {
        $existing = TodoTaskNode::where('parent_id', $node->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('client_id');

        foreach ($childrenData as $idx => $childData) {
            $clientId = $childData['id'] ?? null;
            $child = $clientId ? $existing->get($clientId) : null;

            if ($child) {
                $updates = ['sort_order' => $idx];
                foreach (['label', 'task_type', 'schedule'] as $f) {
                    if (isset($childData[$f]) && $childData[$f] !== $child->$f) {
                        $updates[$f] = $childData[$f];
                    }
                }
                $child->update($updates);
            } else {
                TodoTaskNode::create([
                    'user_page_component_id' => $node->user_page_component_id,
                    'parent_id' => $node->id,
                    'sort_order' => $idx,
                    'client_id' => $clientId ?? ('tn-'.time().'-'.substr(md5((string) rand()), 0, 6)),
                    'task_type' => $childData['task_type'] ?? 'line_item',
                    'label' => $childData['label'] ?? '',
                    'schedule' => $childData['schedule'] ?? $node->schedule,
                ]);
            }
        }

        $keepIds = collect($childrenData)->pluck('id')->filter()->toArray();
        if (count($keepIds) > 0 || count($childrenData) === 0) {
            $existingIds = $existing->pluck('client_id')->toArray();
            $toRemove = array_diff($existingIds, $keepIds);
            if (count($toRemove) > 0) {
                TodoTaskNode::where('parent_id', $node->id)
                    ->whereIn('client_id', $toRemove)
                    ->delete();
            }
        }
    }

    protected function pageWithBalances($page, User $user): JsonResponse
    {
        $page->load('components');
        $balances = TodoBalance::where('user_id', $user->id)->get();

        $data = $page->toArray();

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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * Current vacation status: whether an open period exists, plus the open period if any.
     */
    public function vacationShow(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $open = TodoVacationPeriod::where('user_id', $user->id)
            ->whereNull('end_date')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'on_vacation' => $open !== null,
            'current_period' => $open,
        ]);
    }

    /**
     * Toggle vacation on/off.
     */
    public function vacationUpdate(Todo\ViewRequest $request, User $user): JsonResponse
    {
        $onVacation = (bool) $request->input('on_vacation');
        $settings = $this->settingRepository->findAll([['user_id', '=', $user->id]])->first();
        $timezone = $settings->timezone ?? 'UTC';
        $today = Carbon::now($timezone)->toDateString();

        $open = TodoVacationPeriod::where('user_id', $user->id)
            ->whereNull('end_date')
            ->orderByDesc('id')
            ->first();

        if ($onVacation && ! $open) {
            TodoVacationPeriod::create([
                'user_id' => $user->id,
                'start_date' => $today,
                'end_date' => null,
            ]);
        } elseif (! $onVacation && $open) {
            $open->update(['end_date' => $today]);
        }

        return $this->vacationShow($request, $user);
    }

    /**
     * Log a balance change for an hours-mode item.
     */
    protected function logBalanceChange(User $user, string $label, float $hoursDelta, string $reason, ?TimeEntry $sourceEntry = null, ?string $date = null): void
    {
        if (abs($hoursDelta) < 0.0001) {
            return;
        }

        $itemKey = $label;
        if (str_contains($itemKey, ' — ')) {
            $itemKey = explode(' — ', $itemKey)[1];
        }
        $itemKey = rtrim($itemKey, ' -');

        $balance = TodoBalance::where('user_id', $user->id)
            ->where('item_key', $itemKey)
            ->where('tracking_mode', TodoBalance::TRACKING_MODE_HOURS)
            ->first();

        if (! $balance) {
            return;
        }

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
     */
    protected function convertBalanceMode(TodoBalance $balance, string $newMode, float $timeBudgetHours, ?string $date = null): void
    {
        $date = $date ?? Carbon::today()->toDateString();
        $before = (float) $balance->balance;

        if ($newMode === TodoBalance::TRACKING_MODE_HOURS) {
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
        } else {
            $rawUnits = $before / $timeBudgetHours;
            $wholeUnits = floor($rawUnits);
            $remainder = round($before - ($wholeUnits * $timeBudgetHours), 4);

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
