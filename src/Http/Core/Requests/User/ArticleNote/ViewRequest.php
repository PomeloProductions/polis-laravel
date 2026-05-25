<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\ArticleNote;

use App\Models\User\ArticleNote;
use App\Policies\User\ArticleNotePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class ViewRequest
 */
class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands, HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ArticleNotePolicy::ACTION_VIEW;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return ArticleNote::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('user'),
            $this->route('article_note'),
        ];
    }
}
