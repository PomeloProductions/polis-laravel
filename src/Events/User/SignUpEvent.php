<?php

declare(strict_types=1);

namespace Polis\Events\User;

use App\Models\User\User;

/**
 * Class SignUpEvent
 */
class SignUpEvent
{
    /**
     * @var User
     */
    private $user;

    /**
     * SignUpEvent constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
