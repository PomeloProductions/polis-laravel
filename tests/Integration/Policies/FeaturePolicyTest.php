<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies;

use App\Models\Feature;
use App\Models\User\User;
use App\Policies\FeaturePolicy;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class FeaturePolicyTest
 */
final class FeaturePolicyTest extends ApplicationTestCase
{
    public function test_all(): void
    {
        $policy = new FeaturePolicy;

        $this->assertTrue($policy->all(new User));
    }

    public function test_view(): void
    {
        $policy = new FeaturePolicy;

        $this->assertTrue($policy->view(new User, new Feature));
    }
}
