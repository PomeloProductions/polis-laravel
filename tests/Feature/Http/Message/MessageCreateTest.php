<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Message;

use App\Models\Messaging\Message;
use App\Models\Role;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;
use Polis\Tests\Traits\RolesTesting;

/**
 * Class MembershipPlanCreateTest
 */
final class MessageCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog, RolesTesting;

    private $route = '/v1/messages';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_create_successful(): void
    {
        $properties = [
            'template' => 'contact',
            'data' => [
                'first_name' => 'John',
                'last_name' => 'Clancy',
                'phone' => '123',
            ],
        ];

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);

        $response->assertJson($properties);
    }

    public function test_create_successful_connects_logged_in_user(): void
    {
        $this->actAs(Role::APP_USER);

        $properties = [
            'template' => 'contact',
            'data' => [
                'first_name' => 'John',
                'last_name' => 'Clancy',
                'phone' => '123',
            ],
        ];

        $response = $this->json('POST', $this->route, $properties);

        $response->assertStatus(201);

        $response->assertJson($properties);

        /** @var Message $model */
        $model = $response->original;

        $this->assertEquals($this->actingAs->id, $model->from_id);
        $this->assertEquals('user', $model->from_type);
    }

    public function test_create_fails_invalid_string_fields(): void
    {
        $data = [
            'message' => 324,
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'message' => ['The message must be a string.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_enum_fields(): void
    {
        $data = [
            'template' => 'bye',
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'template' => ['The selected template is invalid.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_fields(): void
    {
        $data = [
            'seen' => 'hello',
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'seen' => ['The seen field must be true or false.'],
            ],
        ]);
    }

    public function test_create_fails_invali_array_fields(): void
    {
        $data = [
            'data' => 'hello',
        ];

        $response = $this->json('POST', $this->route, $data);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Sorry, something went wrong.',
            'errors' => [
                'data' => ['The data must be an array.'],
            ],
        ]);
    }
}
