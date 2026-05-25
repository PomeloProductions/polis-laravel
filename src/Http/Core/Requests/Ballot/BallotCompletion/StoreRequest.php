<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Ballot\BallotCompletion;

use App\Models\Vote\BallotCompletion;
use App\Policies\Vote\BallotCompletionPolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return BallotCompletionPolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return BallotCompletion::class;
    }

    /**
     * Get validation rules for the create request
     */
    public function rules(BallotCompletion $ballotCompletion): array
    {
        return $ballotCompletion->getValidationRules(BallotCompletion::VALIDATION_RULES_CREATE);
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
}
