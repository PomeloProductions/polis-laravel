<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\Asset;

use App\Models\Asset;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserPaymentMethodDeleteTest
 */
final class UserAssetDeleteTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();

        $this->user = User::factory()->create();
        $this->path .= $this->user->id.'/assets/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $asset = Asset::factory()->create([
            'owner_id' => $this->user->id,
            'owner_type' => 'user',
        ]);
        $response = $this->json('DELETE', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $asset = Asset::factory()->create([
            'owner_id' => $this->user->id,
            'owner_type' => 'user',
        ]);

        $this->actAsUser();

        $response = $this->json('DELETE', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_user_does_not_own_payment_method_blocked(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs($this->user);

        $response = $this->json('DELETE', $this->path.$asset->id);

        $response->assertStatus(403);
    }

    public function test_delete_successful(): void
    {
        $asset = Asset::factory()->create([
            'owner_id' => $this->user->id,
            'owner_type' => 'user',
        ]);

        $this->actingAs($this->user);

        $response = $this->json('DELETE', $this->path.$asset->id);

        $response->assertStatus(204);

        $this->assertCount(0, Asset::all());
    }
}
