<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\User.
 *
 * Polis contracts type-hint App\Models\* concrete classes in their method
 * signatures (e.g. MessageRepositoryContract::sendEmailToUser(User $user)).
 * In a consumer application those classes are provided by the consumer; in
 * this package's standalone Testbench harness they don't exist, which
 * blocks Mockery from proxying the contracts. This fixture provides a
 * minimal class at the expected FQCN so the type hints resolve.
 *
 * Extends BaseModelAbstract so repository tests that pass User instances
 * to methods typed `BaseModelAbstract` (e.g. UserRepository::update,
 * MessageRepository::create's $relatedModel) succeed without a
 * multi-class Mockery hack. The real consumer-app User does extend
 * BaseModelAbstract via Authenticatable, so this matches the production
 * shape.
 *
 * Mixes in MockeryFriendlyAttributesTrait so legacy policy/validator tests'
 * `$mock->id = 5` patterns continue to work without triggering Eloquent's
 * setAttribute() on Mockery type-mocks. See the trait for details.
 */
class User extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;

    protected $guarded = [];
}

if (! class_exists(\App\Models\User\User::class, false)) {
    class_alias(
        User::class,
        \App\Models\User\User::class,
    );
}
