<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Traits;

/**
 * Trait HasNoExpands
 */
trait HasNoExpands
{
    /**
     * No expands allowed when using this trait
     */
    public function allowedExpands(): array
    {
        return [];
    }
}
