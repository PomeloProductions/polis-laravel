<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\Contact;
use App\Models\User\User;
use Polis\Policies\BasePolicyAbstract;

abstract class ContactPolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $loggedInUser, User $requestedUser)
    {
        return $loggedInUser->id == $requestedUser->id;
    }

    /**
     * @return bool
     */
    public function create(User $loggedInUser, User $requestedUser)
    {
        return $loggedInUser->id == $requestedUser->id;
    }

    /**
     * @return bool
     */
    public function update(User $loggedInUser, User $requestedUser, Contact $contact)
    {
        return $loggedInUser->id == $requestedUser->id &&
            ($requestedUser->id == $contact->initiated_by_id || $requestedUser->id == $contact->requested_id);
    }

    /**
     * @return bool
     */
    public function delete(User $loggedInUser, User $requestedUser, Contact $contact)
    {
        return $loggedInUser->id == $requestedUser->id &&
            ($requestedUser->id == $contact->initiated_by_id || $requestedUser->id == $contact->requested_id);
    }
}
