<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\ForgotPassword;

use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Events\User\ForgotPasswordEvent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class ForgotPasswordTest
 */
final class ForgotPasswordTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    private $route = '/v1/forgot-password';

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplicationLog();
        $this->setupDatabase();
    }

    public function test_missing_required_fields(): void
    {
        $response = $this->json('POST', $this->route);

        $response->assertJson([
            'errors' => [
                'email' => [
                    'The email field is required.',
                ],
            ],
        ]);
        $response->assertStatus(422);
    }

    public function test_string_fields_too_long(): void
    {
        $response = $this->json('POST', $this->route, [
            'email' => str_repeat('a', 121),
        ]);

        $response->assertJson([
            'errors' => [
                'email' => [
                    'The email may not be greater than 120 characters.',
                ],
            ],
        ]);
        $response->assertStatus(422);
    }

    public function test_email_format_incorrect(): void
    {
        $response = $this->json('POST', $this->route, [
            'email' => 'bryce',
        ]);

        $response->assertJson([
            'errors' => [
                'email' => [
                    'The email must be a valid email address.',
                ],
            ],
        ]);
        $response->assertStatus(422);
    }

    public function test_user_by_email_does_not_exist(): void
    {
        $response = $this->json('POST', $this->route, [
            'email' => 'guy@smiley.com',
        ]);

        $response->assertJson([
            'errors' => [
                'email' => ['The selected email is invalid.'],
            ],
        ]);
        $response->assertStatus(422);
    }

    public function test_success(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
        ]);

        $dispatcher = mock(Dispatcher::class);

        $forgotPasswordEventDispatched = false;

        $dispatcher->shouldReceive('dispatch')
            ->with(\Mockery::on(function ($event) use ($user, &$forgotPasswordEventDispatched) {

                if ($event instanceof ForgotPasswordEvent) {

                    $token = $event->getPasswordToken();

                    $this->assertEquals($user->id, $token->user_id);

                    $forgotPasswordEventDispatched = true;

                    return true;
                }

                return true;
            })
            );

        $this->app->bind(Dispatcher::class, function () use ($dispatcher) {
            return $dispatcher;
        });

        $response = $this->json('POST', $this->route, [
            'email' => 'test@test.com',
        ]);

        $this->assertTrue($forgotPasswordEventDispatched);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'OK',
        ]);
    }
}
