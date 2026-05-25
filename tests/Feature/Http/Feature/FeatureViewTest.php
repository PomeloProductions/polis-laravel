<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Feature;

use App\Models\Feature;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class FeatureViewTest
 */
final class FeatureViewTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog, RolesTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_get_single_success(): void
    {
        /** @var Feature $model */
        $model = Feature::factory()->create();

        $response = $this->json('GET', '/v1/features/'.$model->id);

        $response->assertStatus(200);
        $response->assertJson($model->toArray());
    }

    public function test_get_single_not_found_fails(): void
    {
        $response = $this->json('GET', '/v1/features/13452')
            ->assertExactJson([
                'message' => 'This item was not found.',
            ]);
        $response->assertStatus(404);
    }

    public function test_get_single_invalid_id_fails(): void
    {
        $response = $this->json('GET', '/v1/features/a')
            ->assertExactJson([
                'message' => 'This path was not found.',
            ]);
        $response->assertStatus(404);
    }
}
