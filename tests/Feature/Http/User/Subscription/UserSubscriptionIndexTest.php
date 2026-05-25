<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\Subscription;

use App\Models\Subscription\Subscription;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserSubscriptionIndexTest
 */
final class UserSubscriptionIndexTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        User::unsetEventDispatcher();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $user = User::factory()->create();

        $response = $this->json('GET', $this->path.$user->id.'/subscriptions');

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $this->actAsUser();
        $user = User::factory()->create();

        $response = $this->json('GET', $this->path.$user->id.'/subscriptions');

        $response->assertStatus(403);
    }

    public function test_user_not_found(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path.'12/subscriptions');

        $response->assertStatus(404);
    }

    public function test_get_pagination_empty(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path.$this->actingAs->id.'/subscriptions');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAsUser();

        Subscription::factory()->count(6)->create();
        Subscription::factory()->count(15)->create([
            'subscriber_id' => $this->actingAs->id,
            'subscriber_type' => 'user',
        ]);
        Subscription::factory()->count(3)->create([
            'subscriber_id' => $this->actingAs->id,
            'subscriber_type' => 'organization',
        ]);

        // first page
        $response = $this->json('GET', $this->path.$this->actingAs->id.'/subscriptions');
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
                    '*' => array_keys((new Subscription)->toArray()),
                ],
            ]);

        // second page
        $response = $this->json('GET', $this->path.$this->actingAs->id.'/subscriptions?page=2');
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
                    '*' => array_keys((new Subscription)->toArray()),
                ],
            ]);

        // page with limit
        $response = $this->json('GET', $this->path.$this->actingAs->id.'/subscriptions?page=2&limit=5');
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
                    '*' => array_keys((new Subscription)->toArray()),
                ],
            ]);
    }
}
