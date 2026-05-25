<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Collection;

use App\Models\Collection\Collection;
use App\Policies\Collection\CollectionPolicy;
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
        return CollectionPolicy::ACTION_DELETE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return Collection::class;
    }

    protected function getPolicyParameters(): array
    {
        return [
            $this->route('collection'),
        ];
    }
}
