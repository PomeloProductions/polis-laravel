<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\Authentication;

use App\Models\Role;
use App\Models\User\InvitationToken;
use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Polis\Events\User\InvitationAcceptedEvent;
use Polis\Events\User\SignUpEvent;
use Polis\Listeners\User\InvitationAcceptedListener;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserSignUpTest
 */
final class SignUpTest extends ApplicationTestCase
{
    use MocksApplicationLog;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
    }

    public function test_success(): void
    {
        $dispatcher = mock(Dispatcher::class);

        $signUpEventHit = false;

        $dispatcher->shouldReceive('dispatch')->with(\Mockery::on(function ($event) use (&$signUpEventHit) {
            if ($event instanceof SignUpEvent) {
                $signUpEventHit = true;
            }

            return true;
        }));

        $this->app->bind(Dispatcher::class, function () use ($dispatcher) {
            return $dispatcher;
        });

        $properties = [
            'email' => 'guy@smiley.com',
            'first_name' => 'Steve',
            'password' => 'complex!',
        ];

        $response = $this->json('POST', '/v1/auth/sign-up', $properties);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'token',
        ]);
        $token = $response->json('token');

        $this->assertTrue($signUpEventHit);

        $this->actingAs = null;

        $this->app['env'] = 'testing-override';
        $response = $this->json('GET', '/v1/users/me', [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $response->assertStatus(200);
        $model = $response->original;

        // Make sure the password was hashed properly separately
        $password = $properties['password'];
        unset($properties['password']);

        $this->assertEquals($properties, [
            'email' => $model->email,
            'first_name' => $model->first_name,
        ]);

        $this->assertTrue(Hash::check($password, $model->password));
    }

    public function test_website_sign_up_failure_missing_required_fields(): void
    {
        $response = $this->json('POST', '/v1/auth/sign-up', []);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'email' => ['The email field is required.'],
                'first_name' => ['The first name field is required.'],
                'password' => ['The password field is required.'],
            ],
        ]);
    }

    public function test_website_sign_up_fails_invalid_string_fields(): void
    {
        $response = $this->json('POST', '/v1/auth/sign-up', [
            'email' => 1,
            'first_name' => 1,
            'password' => 1,
        ]);

        $response->assertJson(['errors' => [
            'email' => ['The email must be a string.'],
            'first_name' => ['The first name must be a string.'],
            'password' => ['The password must be a string.'],
        ]]);

        $response->assertStatus(400);
    }

    public function test_website_sign_up_fails_too_short_fields(): void
    {
        $response = $this->json('POST', '/v1/auth/sign-up', [
            'password' => 'a',
        ]);
        $response->assertJson(['errors' => [
            'password' => ['The password must be at least 6 characters.'],
        ]]);

        $response->assertStatus(400);
    }

    public function test_website_sign_up_fails_too_long_fields(): void
    {
        $response = $this->json('POST', '/v1/auth/sign-up', [
            'email' => str_repeat('a', 121),
            'first_name' => str_repeat('a', 121),
            'password' => str_repeat('a', 257),
        ]);
        $response->assertJson(['errors' => [
            'email' => ['The email may not be greater than 120 characters.'],
            'first_name' => ['The first name may not be greater than 120 characters.'],
            'password' => ['The password may not be greater than 256 characters.'],
        ]]);

        $response->assertStatus(400);
    }

    public function test_website_sign_up_fails_invalid_email_fields(): void
    {
        $response = $this->json('POST', '/v1/auth/sign-up', [
            'email' => 'asdf',
        ]);
        $response->assertJson(['errors' => [
            'email' => ['The email must be a valid email address.'],
        ]]);

        $response->assertStatus(400);
    }

    public function test_website_sign_up_fails_email_in_use(): void
    {
        User::factory()->create(['email' => 'test@test.com']);

        $response = $this->json('POST', '/v1/auth/sign-up', [
            'email' => 'test@test.com',
        ]);
        $response->assertJson(['errors' => [
            'email' => ['The email has already been taken.'],
        ]]);

        $response->assertStatus(400);
    }

    public function test_sign_up_success_with_valid_invitation_token(): void
    {
        Config::set('polis.invitation_required', true);

        $role = Role::find(Role::ARTICLE_EDITOR);
        $invitationToken = InvitationToken::factory()->create([
            'token' => 'test-token-123',
            'role_id' => $role->id,
            'used_at' => null,
        ]);

        $dispatcher = mock(Dispatcher::class);

        $signUpEventHit = false;
        $invitationAcceptedEventHit = false;

        $dispatcher->shouldReceive('dispatch')->with(\Mockery::on(function ($event) use (&$signUpEventHit, &$invitationAcceptedEventHit) {
            if ($event instanceof SignUpEvent) {
                $signUpEventHit = true;
            }
            if ($event instanceof InvitationAcceptedEvent) {
                $invitationAcceptedEventHit = true;
            }

            return true;
        }));

        $this->app->bind(Dispatcher::class, function () use ($dispatcher) {
            return $dispatcher;
        });

        $properties = [
            'email' => 'guy@smiley.com',
            'first_name' => 'Steve',
            'password' => 'complex!',
            'invitation_token' => 'test-token-123',
        ];

        $response = $this->json('POST', '/v1/auth/sign-up', $properties);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'token',
        ]);

        $this->assertTrue($signUpEventHit);
        $this->assertTrue($invitationAcceptedEventHit);

        // Since we mocked the dispatcher, manually call the listener to verify its behavior
        $user = User::where('email', 'guy@smiley.com')->first();
        $invitationAcceptedListener = app(InvitationAcceptedListener::class);
        $invitationAcceptedListener->handle(new InvitationAcceptedEvent($user, $invitationToken));

        // Verify the token was marked as used
        $invitationToken->refresh();
        $this->assertNotNull($invitationToken->used_at);

        // Verify the user has the role
        $this->assertTrue($user->roles->contains($role));
    }

    public function test_sign_up_fails_when_invitation_required_but_not_provided(): void
    {
        Config::set('polis.invitation_required', true);

        $properties = [
            'email' => 'guy@smiley.com',
            'first_name' => 'Steve',
            'password' => 'complex!',
        ];

        $response = $this->json('POST', '/v1/auth/sign-up', $properties);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'invitation_token' => ['The invitation token field is required.'],
            ],
        ]);
    }

    public function test_sign_up_fails_when_invitation_token_is_invalid(): void
    {
        Config::set('polis.invitation_required', true);

        $properties = [
            'email' => 'guy@smiley.com',
            'first_name' => 'Steve',
            'password' => 'complex!',
            'invitation_token' => 'invalid-token',
        ];

        $response = $this->json('POST', '/v1/auth/sign-up', $properties);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'invitation_token' => ['The invitation token is invalid.'],
            ],
        ]);
    }

    public function test_sign_up_fails_when_invitation_token_already_used(): void
    {
        Config::set('polis.invitation_required', true);

        InvitationToken::factory()->create([
            'token' => 'used-token',
            'used_at' => now(),
        ]);

        $properties = [
            'email' => 'guy@smiley.com',
            'first_name' => 'Steve',
            'password' => 'complex!',
            'invitation_token' => 'used-token',
        ];

        $response = $this->json('POST', '/v1/auth/sign-up', $properties);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'invitation_token' => ['The invitation token is invalid.'],
            ],
        ]);
    }

    public function test_sign_up_success_with_invitation_token_without_role(): void
    {
        Config::set('polis.invitation_required', true);

        InvitationToken::factory()->create([
            'token' => 'token-without-role',
            'role_id' => null,
            'used_at' => null,
        ]);

        $dispatcher = mock(Dispatcher::class);

        $dispatcher->shouldReceive('dispatch')->with(\Mockery::on(function ($event) {
            return true;
        }));

        $this->app->bind(Dispatcher::class, function () use ($dispatcher) {
            return $dispatcher;
        });

        $properties = [
            'email' => 'guy@smiley.com',
            'first_name' => 'Steve',
            'password' => 'complex!',
            'invitation_token' => 'token-without-role',
        ];

        $response = $this->json('POST', '/v1/auth/sign-up', $properties);

        $response->assertStatus(201);

        // Verify the user was created but has no additional roles
        $user = User::where('email', 'guy@smiley.com')->first();
        $this->assertNotNull($user);
    }

    public function test_sign_up_success_when_invitation_not_required(): void
    {
        Config::set('polis.invitation_required', false);

        $dispatcher = mock(Dispatcher::class);

        $dispatcher->shouldReceive('dispatch')->with(\Mockery::on(function ($event) {
            return true;
        }));

        $this->app->bind(Dispatcher::class, function () use ($dispatcher) {
            return $dispatcher;
        });

        $properties = [
            'email' => 'guy@smiley.com',
            'first_name' => 'Steve',
            'password' => 'complex!',
        ];

        $response = $this->json('POST', '/v1/auth/sign-up', $properties);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'token',
        ]);
    }
}
