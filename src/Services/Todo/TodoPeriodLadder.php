<?php

declare(strict_types=1);

namespace Polis\Services\Todo;

use Illuminate\Support\Carbon;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\PeriodLadderContract;
use Polis\Contracts\Repositories\User\TodoSettingRepositoryContract;
use Polis\Models\User\UserPage;
use Polis\Services\PeriodPageGenerationService;

/**
 * The Todo domain's {@see PeriodLadderContract}: a year → month → week → day
 * hierarchy of `page_type = 'todo'` pages. All Todo-specific page shaping
 * (slugs, names, config_json fingerprints, week-start math) lives here so the
 * generic {@see PeriodPageGenerationService} stays domain-free.
 *
 * Owners are {@see IsAnEntityContract}; today that maps to a `user_id` column on
 * user_pages (see ownerFilter/ownerAttributes), which is the seam an
 * organization-owned ladder would override once the schema carries a
 * polymorphic owner.
 */
class TodoPeriodLadder implements PeriodLadderContract
{
    public const LEVEL_YEAR = 'year';

    public const LEVEL_MONTH = 'month';

    public const LEVEL_WEEK = 'week';

    public const LEVEL_DAY = 'day';

    public function __construct(
        protected TodoSettingRepositoryContract $settingRepository,
    ) {}

    public function pageType(): string
    {
        return 'todo';
    }

    public function levels(): array
    {
        return [self::LEVEL_YEAR, self::LEVEL_MONTH, self::LEVEL_WEEK, self::LEVEL_DAY];
    }

    public function rootMatch(): array
    {
        return ['todo_level' => 'root'];
    }

    public function ownerFilter(IsAnEntityContract $entity): array
    {
        return [['user_id', '=', $entity->getKey()]];
    }

    public function ownerAttributes(IsAnEntityContract $entity): array
    {
        return ['user_id' => $entity->getKey()];
    }

    public function configMatchFor(IsAnEntityContract $entity, string $level, Carbon $date): array
    {
        return match ($level) {
            self::LEVEL_YEAR => ['todo_level' => 'year', 'todo_year' => $date->year],
            self::LEVEL_MONTH => ['todo_level' => 'month', 'todo_month' => $date->month, 'todo_year' => $date->year],
            self::LEVEL_WEEK => ['todo_level' => 'week', 'todo_week_start' => $this->weekStart($entity, $date)->toDateString()],
            self::LEVEL_DAY => ['todo_level' => 'day', 'todo_date' => $date->toDateString()],
            default => ['todo_level' => $level],
        };
    }

    public function pageAttributesFor(IsAnEntityContract $entity, string $level, Carbon $date, UserPage $parentPage): array
    {
        return match ($level) {
            self::LEVEL_YEAR => $this->yearAttributes($date),
            self::LEVEL_MONTH => $this->monthAttributes($date),
            self::LEVEL_WEEK => $this->weekAttributes($entity, $date),
            self::LEVEL_DAY => $this->dayAttributes($entity, $date),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function yearAttributes(Carbon $date): array
    {
        $slug = (string) $date->year;

        return [
            'slug' => $slug,
            'name' => (string) $date->year,
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'display_order' => $date->year,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'config_json' => ['todo_level' => 'year', 'todo_year' => $date->year],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function monthAttributes(Carbon $date): array
    {
        $slug = "{$date->year}/{$date->format('m')}";

        return [
            'slug' => $slug,
            'name' => $date->format('F'),
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'display_order' => $date->month,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'config_json' => ['todo_level' => 'month', 'todo_month' => $date->month, 'todo_year' => $date->year],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function weekAttributes(IsAnEntityContract $entity, Carbon $date): array
    {
        $weekStart = $this->weekStart($entity, $date);
        $weekEnd = $this->weekEnd($entity, $weekStart);
        $weekNumber = $this->weekNumber($weekStart);

        $slug = "{$weekStart->year}/{$weekStart->format('m')}/week-{$weekNumber}";
        $name = "Week {$weekNumber} ({$weekStart->format('n/j')} - {$weekEnd->format('n/j')})";

        return [
            'slug' => $slug,
            'name' => $name,
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'display_order' => $weekNumber,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'config_json' => [
                'todo_level' => 'week',
                'todo_week_start' => $weekStart->toDateString(),
                'todo_week_end' => $weekEnd->toDateString(),
                'todo_year' => $weekStart->year,
                'todo_month' => $weekStart->month,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dayAttributes(IsAnEntityContract $entity, Carbon $date): array
    {
        $weekStart = $this->weekStart($entity, $date);
        $weekNumber = $this->weekNumber($weekStart);
        $slug = "{$date->year}/{$date->format('m')}/week-{$weekNumber}/{$date->day}";
        $name = $date->format('l n/j');

        return [
            'slug' => $slug,
            'name' => $name,
            'icon' => 'IconCalendar',
            'route_path' => $slug,
            'display_order' => $date->day,
            'is_visible' => false,
            'is_required' => false,
            'is_nav_item' => false,
            'config_json' => ['todo_level' => 'day', 'todo_date' => $date->toDateString(), 'todo_year' => $date->year, 'todo_month' => $date->month],
        ];
    }

    public function weekStart(IsAnEntityContract $entity, Carbon $date): Carbon
    {
        $weekStartDay = $this->weekStartDay($entity);

        $current = $date->copy();
        $monthStart = $date->copy()->startOfMonth();

        while ($current->dayOfWeek !== $weekStartDay) {
            $current->subDay();
        }

        if ($current->lt($monthStart)) {
            $current = $monthStart->copy();
        }

        return $current->startOfDay();
    }

    public function weekEnd(IsAnEntityContract $entity, Carbon $weekStart): Carbon
    {
        $weekStartDay = $this->weekStartDay($entity);

        $endDay = ($weekStartDay + 6) % 7;
        $current = $weekStart->copy();
        while ($current->dayOfWeek !== $endDay) {
            $current->addDay();
        }

        $monthEnd = $weekStart->copy()->endOfMonth()->startOfDay();
        if ($current->gt($monthEnd)) {
            $current = $monthEnd;
        }

        return $current->startOfDay();
    }

    public function weekNumber(Carbon $weekStart): int
    {
        if ($weekStart->day === 1) {
            return 1;
        }

        $count = 1;
        $monthStart = $weekStart->copy()->startOfMonth();
        $current = $monthStart->copy()->addDay();

        while ($current->lte($weekStart)) {
            if ($current->dayOfWeek === 0) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    protected function weekStartDay(IsAnEntityContract $entity): int
    {
        $settings = $this->settingRepository->findAll([
            ['user_id', '=', $entity->getKey()],
        ])->first();

        return $settings ? (int) $settings->week_start_day : 0;
    }
}
