<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Todo;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\PeriodLadderContract;
use Polis\Contracts\Repositories\User\TodoSettingRepositoryContract;
use Polis\Models\User\TodoSetting;
use Polis\Models\User\UserPage;
use Polis\Services\Todo\TodoPeriodLadder;
use Polis\Tests\TestCase;

final class TodoPeriodLadderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Build a ladder whose settings repo returns no TodoSetting (so week_start_day
     * defaults to 0 / Sunday), and an entity whose key is 1.
     */
    private function ladder(int $weekStartDay = 0): TodoPeriodLadder
    {
        $repo = Mockery::mock(TodoSettingRepositoryContract::class);
        if ($weekStartDay === 0) {
            $repo->shouldReceive('findAll')->andReturn(new Collection);
        } else {
            $setting = new TodoSetting;
            $setting->week_start_day = $weekStartDay;
            $repo->shouldReceive('findAll')->andReturn(new Collection([$setting]));
        }

        return new TodoPeriodLadder($repo);
    }

    private function entity(int $id = 1): IsAnEntityContract
    {
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->shouldReceive('getKey')->andReturn($id);

        return $entity;
    }

    public function test_page_type_and_levels(): void
    {
        $ladder = $this->ladder();

        $this->assertInstanceOf(PeriodLadderContract::class, $ladder);
        $this->assertEquals('todo', $ladder->pageType());
        $this->assertEquals(['year', 'month', 'week', 'day'], $ladder->levels());
        $this->assertEquals(['todo_level' => 'root'], $ladder->rootMatch());
    }

    public function test_owner_filter_and_attributes_map_to_user_id(): void
    {
        $ladder = $this->ladder();
        $entity = $this->entity(42);

        $this->assertEquals([['user_id', '=', 42]], $ladder->ownerFilter($entity));
        $this->assertEquals(['user_id' => 42], $ladder->ownerAttributes($entity));
    }

    public function test_week_start_defaults_to_sunday(): void
    {
        $ladder = $this->ladder(0);
        // 2026-06-17 is a Wednesday; the Sunday-start week begins 2026-06-14.
        $start = $ladder->weekStart($this->entity(), Carbon::parse('2026-06-17'));

        $this->assertEquals('2026-06-14', $start->toDateString());
    }

    public function test_week_start_clamps_to_month_start(): void
    {
        $ladder = $this->ladder(0);
        // 2026-06-02 (Tue): the Sunday would be 2026-05-31, but weeks are clamped
        // to the month boundary, so the week starts 2026-06-01.
        $start = $ladder->weekStart($this->entity(), Carbon::parse('2026-06-02'));

        $this->assertEquals('2026-06-01', $start->toDateString());
    }

    public function test_config_match_for_levels(): void
    {
        $ladder = $this->ladder(0);
        $date = Carbon::parse('2026-06-17');

        $this->assertEquals(['todo_level' => 'year', 'todo_year' => 2026], $ladder->configMatchFor($this->entity(), 'year', $date));
        $this->assertEquals(['todo_level' => 'month', 'todo_month' => 6, 'todo_year' => 2026], $ladder->configMatchFor($this->entity(), 'month', $date));
        $this->assertEquals(['todo_level' => 'day', 'todo_date' => '2026-06-17'], $ladder->configMatchFor($this->entity(), 'day', $date));
    }

    public function test_day_page_attributes_include_owner_free_shape(): void
    {
        $ladder = $this->ladder(0);
        $parent = new UserPage;
        $attrs = $ladder->pageAttributesFor($this->entity(), 'day', Carbon::parse('2026-06-17'), $parent);

        $this->assertEquals('day', $attrs['config_json']['todo_level']);
        $this->assertEquals('2026-06-17', $attrs['config_json']['todo_date']);
        $this->assertArrayHasKey('slug', $attrs);
        $this->assertArrayNotHasKey('user_id', $attrs, 'owner attributes are injected by the engine, not the ladder page attrs');
    }
}
