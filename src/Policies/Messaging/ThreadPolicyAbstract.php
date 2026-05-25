<?php

declare(strict_types=1);

namespace Polis\Policies\Messaging;

use App\Models\User\User;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateProviderContract;
use Polis\Policies\BasePolicyAbstract;

abstract class ThreadPolicyAbstract extends BasePolicyAbstract
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
     * @param  null|int  $subjectId
     * @return bool
     */
    public function all(User $loggedInUser, User $requestedUser, string $threadSubject, $subjectId = null)
    {
        $gate = $this->provider->createGate($threadSubject);

        if ($gate == null) {
            return false;
        }

        return $loggedInUser->id == $requestedUser->id && $gate->authorizeSubject($loggedInUser, $subjectId);
    }

    /**
     * @param  null  $subjectId
     * @return bool
     */
    public function create(User $loggedInUser, User $requestedUser, string $threadSubject, $subjectId = null)
    {
        $gate = $this->provider->createGate($threadSubject);

        if ($gate == null) {
            return false;
        }

        return $loggedInUser->id == $requestedUser->id && $gate->authorizeSubject($loggedInUser, $subjectId);
    }
}
