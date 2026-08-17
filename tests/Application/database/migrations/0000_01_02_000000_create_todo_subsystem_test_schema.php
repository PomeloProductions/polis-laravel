<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Todo subsystem test schema.
 *
 * The consolidated test schema (0000_01_01_000000_create_consolidated_test_schema)
 * deliberately did NOT create the Todo/Timer tables — the Todo subsystem lived
 * only in the PolisOS API and had never been routed into the package's dummy
 * consumer app (see the historical "todos/* routes are PolisOS-specific" note
 * in routes/core.php). This migration ports the Todo relational schema into the
 * dummy app so the package's TodoControllerAbstract, TodoTaskTreeService,
 * TodoNodeTreeCodec and NodeTreeService actually execute under HTTP feature
 * tests.
 *
 * Column sets are derived from the models' @property docblocks + $casts under
 * src/Models/User/Todo*.php and src/Models/User/{TimeEntry,TimerSession}.php,
 * plus the columns the controller/services read+write directly. Every table
 * carries the softDeletes + timestamps the models declare (TodoBalanceLog is
 * the exception: it sets UPDATED_AT = null and is append-only, so it gets only
 * created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('week_start_day')->default(0);
            $table->string('timezone', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name', 100);
            $table->string('level', 10);
            $table->json('sections_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('item_key');
            $table->string('tracking_mode', 10)->default('units');
            $table->decimal('balance', 16, 4)->default(0);
            $table->decimal('time_budget_hours', 16, 4)->nullable();
            $table->decimal('tally_step', 16, 4)->default(0);
            $table->json('schedule')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_balance_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('todo_balance_id');
            $table->string('reason', 30);
            $table->decimal('delta', 16, 4)->default(0);
            $table->decimal('balance_before', 16, 4)->default(0);
            $table->decimal('balance_after', 16, 4)->default(0);
            $table->date('occurred_on');
            $table->string('source_type')->nullable();
            $table->unsignedInteger('source_id')->nullable();
            $table->json('meta_json')->nullable();
            // Append-only: model sets UPDATED_AT = null, so only created_at.
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('todo_calendars', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->json('days_of_week')->nullable();
            $table->json('specific_dates')->nullable();
            $table->boolean('is_exclusion')->default(false);
            $table->boolean('active_on_vacation')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_vacation_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_task_nodes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_page_component_id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->unsignedInteger('todo_rotating_group_id')->nullable();
            $table->unsignedInteger('todo_balance_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('client_id', 50);
            $table->string('task_type', 30)->default('line_item');
            $table->string('label')->default('');
            $table->text('description')->nullable();
            $table->boolean('collapsed')->default(false);
            $table->decimal('tally', 16, 2)->nullable();
            $table->decimal('tally_step', 16, 2)->default(0);
            $table->json('schedule')->nullable();
            $table->string('on_copy', 30)->default('preserve');
            $table->decimal('time_budget_hours', 16, 2)->nullable();
            $table->decimal('logged_hours', 16, 4)->default(0);
            $table->decimal('logged_time', 16, 4)->default(0);
            $table->decimal('deficit', 16, 4)->default(0);
            $table->string('tracking_mode', 10)->default('units');
            $table->boolean('decrement_on_done')->default(false);
            $table->string('time_tracking_mode', 20)->default('units');
            $table->boolean('completed')->default(false);
            $table->string('last_date')->nullable();
            $table->boolean('custom_groups')->default(false);
            $table->integer('cascade_ratio')->default(2);
            $table->boolean('show_checkmark')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_rotating_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('todo_task_node_id');
            $table->integer('group_number')->default(1);
            $table->string('label')->nullable();
            $table->integer('count_this_group')->default(0);
            $table->string('on_copy', 30)->default('preserve');
            $table->string('last_date')->nullable();
            $table->boolean('mark_done_on_group')->default(false);
            $table->integer('cascade_ratio')->default(2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_sub_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('todo_task_node_id');
            $table->string('client_id', 50);
            $table->string('text', 500)->default('');
            $table->boolean('completed')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('todo_node_calendars', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('todo_task_node_id');
            $table->unsignedInteger('todo_calendar_id');
            $table->string('mode', 10)->default('add');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('timer_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('component_id')->nullable();
            $table->string('item_id')->nullable();
            $table->string('label')->default('');
            $table->integer('session_budget_seconds')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('timer_session_id')->nullable();
            $table->string('label')->default('');
            $table->text('note')->nullable();
            $table->unsignedInteger('component_id')->nullable();
            $table->string('item_id')->nullable();
            $table->decimal('budget_hours', 16, 2)->nullable();
            $table->decimal('session_budget_hours', 16, 2)->nullable();
            $table->unsignedInteger('todo_balance_id')->nullable();
            $table->integer('session_elapsed_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('timer_sessions');
        Schema::dropIfExists('todo_node_calendars');
        Schema::dropIfExists('todo_sub_items');
        Schema::dropIfExists('todo_rotating_groups');
        Schema::dropIfExists('todo_task_nodes');
        Schema::dropIfExists('todo_vacation_periods');
        Schema::dropIfExists('todo_calendars');
        Schema::dropIfExists('todo_balance_logs');
        Schema::dropIfExists('todo_balances');
        Schema::dropIfExists('todo_templates');
        Schema::dropIfExists('todo_settings');
    }
};
