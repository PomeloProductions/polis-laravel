<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\User\Thread\Message;

use App\Models\Messaging\Message;
use App\Policies\Messaging\MessagePolicy;
use Polis\Http\Core\Requests\BaseAuthenticatedRequestAbstract;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class UpdateRequest
 */
class UpdateRequest extends BaseAuthenticatedRequestAbstract
{
    use HasNoExpands;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return MessagePolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return Message::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('user'),
            $this->route('thread'),
            $this->route('message'),
        ];
    }

    /**
     * The rules for the request
     *
     * @return array
     */
    public function rules(Message $message)
    {
        return $message->getValidationRules(Message::VALIDATION_RULES_UPDATE);
    }
}
