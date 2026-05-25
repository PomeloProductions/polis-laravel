<?php

declare(strict_types=1);

namespace Polis\Policies;

use App\Models\Category;
use App\Models\User\User;

abstract class CategoryPolicyAbstract extends BasePolicyAbstract
{
    /**
     * All users can create a category
     *
     * @return bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Only super admins can update a category
     *
     * @return bool
     */
    public function update(User $user, Category $category)
    {
        return false;
    }

    /**
     * Only super admins can delete a category
     *
     * @return bool
     */
    public function delete(User $user, Category $category)
    {
        return false;
    }
}
