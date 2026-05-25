<?php

declare(strict_types=1);

namespace Polis\Policies\Messaging;

use App\Models\Messaging\Message;
use App\Models\Messaging\Thread;
use App\Models\User\User;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateProviderContract;
use Polis\Policies\BasePolicyAbstract;

abstract class MessagePolicyAbstract extends BasePolicyAbstract
{
    /**
     * @var ThreadSubjectGateProviderContract
     */
    private $provider;

    public function __construct(ThreadSubjectGateProviderContract $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return bool
     */
    public function all(User $loggedInUser, User $requestedUser, Thread $thread)
    {
        $gate = $this->provider->createGate($thread->subject_type);

        if ($gate == null) {
            return false;
        }

        return $loggedInUser->id == $requestedUser->id && $gate->authorizeThread($loggedInUser, $thread);
    }

    /**
     * @return bool
     */
    public function create(User $loggedInUser, User $requestedUser, Thread $thread)
    {
        $gate = $this->provider->createGate($thread->subject_type);

        if ($gate == null) {
            return false;
        }

        return $loggedInUser->id == $requestedUser->id && $gate->authorizeThread($loggedInUser, $thread);
    }

    /**
     * @return bool
     */
    public function update(User $loggedInUser, User $requestedUser, Thread $thread, Message $message)
    {
        $gate = $this->provider->createGate($thread->subject_type);

        if ($gate == null) {
            return false;
        }

        return $loggedInUser->id == $requestedUser->id && $gate->authorizeThread($loggedInUser, $thread) &&
            $thread->id == $message->thread_id && $message->to_id == $loggedInUser->id;
    }
}
