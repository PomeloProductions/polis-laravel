<?php

declare(strict_types=1);

namespace Polis\ThreadSecurity;

use Illuminate\Contracts\Foundation\Application;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateContract;
use Polis\Contracts\ThreadSecurity\ThreadSubjectGateProviderContract;

/**
 * Class ThreadSubjectGateProvider
 *
 * Resolves a {@see ThreadSubjectGateContract} for a given thread subject type.
 * Consumers extend this class (and rebind the container binding in
 * {@see \Polis\Providers\BaseAuthServiceProvider}) to add application-specific
 * subject types beyond the package defaults (`general`, `private_message`).
 */
class ThreadSubjectGateProvider implements ThreadSubjectGateProviderContract
{
    /**
     * @var Application
     */
    private $app;

    /**
     * ThreadSubjectGateProvider constructor.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Creates the gate for the passed in subject type
     */
    public function createGate($subjectType): ?ThreadSubjectGateContract
    {
        switch ($subjectType) {
            case 'general':
                return new GeneralThreadGate;
            case 'private_message':
                return new PrivateThreadGate;

                // put application level gates below
        }

        return null;
    }
}
