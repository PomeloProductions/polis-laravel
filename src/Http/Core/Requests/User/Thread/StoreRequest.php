<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\Thread;

use App\Models\Messaging\Thread;
use App\Policies\Messaging\ThreadPolicy;
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
        return ThreadPolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Thread::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        $subjectType = $this->input('subject_type', '');
        $subjectId = $this->input('subject_id', null);

        return [
            $this->route('user'),
            $subjectType,
            $subjectId,
        ];
    }

    /**
     * The rules for the request
     *
     * @return array
     */
    public function rules(Thread $thread)
    {
        return $thread->getValidationRules(Thread::VALIDATION_RULES_CREATE);
    }
}
