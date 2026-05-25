<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Ballot;

use App\Models\Role;
use App\Models\Vote\Ballot;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class BallotViewTest
 */
final class BallotViewTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $model = Ballot::factory()->create();
        $response = $this->json('GET', '/v1/ballots/'.$model->id);
        $response->assertStatus(403);
    }

    public function test_get_single_success(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        /** @var Ballot $model */
        $model = Ballot::factory()->create([
            'id' => 1,
        ]);

        $response = $this->json('GET', '/v1/ballots/1');

        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }

    public function test_get_single_not_found_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $response = $this->json('GET', '/v1/ballots/1')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_get_single_invalid_id_fails(): void
    {
        $this->actAs(Role::SUPER_ADMIN);
        $response = $this->json('GET', '/v1/ballots/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }
}
