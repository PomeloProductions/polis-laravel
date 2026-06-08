<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Policies\User;

use Polis\Policies\User\ExternalAccountConnectionPolicyAbstract;

/**
 * Empty concrete subclass of ExternalAccountConnectionPolicyAbstract so the
 * abstract's gate methods can be exercised in unit tests without needing a
 * consumer-side App\Policies\... concrete.
 */
class ExternalAccountConnectionPolicy extends ExternalAccountConnectionPolicyAbstract {}
