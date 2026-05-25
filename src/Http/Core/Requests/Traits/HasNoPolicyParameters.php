<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Traits;

/**
 * Trait HasNoPolicyParameters
 */
trait HasNoPolicyParameters
{
    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [];
    }
}
