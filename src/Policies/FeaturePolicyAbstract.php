<?php

declare(strict_types=1);

namespace Polis\Policies;

use App\Models\Feature;
use App\Models\User\User;

abstract class FeaturePolicyAbstract extends BasePolicyAbstract
{
    /**
     * @return bool
     */
    public function all(User $user)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function view(User $user, Feature $feature)
    {
        return true;
    }
}
