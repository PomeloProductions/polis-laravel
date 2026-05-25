<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\InvitationToken;

use App\Models\User\InvitationToken;
use App\Policies\User\InvitationTokenPolicy;
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
        return InvitationTokenPolicy::ACTION_UPDATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return InvitationToken::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->route('invitation_token'),
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'role_id' => 'nullable|integer|exists:roles,id',
        ];
    }
}
