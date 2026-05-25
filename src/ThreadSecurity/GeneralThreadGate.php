<?php

declare(strict_types=1);

namespace Polis\ThreadSecurity;

use App\Models\Messaging\Thread;
use App\Models\User\User;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateContract;

/**
 * Class GeneralThreadGate
 */
class GeneralThreadGate implements ThreadSubjectGateContract
{
    /**
     * Authorizes the passed in user to make sure that they can access the thread subject
     * The optional id passed in if we are authorizing a specific subject id
     *
     * @param  null  $id
     */
    public function authorizeSubject(User $user, $id = null): bool
    {
        return true;
    }

    /**
     * Authorizes that a user can post to a specific thread
     */
    public function authorizeThread(User $user, Thread $thread): bool
    {
        return true;
    }
}
