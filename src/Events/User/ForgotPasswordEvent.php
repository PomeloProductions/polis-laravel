<?php

declare(strict_types=1);

namespace Polis\Events\User;

use App\Models\User\PasswordToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class ForgotPasswordEvent
 */
class ForgotPasswordEvent implements ShouldQueue
{
    use Queueable;

    /**
     * @var PasswordToken
     */
    private $passwordToken;

    /**
     * ForgotPasswordEvent constructor.
     */
    public function __construct(PasswordToken $passwordToken)
    {
        $this->passwordToken = $passwordToken;
    }

    public function getPasswordToken(): PasswordToken
    {
        return $this->passwordToken;
    }
}
