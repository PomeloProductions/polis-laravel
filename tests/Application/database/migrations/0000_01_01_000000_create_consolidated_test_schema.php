<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Harness-only consolidated schema.
 *
 * This migration is NOT part of the consumer application's real migration
 * history. It exists solely for the polis-laravel package test harness, where
 * an in-memory SQLite database is booted fresh for every test.
 *
 * Its timestamp (0000_01_01_000000) sorts before every other migration, so it
 * runs FIRST and stands up the entire end-state schema in one pass. It is the
 * fold of all 67 consumer migrations (create + alter) into clean, final-shape
 * `Schema::create` calls — no ->change(), no dropColumn, no data backfills.
 *
 * Deliberately OMITTED here because the package's own migrations (which run
 * AFTER this file) add them and would otherwise collide:
 *   - articles.key / articles.organization_id      (package 2026_05_30_000001)
 *   - articles.owner_id / articles.owner_type       (package 2026_08_06_000001)
 *   - user_pages.owner_id / user_pages.owner_type   (package 2026_08_06_000002)
 *   - article_notes.owner_id / owner_type           (package 2026_08_06_000003)
 *   - external_account_connections table            (package 2026_06_08_000001)
 *   - sources table                                 (package 2026_06_19_000001)
 *
 * Foreign-key constraints are mostly omitted; SQLite does not need them for the
 * ORM under test and omitting them avoids create-order fragility. Plain columns
 * and indexes are preserved.
 *
 * Table-name / column notes reflecting the FOLDED end state:
 *   - `iterations` (cusco) was renamed to `article_iterations` (2021_08_08);
 *     the ArticleIteration model resolves to `article_iterations`, so that is
 *     the name used here. Its FK column to versions is `article_iteration_id`.
 *   - `ballot_subjects` (cusco) was renamed to `ballot_items` (2020_12_12).
 *   - `todo_rotating_items` was DROPPED (2026_04_16) and is therefore NOT
 *     created; `todo_task_nodes` instead gained `todo_rotating_group_id`.
 *   - The Todo tables below are the fold of the consumer's todo/time-entry
 *     migrations (creates + alters through 2026_07_16): balance/node decimals
 *     carry the widened precisions (12,4 / 10,4), `last_date` is varchar(30)
 *     (2026_04_14 expanded it to hold ISO timestamps), `todo_balance_logs` is
 *     in its post-morph shape (source_type/source_id; time_entry_id dropped),
 *     and `todo_task_nodes.count_this_group` is a nullable SIGNED integer
 *     (cascade resets legitimately drive counts negative).
 *   - `todo_templates`, `active_timers` and `check_offs` are intentionally
 *     omitted: no harness test touches them.
 */
return new class extends Migration
{
    public function up(): void
    {
        // NOTE: All consumer tables required by the test factories and suites
        // are reconstructed below in their folded end state (todo_templates,
        // active_timers and check_offs are the only omissions — see header).

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 32);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('merged_to_id')->nullable();
            $table->string('stripe_customer_key', 120)->nullable();
            // email unique index is dropped by 2021_04_19 (system users share
            // the same address), so it is intentionally NOT unique here.
            $table->string('email', 120);
            // name -> first_name (2020_07_23), made nullable (2020_03_03)
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password', 255)->nullable();
            // 2019_11_27 profile fields
            $table->boolean('allow_users_to_add_me')->default(true);
            $table->boolean('receive_push_notifications')->default(true);
            // 2026_04_26
            $table->string('time_format', 3)->default('12h');
            $table->text('about_me')->nullable();
            $table->string('push_notification_key', 512)->nullable();
            // 2020_03_21
            $table->unsignedInteger('profile_image_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->integer('role_id')->unsigned()->index('role_user_role_id_foreign');
            $table->integer('user_id')->unsigned()->index('role_user_user_id_foreign');
            $table->timestamps();
        });

        Schema::create('password_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('token', 40);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 120);
            $table->unsignedInteger('profile_image_id')->nullable();
            $table->string('stripe_customer_key')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('organization_managers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('organization_id');
            $table->unsignedInteger('role_id');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ballots', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 128)->nullable();
            $table->string('type');
            $table->softDeletes();
            $table->timestamps();
        });

        // ballot_subjects renamed to ballot_items (2020_12_12); subject_id/type/
        // vote_count dropped, name added (2020_12_29)
        Schema::create('ballot_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ballot_id');
            $table->integer('votes_cast')->default(0);
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ballot_item_options', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ballot_item_id');
            $table->integer('vote_count')->default(0);
            $table->integer('subject_id');
            $table->string('subject_type');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('ballot_completions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ballot_id');
            $table->unsignedInteger('user_id');
            $table->softDeletes();
            $table->timestamps();
        });

        // votes.ballot_subject_id renamed to ballot_item_option_id (2020_12_12)
        Schema::create('votes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ballot_item_option_id');
            $table->unsignedInteger('ballot_completion_id');
            $table->integer('result')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 40);
            $table->string('duration', 20);
            // 2020_11_04
            $table->text('description')->nullable();
            $table->string('entity_type')->default('user');
            $table->boolean('default')->default(false);
            // 2020_12_02
            $table->integer('trial_period')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('membership_plan_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('membership_plan_id');
            $table->float('cost');
            $table->boolean('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->string('owner_type', 20)->default('user');
            $table->string('payment_method_key', 120)->nullable();
            $table->string('payment_method_type', 20);
            $table->string('identifier', 20)->nullable();
            // 2020_11_24
            $table->boolean('default')->default(false);
            $table->string('brand')->nullable();
            // 2020_12_01
            $table->string('exp_month', 2)->nullable();
            $table->string('exp_year', 4)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('membership_plan_rate_id');
            $table->unsignedInteger('payment_method_id');
            $table->unsignedInteger('subscriber_id');
            $table->string('subscriber_type', 20)->default('user');
            $table->timestamp('last_renewed_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->boolean('recurring')->default(false);
            // 2020_12_02
            $table->boolean('is_trial')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        // subscription_id column dropped (2020_03_16); owner_id/type added (2020_08_07)
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payment_method_id');
            $table->float('amount');
            $table->string('transaction_key', 120)->nullable();
            $table->dateTime('refunded_at')->nullable();
            $table->unsignedInteger('owner_id')->nullable();
            $table->string('owner_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('line_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('payment_id');
            $table->unsignedInteger('item_id')->nullable();
            $table->string('item_type', 20);
            $table->float('amount');
            $table->softDeletes();
            $table->timestamps();
        });

        // assets: user_id renamed to owner_id + owner_type morph (2020_05_23);
        // source (2024_05_13); alt/width/height (2024_06_20)
        Schema::create('assets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id')->nullable();
            $table->string('owner_type')->default('user')->nullable();
            $table->text('name')->nullable();
            $table->text('caption')->nullable();
            $table->string('url', 120);
            $table->string('source')->nullable();
            $table->string('alt')->nullable();
            $table->integer('width')->default(0);
            $table->integer('height')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // articles: has_full_modification_history (2021_08_08); url/authors
        // (2025_11_23). key/organization_id/owner_* intentionally OMITTED (added
        // by package migrations).
        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('created_by_id');
            $table->string('title', 120);
            $table->string('url')->nullable();
            $table->text('authors')->nullable();
            $table->boolean('has_full_modification_history')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('article_modifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('article_id');
            $table->string('action');
            $table->integer('start_position')->default(0);
            $table->integer('length')->nullable();
            $table->string('content')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // cusco `iterations`, renamed to `article_iterations` (2021_08_08);
        // gained article_modification_id.
        Schema::create('article_iterations', function (Blueprint $table) {
            $table->increments('id');
            $table->text('content');
            $table->unsignedInteger('created_by_id');
            $table->unsignedInteger('article_id');
            $table->unsignedInteger('article_modification_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // article_versions: iteration_id renamed to article_iteration_id (2021_08_08)
        Schema::create('article_versions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('article_id');
            $table->unsignedInteger('article_iteration_id');
            $table->string('name', 20)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('article_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('article_id');
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();
        });

        // article_notes: owner_id/owner_type intentionally OMITTED (package migration)
        Schema::create('article_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('article_id');
            $table->timestamp('completed_at')->nullable();
            $table->text('response')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'article_id']);
        });

        Schema::create('threads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('topic', 120)->nullable();
            $table->unsignedInteger('subject_id')->nullable();
            $table->string('subject_type', 20)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('thread_user', function (Blueprint $table) {
            $table->unsignedInteger('thread_id');
            $table->unsignedInteger('user_id');
            $table->primary(['thread_id', 'user_id']);
        });

        // messages: reply_to_* (2020_11_21); to_type/from_type (2024_04_16)
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 120)->nullable();
            $table->string('subject', 256)->nullable();
            $table->string('template', 32)->nullable();
            $table->json('data');
            $table->unsignedInteger('to_id')->nullable();
            $table->string('to_type')->nullable();
            $table->unsignedInteger('from_id')->nullable();
            $table->string('from_type')->nullable();
            $table->unsignedInteger('thread_id')->nullable();
            $table->json('via')->nullable();
            $table->string('action', 128)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->dateTime('seen_at')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->string('reply_to_name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->increments('id');
            $table->text('content');
            $table->unsignedInteger('resource_id');
            $table->string('resource_type', 20);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('initiated_by_id');
            $table->unsignedInteger('requested_id');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('denied_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // categories: parent_id + color tree shape (2026_05_13)
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 16)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('parent_id');
        });

        Schema::create('article_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('article_id');
            $table->unsignedInteger('category_id');
            $table->float('relevance')->default(1.0);
            $table->timestamps();
            $table->unique(['article_id', 'category_id']);
        });

        Schema::create('features', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('feature_membership_plan', function (Blueprint $table) {
            $table->unsignedInteger('feature_id');
            $table->unsignedInteger('membership_plan_id');
            $table->primary(['feature_id', 'membership_plan_id']);
        });

        Schema::create('invitation_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token', 40)->unique();
            $table->unsignedInteger('role_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->string('owner_type');
            $table->string('name')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('collection_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('item_id');
            $table->string('item_type');
            $table->unsignedInteger('collection_id');
            $table->unsignedInteger('order');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('collection_item_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('collection_item_id');
            $table->unsignedInteger('category_id');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['collection_item_id', 'category_id'], 'cic_unique');
            $table->index('category_id');
        });

        Schema::create('collection_item_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('collection_item_id');
            $table->string('meta_key', 64);
            $table->text('meta_value')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['collection_item_id', 'meta_key'], 'cim_unique');
            $table->index('meta_key');
        });

        Schema::create('push_notification_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('owner_id');
            $table->string('owner_type');
            $table->string('push_notification_key');
            $table->timestamps();
            $table->softDeletes();
        });

        // -----------------------------------------------------------------
        // Statistics module (2025_04_30)
        // -----------------------------------------------------------------
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model');
            $table->string('relation');
            $table->boolean('public')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('statistic_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('statistic_id');
            $table->string('field');
            $table->string('operator');
            $table->string('value')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('target_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('statistic_id');
            $table->morphs('target');
            $table->json('result')->nullable();
            $table->float('value')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // -----------------------------------------------------------------
        // User pages (2026_03_28). owner_id/owner_type OMITTED (package migration)
        // -----------------------------------------------------------------
        Schema::create('user_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('slug', 50);
            $table->string('name', 100);
            $table->string('icon', 50)->default('IconList');
            $table->string('color', 7)->nullable();
            $table->string('route_path', 100);
            $table->string('page_type', 30);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_nav_item')->default(true);
            $table->unsignedInteger('parent_page_id')->nullable();
            $table->json('config_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'slug']);
        });

        Schema::create('user_page_components', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_page_id');
            $table->string('component_type', 50);
            $table->unsignedInteger('display_order')->default(0);
            $table->json('config_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // -----------------------------------------------------------------
        // Todo module (2026_03_29 .. 2026_07_16, folded)
        // -----------------------------------------------------------------

        // todo_settings: timezone added (2026_04_15)
        Schema::create('todo_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->tinyInteger('week_start_day')->unsigned()->default(0);
            $table->string('timezone', 50)->default('UTC');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id']);
        });

        // timer_sessions (2026_05_30). status: 'active' or 'completed'.
        Schema::create('timer_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('component_id')->nullable();
            $table->string('item_id', 255)->nullable();
            $table->string('label', 255);
            $table->unsignedInteger('session_budget_seconds')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'item_id', 'status']);
        });

        // time_entries: timer_session_id (2026_05_30), todo_balance_id
        // (2026_04_27), session_elapsed_seconds (2026_05_10)
        Schema::create('time_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('timer_session_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('label', 255);
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('component_id')->nullable();
            $table->string('item_id', 255)->nullable();
            $table->decimal('budget_hours', 8, 2)->nullable();
            $table->decimal('session_budget_hours', 8, 2)->nullable();
            $table->unsignedInteger('todo_balance_id')->nullable();
            $table->unsignedInteger('session_elapsed_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('color', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'started_at']);
        });

        // todo_balances: precision widened to (12,4)/(10,4) (2026_04_14)
        Schema::create('todo_balances', function (Blueprint $table) {
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

        // todo_balance_logs: post-morph shape (2026_04_09_000003 replaced
        // time_entry_id with source_type/source_id); precision (2026_04_14)
        Schema::create('todo_balance_logs', function (Blueprint $table) {
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
            $table->index(['user_id', 'occurred_on']);
            $table->index(['source_type', 'source_id']);
        });

        // todo_task_nodes: cascade_ratio (2026_04_13), last_date varchar(30)
        // (2026_04_14), todo_rotating_group_id (2026_04_16), tally_step default
        // 0 (2026_05_10) then back to 1 with (10,4) precision (2026_06_22),
        // show_checkmark (2026_05_25), tally/time_budget_hours precision
        // (2026_06_22), count_this_group nullable SIGNED int (2026_07_16)
        Schema::create('todo_task_nodes', function (Blueprint $table) {
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
            $table->index('user_page_component_id');
            $table->index('parent_id');
            $table->index('todo_balance_id');
            $table->index('todo_rotating_group_id');
        });

        // todo_rotating_groups: parent_group_id dropped (2026_04_16), last_date
        // varchar(30) (2026_04_14), cascade_ratio (2026_04_15)
        Schema::create('todo_rotating_groups', function (Blueprint $table) {
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

        Schema::create('todo_sub_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('todo_task_node_id');
            $table->string('client_id', 50);
            $table->string('text', 500);
            $table->boolean('completed')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index('todo_task_node_id');
        });

        // todo_calendars: active_on_vacation (2026_06_30)
        Schema::create('todo_calendars', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name', 100);
            $table->json('days_of_week')->nullable();
            $table->json('specific_dates')->nullable();
            $table->boolean('is_exclusion')->default(false);
            $table->boolean('active_on_vacation')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id']);
        });

        // No timestamps/softDeletes on this pivot (matches the consumer create).
        Schema::create('todo_node_calendars', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('todo_task_node_id');
            $table->unsignedInteger('todo_calendar_id');
            $table->string('mode', 10)->default('add');
            $table->unsignedInteger('sort_order')->default(0);
        });

        // [start_date, end_date] inclusive; null end_date = ongoing vacation.
        Schema::create('todo_vacation_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id']);
        });

        // -----------------------------------------------------------------
        // Framework / infra tables
        // -----------------------------------------------------------------
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('websockets_statistics_entries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('app_id');
            $table->integer('peak_connection_count');
            $table->integer('websocket_message_count');
            $table->integer('api_message_count');
            $table->nullableTimestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websockets_statistics_entries');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('todo_vacation_periods');
        Schema::dropIfExists('todo_node_calendars');
        Schema::dropIfExists('todo_calendars');
        Schema::dropIfExists('todo_sub_items');
        Schema::dropIfExists('todo_rotating_groups');
        Schema::dropIfExists('todo_task_nodes');
        Schema::dropIfExists('todo_balance_logs');
        Schema::dropIfExists('todo_balances');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('timer_sessions');
        Schema::dropIfExists('todo_settings');
        Schema::dropIfExists('user_page_components');
        Schema::dropIfExists('user_pages');
        Schema::dropIfExists('target_statistics');
        Schema::dropIfExists('statistic_filters');
        Schema::dropIfExists('statistics');
        Schema::dropIfExists('push_notification_keys');
        Schema::dropIfExists('collection_item_meta');
        Schema::dropIfExists('collection_item_categories');
        Schema::dropIfExists('collection_items');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('invitation_tokens');
        Schema::dropIfExists('feature_membership_plan');
        Schema::dropIfExists('features');
        Schema::dropIfExists('article_category');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('thread_user');
        Schema::dropIfExists('threads');
        Schema::dropIfExists('article_notes');
        Schema::dropIfExists('article_summaries');
        Schema::dropIfExists('article_versions');
        Schema::dropIfExists('article_iterations');
        Schema::dropIfExists('article_modifications');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('line_items');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('membership_plan_rates');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('ballot_completions');
        Schema::dropIfExists('ballot_item_options');
        Schema::dropIfExists('ballot_items');
        Schema::dropIfExists('ballots');
        Schema::dropIfExists('organization_managers');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('password_tokens');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
