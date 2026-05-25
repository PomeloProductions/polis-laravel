<?php

declare(strict_types=1);

namespace Polis\Events\User;

use App\Models\User\User;

/**
 * Class UserMergeEvent
 */
class UserMergeEvent
{
    /**
     * This is the user that everything is merging into
     *
     * @var User
     */
    private $mainUser;

    /**
     * This is the user that is being merged and deleted
     *
     * @var User
     */
    private $mergeUser;

    /**
     * This should be a key value pair of field names as the keys, and boolean for whether or not to merge the value.
     * Custom listeners can be in whatever format you like
     *
     * @var array
     */
    private $mergeOptions;

    /**
     * UserMergeEvent constructor.
     */
    public function __construct(User $mainUser, User $mergeUser, array $mergeOptions = [])
    {
        $this->mainUser = $mainUser;
        $this->mergeUser = $mergeUser;
        $this->mergeOptions = $mergeOptions;
    }

    public function getMainUser(): User
    {
        return $this->mainUser;
    }

    public function getMergeUser(): User
    {
        return $this->mergeUser;
    }

    public function getMergeOptions(): array
    {
        return $this->mergeOptions;
    }
}
