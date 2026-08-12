<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Resource;

use App\Models\Resource;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ResourceIndexTest
 */
final class ResourceIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/resources';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        User::unsetEventDispatcher();
    }

    public function test_not_logged_user_blocked(): void
    {
        $response = $this->json('GET', $this->path);

        $response->assertStatus(403);
    }

    public function test_get_pagination_empty(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path);

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAsUser();

        Resource::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', $this->path);
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 10,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Resource)->toArray()),
                ],
            ]);

        // second page
        $response = $this->json('GET', $this->path.'?page=2');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 10,
            'from' => 11,
            'to' => 15,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Resource)->toArray()),
                ],
            ]);

        // page with limit
        $response = $this->json('GET', $this->path.'?page=2&limit=5');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 2,
            'per_page' => 5,
            'from' => 6,
            'to' => 10,
            'last_page' => 3,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Resource)->toArray()),
                ],
            ]);
    }

    public function test_get_pagination_with_expand(): void
    {
        $this->actAsUser();

        Resource::factory()->count(15)->create();

        // first page
        $response = $this->json('GET', $this->path.'?expand[resource]=*');
        $response->assertStatus(200);
        $response->assertJson([
            'total' => 15,
            'current_page' => 1,
            'per_page' => 10,
            'from' => 1,
            'to' => 10,
            'last_page' => 2,
        ])
            ->assertJsonStructure([
                'data' => [
                    '*' => array_keys((new Resource)->toArray()),
                ],
            ]);
    }
}
