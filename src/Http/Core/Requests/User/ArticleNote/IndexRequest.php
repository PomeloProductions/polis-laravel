<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\ArticleNote;

use App\Models\User\ArticleNote;
use App\Policies\User\ArticleNotePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 */
class IndexRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ArticleNotePolicy::ACTION_LIST;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return ArticleNote::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('user'),
        ];
    }

    /**
     * All expands that are allowed for this request
     */
    public function allowedExpands(): array
    {
        return [
            'user',
            'article',
        ];
    }
}
