<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use App\Models\User\UserPageComponent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageComponentDeleteTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private string $path = '/v1/users/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_delete_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
            'component_type' => 'stats_cards',
        ]);

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id);
        $response->assertStatus(204);

        $this->assertSoftDeleted('user_page_components', ['id' => $component->id]);
    }

    public function test_delete_different_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $component = UserPageComponent::factory()->create([
            'user_page_id' => $page->id,
        ]);

        $this->actAsUser();

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id.'/components/'.$component->id);
        $response->assertStatus(403);
    }
}
