<?php

declare(strict_types=1);

namespace Polis\Tests\Traits;

use Polis\Testing\RolesTesting as ShippedRolesTesting;

/**
 * Internal alias of the shipped {@see ShippedRolesTesting} trait.
 *
 * The real helpers now live in the published `src/Testing/` namespace so
 * consuming applications inherit them. This alias is retained so the package's
 * existing internal tests that reference `Polis\Tests\Traits\RolesTesting`
 * keep working without edit, while sharing a single implementation.
 */
trait RolesTesting
{
    use ShippedRolesTesting;
}
