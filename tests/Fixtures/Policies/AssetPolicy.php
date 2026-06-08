<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Policies;

use Polis\Policies\AssetPolicyAbstract;

/**
 * Empty concrete subclass used to invoke AssetPolicyAbstract's gate
 * methods in isolation. See tests/Fixtures/Policies/README.md.
 */
class AssetPolicy extends AssetPolicyAbstract {}
