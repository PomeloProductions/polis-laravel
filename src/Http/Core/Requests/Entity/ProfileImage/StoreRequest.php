<?php

declare(strict_types=1);

namespace Polis\Http\Core\Requests\Entity\ProfileImage;

use App\Models\User\ProfileImage;
use App\Policies\User\ProfileImagePolicy;
use Polis\Contracts\Http\HasEntityInRequestContract;
use Polis\Http\Core\Requests\BaseAssetUploadRequestAbstract;
use Polis\Http\Core\Requests\Entity\Traits\IsEntityRequestTrait;

/**
 * Class StoreRequest
 */
class StoreRequest extends BaseAssetUploadRequestAbstract implements HasEntityInRequestContract
{
    use IsEntityRequestTrait;

    /**
     * Get the policy action for the guard
     */
    protected function getPolicyAction(): string
    {
        return ProfileImagePolicy::ACTION_CREATE;
    }

    /**
     * Get the class name of the policy that this request utilizes
     */
    protected function getPolicyModel(): string
    {
        return ProfileImage::class;
    }

    /**
     * Gets any additional parameters needed for the policy function
     */
    protected function getPolicyParameters(): array
    {
        return [
            $this->getEntity(),
        ];
    }

    public function rules(ProfileImage $profileImage): array
    {
        return $profileImage->getValidationRules(ProfileImage::VALIDATION_RULES_CREATE);
    }
}
