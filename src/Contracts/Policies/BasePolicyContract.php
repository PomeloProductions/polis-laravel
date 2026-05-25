<?php

declare(strict_types=1);

namespace Polis\Contracts\Policies;

/**
 * Interface BasePolicyContract
 */
interface BasePolicyContract
{
    /**#@+
     * @var string action method names for the policies
     */
    const ACTION_LIST = 'all';

    const ACTION_VIEW = 'view';

    const ACTION_CREATE = 'create';

    const ACTION_UPDATE = 'update';

    const ACTION_DELETE = 'delete';
    /**#@-*/
}
