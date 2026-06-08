<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Messaging\Thread.
 *
 * MessagePolicyAbstract / ThreadPolicyAbstract type-hint this on their
 * gate signatures and read $subject_type to look up the relevant
 * subject-gate provider.
 */
class Thread
{
    public ?int $id = null;

    public ?string $subject_type = null;

    public ?int $subject_id = null;
}

if (! class_exists(\App\Models\Messaging\Thread::class, false)) {
    class_alias(
        Thread::class,
        \App\Models\Messaging\Thread::class,
    );
}
