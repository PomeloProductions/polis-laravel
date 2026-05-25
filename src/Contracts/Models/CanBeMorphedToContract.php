<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

/**
 * Interface CanBeMorphedTo
 */
interface CanBeMorphedToContract
{
    /**
     * The name of the morph relation
     */
    public function morphRelationName(): string;
}
