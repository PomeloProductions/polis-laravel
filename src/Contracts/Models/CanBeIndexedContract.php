<?php

declare(strict_types=1);

namespace Polis\Contracts\Models;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Interface CanBeIndexedContract
 *
 * @property resource $resource
 * @property int $id
 */
interface CanBeIndexedContract extends CanBeMorphedToContract
{
    /**
     * Gets the content string to index
     */
    public function getContentString(): ?string;

    /**
     * The resource object for this indexable model
     */
    public function resource(): MorphOne;
}
