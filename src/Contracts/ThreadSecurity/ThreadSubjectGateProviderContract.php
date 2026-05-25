<?php

declare(strict_types=1);

namespace Polis\Contracts\ThreadSecurity;

/**
 * Interface ThreadSubjectGateProviderContract
 */
interface ThreadSubjectGateProviderContract
{
    /**
     * Creates the gate for the passed in subject type
     */
    public function createGate($subjectType): ?ThreadSubjectGateContract;
}
