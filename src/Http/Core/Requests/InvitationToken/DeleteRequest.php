<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\InvitationToken;

use App\Models\User\InvitationToken;
use App\Policies\User\InvitationTokenPolicy;
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
        return InvitationTokenPolicy::ACTION_DELETE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPolicyModel(): string
    {
        return InvitationToken::class;
    }

    protected function getPolicyParameters(): array
    {
        return [
            $this->route('invitation_token'),
        ];
    }
}
