<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\User\Contact.
 *
 * ContactPolicyAbstract reads initiated_by_id / requested_id to validate
 * either party owns the contact relationship.
 */
class Contact
{
    public ?int $id = null;

    public ?int $initiated_by_id = null;

    public ?int $requested_id = null;
}

if (! class_exists(\App\Models\User\Contact::class, false)) {
    class_alias(
        Contact::class,
        \App\Models\User\Contact::class,
    );
}
