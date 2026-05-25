<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Policies;

use App\Models\Feature;
use App\Models\User\User;
use App\Policies\FeaturePolicy;
use Polis\Tests\TestCase;

/**
 * Class FeaturePolicyTest
 */
final class FeaturePolicyTest extends TestCase
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
