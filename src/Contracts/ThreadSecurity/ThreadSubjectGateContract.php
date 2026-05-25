<?php

declare(strict_types=1);

namespace Polis\Contracts\ThreadSecurity;

use App\Models\Messaging\Thread;
use App\Models\User\User;

/**
 * Interface ThreadSubjectGateContract
 */
interface ThreadSubjectGateContract
{
    /**
     * Authorizes the passed in user to make sure that they can access the thread subject
     * The optional id passed in if we are authorizing a specific subject id
     *
     * @param  null  $id
     */
    public function authorizeSubject(User $user, $id = null): bool;

    /**
     * Authorizes that a user can post to a specific thread
     */
    public function authorizeThread(User $user, Thread $thread): bool;
}
