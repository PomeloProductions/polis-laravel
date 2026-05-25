<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication;

use App\Models\User\User;
use Illuminate\Support\Facades\Hash;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class LoginTest
 */
final class LoginTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
        $this->setupDatabase();
    }

    public function test_missing_required_fields(): void
    {
        $response = $this->json('POST', '/v1/auth/login');

        $response->assertJson([
            'errors' => [
                'email' => [
                    'The email field is required.',
                ],
                'password' => [
                    'The password field is required.',
                ],
            ],
        ]);
        $response->assertStatus(400);
    }

    public function test_string_fields_too_long(): void
    {
        $response = $this->json('POST', '/v1/auth/login', [
            'email' => str_repeat('a', 257),
            'password' => str_repeat('a', 257),
        ]);

        $response->assertJson([
            'errors' => [
                'email' => [
                    'The email may not be greater than 256 characters.',
                ],
                'password' => [
                    'The password may not be greater than 256 characters.',
                ],
            ],
        ]);
        $response->assertStatus(400);
    }

    public function test_email_format_incorrect(): void
    {
        $response = $this->json('POST', '/v1/auth/login', [
            'email' => 'bryce',
            'password' => str_repeat('a', 257),
        ]);

        $response->assertJson([
            'errors' => [
                'email' => [
                    'The email must be a valid email address.',
                ],
            ],
        ]);
        $response->assertStatus(400);
    }

    public function test_user_by_email_does_not_exist(): void
    {
        $response = $this->json('POST', '/v1/auth/login', [
            'email' => 'guy@smiley.com',
            'password' => '123',
        ]);

        $response->assertJson([
            'message' => 'Invalid login credentials.',
        ]);
        $response->assertStatus(401);
    }

    public function test_by_email_password_wrong(): void
    {
        User::factory()->create([
            'email' => 'guy@smiley.com',
            'password' => Hash::make('do not guess me!'),
        ]);

        $response = $this->json('POST', '/v1/auth/login', [
            'email' => 'guy@smiley.com',
            'password' => '123',
        ]);
        $response->assertJson([
            'message' => 'Invalid login credentials.',
        ]);
        $response->assertStatus(401);
    }

    public function test_by_email_success_login(): void
    {
        User::factory()->create([
            'email' => 'guy@smiley.com',
            'password' => Hash::make('complex!'),
        ]);

        $response = $this->json('POST', '/v1/auth/login', [
            'email' => 'guy@smiley.com',
            'password' => 'complex!',
        ]);
        $response->assertJsonStructure([
            'token',
        ]);
        $response->assertStatus(200);
    }
}
