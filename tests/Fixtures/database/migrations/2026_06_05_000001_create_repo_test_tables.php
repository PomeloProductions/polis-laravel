<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test-only schema for repository fixture models.
 *
 * Provides the minimal tables required by tests under
 * tests/Unit/Repositories/* that exercise repository code paths against
 * real Eloquent/sqlite (not Mockery) — the BaseRepositoryAbstract create
 * branches and findAll where-clause variants in particular.
 *
 * The tables are intentionally tiny: a parent model, a child model, and
 * a BelongsToMany pivot. Tests can use them with the corresponding
 * fixture model classes in tests/Fixtures/Repository/*.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repo_parent_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repo_child_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('repo_parent_model_id')->nullable();
            $table->string('label')->nullable();
            $table->string('extra')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repo_has_one_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('repo_parent_model_id')->nullable();
            $table->string('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repo_belongs_to_many_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tag')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('repo_parent_repo_belongs_to_many', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('repo_parent_model_id');
            $table->unsignedBigInteger('repo_belongs_to_many_model_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repo_parent_repo_belongs_to_many');
        Schema::dropIfExists('repo_belongs_to_many_models');
        Schema::dropIfExists('repo_has_one_models');
        Schema::dropIfExists('repo_child_models');
        Schema::dropIfExists('repo_parent_models');
    }
};
