<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Ballot;

use App\Models\Vote\Ballot;
use App\Policies\Vote\BallotPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoRules;

/**
 * Class IndexRequest
 */
class ViewRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoRules;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return BallotPolicy::ACTION_VIEW;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Ballot::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('ballot'),
        ];
    }

    /**
     * All expands that are allowed for this request
     */
    public function allowedExpands(): array
    {
        return [
            'ballotItems',
            'ballotItems.ballotItemOptions',
            'ballotItems.ballotItemOptions.subject',
        ];
    }
}
