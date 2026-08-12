<?php

declare(strict_types=1);

namespace Polis\Tests\Integration\Models;

use App\Models\Resource;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;

/**
 * Class ResourceTest
 */
final class ResourceTest extends ApplicationTestCase
{
    
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
