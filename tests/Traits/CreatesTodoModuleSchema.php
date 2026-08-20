<?php

declare(strict_types=1);

namespace Polis\Tests\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the Todo-module tables on the in-memory sqlite connection configured
 * by phpunit.xml, for standalone Unit tests that exercise DB-backed Todo code
 * (console commands, jobs) without the consumer-app migration runner.
 *
 * Table shapes mirror tests/Application/database/migrations/
 * 0000_01_01_000000_create_consolidated_test_schema.php — keep them in sync.
 */
trait CreatesTodoModuleSchema
{
    protected function createTodoModuleTables(): void
    {
        Schema::create('todo_settings', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->tinyInteger('week_start_day')->unsigned()->default(0);
            $table->string('timezone', 50)->default('UTC');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id']);
        });

        Schema::create('todo_balances', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('item_key', 255);
            $table->string('tracking_mode', 10)->default('units');
            $table->decimal('balance', 12, 4)->default(0);
            $table->decimal('time_budget_hours', 10, 4)->nullable();
            $table->decimal('tally_step', 10, 4)->default(1);
            $table->json('schedule')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'item_key']);
        });

        Schema::create('todo_balance_logs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('todo_balance_id');
            $table->string('reason', 50);
            $table->decimal('delta', 12, 4);
            $table->decimal('balance_before', 12, 4);
            $table->decimal('balance_after', 12, 4);
            $table->date('occurred_on');
            $table->string('source_type')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->softDeletes();
            $table->index(['todo_balance_id', 'occurred_on']);
        });

        Schema::create('todo_task_nodes', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_page_component_id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('client_id', 50);
            $table->string('task_type', 20);
            $table->string('label', 255)->default('');
            $table->text('description')->nullable();
            $table->boolean('collapsed')->default(false);
            $table->decimal('tally', 12, 4)->nullable();
            $table->decimal('tally_step', 10, 4)->default(1);
            $table->json('schedule')->nullable();
            $table->string('on_copy', 20)->default('increment');
            $table->decimal('time_budget_hours', 10, 4)->nullable();
            $table->decimal('logged_hours', 10, 4)->default(0);
            $table->decimal('logged_time', 10, 4)->default(0);
            $table->decimal('deficit', 10, 4)->default(0);
            $table->string('tracking_mode', 10)->default('units');
            $table->boolean('decrement_on_done')->default(true);
            $table->boolean('show_checkmark')->default(false);
            $table->string('time_tracking_mode', 15)->default('reset');
            $table->unsignedInteger('todo_balance_id')->nullable();
            $table->unsignedInteger('todo_rotating_group_id')->nullable();
            $table->boolean('completed')->default(false);
            $table->string('last_date', 30)->nullable();
            $table->boolean('custom_groups')->default(false);
            $table->unsignedSmallInteger('cascade_ratio')->default(2);
            $table->integer('count_this_group')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('parent_id');
            $table->index('todo_balance_id');
            $table->index('todo_rotating_group_id');
        });

        Schema::create('todo_rotating_groups', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('todo_task_node_id');
            $table->unsignedSmallInteger('group_number')->default(0);
            $table->string('label', 255)->nullable();
            $table->integer('count_this_group')->default(0);
            $table->string('on_copy', 20)->default('preserve');
            $table->string('last_date', 30)->nullable();
            $table->boolean('mark_done_on_group')->default(false);
            $table->unsignedSmallInteger('cascade_ratio')->default(2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('todo_task_node_id');
        });

        Schema::create('todo_calendars', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name', 100);
            $table->json('days_of_week')->nullable();
            $table->json('specific_dates')->nullable();
            $table->boolean('is_exclusion')->default(false);
            $table->boolean('active_on_vacation')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_node_calendars', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('todo_task_node_id');
            $table->unsignedInteger('todo_calendar_id');
            $table->string('mode', 10)->default('add');
            $table->unsignedInteger('sort_order')->default(0);
        });

        Schema::create('todo_vacation_periods', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function dropTodoModuleTables(): void
    {
        foreach ([
            'todo_vacation_periods',
            'todo_node_calendars',
            'todo_calendars',
            'todo_rotating_groups',
            'todo_task_nodes',
            'todo_balance_logs',
            'todo_balances',
            'todo_settings',
        ] as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
}
