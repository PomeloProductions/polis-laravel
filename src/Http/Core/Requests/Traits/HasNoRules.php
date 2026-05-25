<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Traits;

/**
 * Trait HasNoRules
 */
trait HasNoRules
{
    /**
     * Default Rules
     */
    public function rules(): array
    {
        return [];
    }
}
