<?php

declare(strict_types=1);

namespace Polis\Policies\User;

use App\Models\User\User;
use Polis\Models\User\TodoSetting;
use Polis\Policies\BasePolicyAbstract;

/**
 * Abstract policy for TodoSetting (and the Todo surface generally — the Todo
 * controller's read/generate actions authorize against this). Consumers extend
 * it as App\Policies\User\TodoSettingPolicy.
 */
abstract class TodoSettingPolicyAbstract extends BasePolicyAbstract
{
    public function all(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function view(User $loggedInUser, User $requestedUser, TodoSetting $setting): bool
    {
        return $loggedInUser->id === $requestedUser->id && $setting->user_id === $requestedUser->id;
    }

    public function create(User $loggedInUser, User $requestedUser): bool
    {
        return $loggedInUser->id === $requestedUser->id;
    }

    public function update(User $loggedInUser, User $requestedUser, TodoSetting $setting): bool
    {
        return $loggedInUser->id === $requestedUser->id && $setting->user_id === $requestedUser->id;
    }

    public function delete(User $loggedInUser, User $requestedUser, TodoSetting $setting): bool
    {
        return $loggedInUser->id === $requestedUser->id && $setting->user_id === $requestedUser->id;
    }
}
