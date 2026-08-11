<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\Contact;

use App\Models\User\Contact;
use App\Models\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Polis\Events\User\Contact\ContactCreatedEvent;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserContactCreateTest
 */
final class UserContactCreateTest extends ApplicationTestCase
{
    use MocksApplicationLog;

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

        $this->path .= $this->user->id.'/contacts';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $response = $this->json('POST', $this->path);

        $response->assertStatus(403);
    }

    public function test_create_successful(): void
    {
        $this->actingAs($this->user);

        $events = mock(Dispatcher::class);

        $this->app->bind(Dispatcher::class, function () use ($events) {
            return $events;
        });

        $eventCalled = false;
        $events->shouldReceive('dispatch')->with(\Mockery::on(function ($event) use (&$eventCalled) {
            $eventCalled = $eventCalled ? true : $event instanceof ContactCreatedEvent;

            return true;
        }));

        $user = User::factory()->create();

        $response = $this->json('POST', $this->path, [
            'requested_id' => $user->id,
        ]);

        $response->assertStatus(201);

        $this->assertTrue($eventCalled);
        /** @var Contact $contact */
        $contact = Contact::first();
        $this->assertEquals($this->user->id, $contact->initiated_by_id);
        $this->assertEquals($user->id, $contact->requested_id);
    }

    public function test_create_fails_missing_required_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'requested_id' => ['The requested id field is required.'],
            ],
        ]);
    }

    public function test_create_fails_protected_fields_present(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'deny' => 'hi',
            'confirm' => 'hi',
            'initiated_by_id' => 'hi',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'deny' => ['The deny field is not allowed or can not be set for this request.'],
                'confirm' => ['The confirm field is not allowed or can not be set for this request.'],
                'initiated_by_id' => ['The initiated by id field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_numbers(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'requested_id' => 'hi',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'requested_id' => ['The requested id must be an integer.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_model_fields(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('POST', $this->path, [
            'requested_id' => 544,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'requested_id' => ['The selected requested id is invalid.'],
            ],
        ]);
    }
}
