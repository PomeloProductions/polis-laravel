<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models;

use App\Models\Resource;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;

/**
 * Class ResourceTest
 */
final class ResourceTest extends TestCase
{
    use DatabaseSetupTrait;

    public function test_resource(): void
    {
        User::unsetEventDispatcher();
        $user = User::factory()->create();

        /** @var resource $resource */
        $resource = Resource::factory()->create([
            'resource_id' => $user->id,
            'resource_type' => 'user',
        ]);

        $this->assertInstanceOf(User::class, $resource->resource);
    }
}
