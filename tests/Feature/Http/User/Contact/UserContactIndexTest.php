<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\Contact;

use App\Models\User\Contact;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserContactIndexTest
 */
final class UserContactIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

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

        $response = $this->json('GET', $this->path.$user->id.'/contacts');

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $this->actAsUser();
        $user = User::factory()->create();

        $response = $this->json('GET', $this->path.$user->id.'/contacts');

        $response->assertStatus(403);
    }

    public function test_user_not_found(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path.'12/contacts');

        $response->assertStatus(404);
    }

    public function test_get_pagination_empty(): void
    {
        $this->actAsUser();

        $response = $this->json('GET', $this->path.$this->actingAs->id.'/contacts');

        $response->assertStatus(200);
        $response->assertJson([
            'total' => 0,
            'data' => [],
        ]);
    }

    public function test_get_pagination_result(): void
    {
        $this->actAsUser();

        Contact::factory()->count(4)->create();
        Contact::factory()->count(10)->create([
            'requested_id' => $this->actingAs->id,
        ]);
        Contact::factory()->count(5)->create([
            'initiated_by_id' => $this->actingAs->id,
        ]);

        // first page
        $response = $this->json('GET', $this->path.$this->actingAs->id.'/contacts');
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
                    '*' => array_keys((new Contact)->toArray()),
                ],
            ]);

        // second page
        $response = $this->json('GET', $this->path.$this->actingAs->id.'/contacts?page=2');
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
                    '*' => array_keys((new Contact)->toArray()),
                ],
            ]);

        // page with limit
        $response = $this->json('GET', $this->path.$this->actingAs->id.'/contacts?page=2&limit=5');
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
                    '*' => array_keys((new Contact)->toArray()),
                ],
            ]);
    }

    public function test_get_pagination_with_expands(): void
    {
        $this->actAsUser();

        Contact::factory()->count(4)->create();
        Contact::factory()->count(10)->create([
            'requested_id' => $this->actingAs->id,
        ]);
        Contact::factory()->count(5)->create([
            'initiated_by_id' => $this->actingAs->id,
        ]);

        // first page
        $response = $this->json('GET', $this->path.$this->actingAs->id.'/contacts?expand[initiatedBy]=*&expand[requested]=*');
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
                    '*' => array_keys((new Contact)->toArray()),
                ],
            ]);
    }
}
