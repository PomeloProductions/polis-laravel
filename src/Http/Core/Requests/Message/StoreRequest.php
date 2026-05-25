<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Message;

use App\Models\Messaging\Message;
use Polis\Http\Core\Requests\BaseUnauthenticatedRequest;
use Polis\Http\Core\Requests\Traits\HasNoExpands;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseUnauthenticatedRequest
{
    use HasNoExpands;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(Message $message)
    {
        return $message->getValidationRules(Message::VALIDATION_RULES_CREATE);
    }
}
