<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\User\Contact.
 *
 * Extends BaseModelAbstract because ContactRepositoryContract::update()
 * type-hints BaseModelAbstract. See Category.php for the shared rationale.
 */
class Contact extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\User\Contact::class, false)) {
    class_alias(
        Contact::class,
        \App\Models\User\Contact::class,
    );
}
