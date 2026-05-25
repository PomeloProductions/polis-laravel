<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\CollectionItem;

use App\Models\Collection\CollectionItem;
use App\Policies\Collection\CollectionItemPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class DeleteRequest
 */
class DeleteRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return CollectionItemPolicy::ACTION_DELETE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return CollectionItem::class;
    }

    protected function getPolicyParameters(): array
    {
        return [
            $this->route('collection_item')->collection,
            $this->route('collection_item'),
        ];
    }
}
