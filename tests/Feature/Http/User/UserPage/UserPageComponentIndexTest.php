<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageComponentIndexTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_index_not_logged_in_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('GET', $this->path.$user->id.'/pages/'.$page->id.'/components');
        $response->assertStatus(403);
    }

    public function test_index_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
        ]);

        $response = $this->json('GET', $this->path.$user->id.'/pages/'.$page->id.'/components');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'component_type' => 'stats_cards',
        ]);
    }

    public function test_index_returns_only_page_components()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page1 = UserPage::factory()->create(['user_id' => $user->id]);
        $page2 = UserPage::factory()->create(['user_id' => $user->id]);

        UserPageComponent::factory()->count(3)->create(['user_page_id' => $page1->id]);
        UserPageComponent::factory()->count(2)->create(['user_page_id' => $page2->id]);

        $response = $this->json('GET', $this->path.$user->id.'/pages/'.$page1->id.'/components');
        $response->assertStatus(200);
    }
}
