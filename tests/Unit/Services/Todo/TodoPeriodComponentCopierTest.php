<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Services\Todo;

use Illuminate\Support\Carbon;
use Mockery;
use Polis\Contracts\Repositories\User\UserPageComponentRepositoryContract;
use Polis\Services\Todo\TodoPeriodComponentCopier;
use Polis\Services\Todo\TodoTaskTreeService;
use Polis\Tests\TestCase;

/**
 * Exercises the config_json carry-forward rules (the non-todo_task path), which
 * are pure array transforms and need no database.
 */
final class TodoPeriodComponentCopierTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function copier(): TodoPeriodComponentCopier
    {
        return new TodoPeriodComponentCopier(
            Mockery::mock(UserPageComponentRepositoryContract::class),
            Mockery::mock(TodoTaskTreeService::class),
        );
    }

    public function test_top_level_tally_increments_on_scheduled_day(): void
    {
        $out = $this->copier()->applyCopyRules(
            ['tally' => 2, 'on_copy' => 'increment'],
            Carbon::parse('2026-06-17'),
        );

        $this->assertEquals(3, $out['tally']);
    }

    public function test_top_level_tally_preserved_when_not_scheduled(): void
    {
        // schedule only Mondays (1); target date is a Wednesday → not scheduled → unchanged
        $out = $this->copier()->applyCopyRules(
            ['tally' => 2, 'on_copy' => 'increment', 'schedule' => [1]],
            Carbon::parse('2026-06-17'),
        );

        $this->assertEquals(2, $out['tally']);
    }

    public function test_item_completion_resets_and_reset_rule_zeroes_tally(): void
    {
        $out = $this->copier()->applyCopyRules([
            'items' => [
                ['tally' => 4, 'on_copy' => 'reset', 'completed' => true],
                ['tally' => 4, 'on_copy' => 'preserve', 'completed' => true],
            ],
        ]);

        $this->assertEquals(0, $out['items'][0]['tally']);
        $this->assertFalse($out['items'][0]['completed']);
        $this->assertEquals(4, $out['items'][1]['tally']);
        $this->assertFalse($out['items'][1]['completed']);
    }

    public function test_projects_accumulate_deficit_and_reset_logged(): void
    {
        $out = $this->copier()->applyCopyRules([
            'projects' => [
                ['deficit' => 1.0, 'budgeted_hours' => 3.0, 'logged_hours' => 1.0],
            ],
        ]);

        // deficit = 1 + (3 - 1) = 3, logged reset to 0
        $this->assertEqualsWithDelta(3.0, $out['projects'][0]['deficit'], 0.0001);
        $this->assertEquals(0, $out['projects'][0]['logged_hours']);
    }

    public function test_group_count_defaults_to_preserve(): void
    {
        $out = $this->copier()->applyCopyRules([
            'groups' => [
                ['count_this_group' => 7],
            ],
        ]);

        $this->assertEquals(7, $out['groups'][0]['count_this_group']);
    }

    public function test_logged_time_resets(): void
    {
        $out = $this->copier()->applyCopyRules(['logged_time' => 42]);

        $this->assertEquals(0, $out['logged_time']);
    }
}
