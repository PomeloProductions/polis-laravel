<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\UserPage;

use App\Models\User\User;
use App\Models\User\UserPage;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

class UserPageDeleteTest extends TestCase
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
        $page = UserPage::factory()->create(['user_id' => $user->id]);

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id);
        $response->assertStatus(403);
    }

    public function test_different_user_blocked()
    {
        $user = User::factory()->create();
        $page = UserPage::factory()->create(['user_id' => $user->id]);
        $this->actAsUser();

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id);
        $response->assertStatus(403);
    }

    public function test_delete_successful()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'is_required' => false,
        ]);

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id);
        $response->assertStatus(204);

        $this->assertSoftDeleted('user_pages', ['id' => $page->id]);
    }

    public function test_delete_required_page_blocked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $page = UserPage::factory()->create([
            'user_id' => $user->id,
            'is_required' => true,
        ]);

        $response = $this->json('DELETE', $this->path.$user->id.'/pages/'.$page->id);
        $response->assertStatus(403);
    }
}
