<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageIndexTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked()
    {
        $user = User::factory()->create();

        $response = $this->json('GET', $this->path.$user->id.'/pages');
        $response->assertStatus(403);
    }

    public function test_different_user_blocked()
    {
        $user = User::factory()->create();
        $this->actAsUser();

        $response = $this->json('GET', $this->path.$user->id.'/pages');
        $response->assertStatus(403);
    }

    public function test_index_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        UserPage::factory()->create([
            'user_id' => $user->id,
            'slug' => 'my-page',
            'name' => 'My Page',
            'route_path' => 'my-page',
            'page_type' => 'dashboard',
        ]);

        $response = $this->json('GET', $this->path.$user->id.'/pages');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'slug' => 'my-page',
            'name' => 'My Page',
        ]);
    }

    public function test_index_with_components_expanded()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'slug' => 'test-page',
            'name' => 'Test Page',
            'route_path' => 'test-page',
            'page_type' => 'dashboard',
        ]);

        UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
            'display_order' => 0,
        ]);

        $response = $this->json('GET', $this->path.$user->id.'/pages?expand[components]=*&with[]=components');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'component_type' => 'stats_cards',
        ]);
    }

    public function test_index_returns_only_own_pages()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        UserPage::factory()->count(3)->create(['user_id' => $user->id]);
        UserPage::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->json('GET', $this->path.$user->id.'/pages');
        $response->assertStatus(200);
    }

    public function test_index_empty_when_no_pages()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->json('GET', $this->path.$user->id.'/pages');
        $response->assertStatus(200);
    }
}
