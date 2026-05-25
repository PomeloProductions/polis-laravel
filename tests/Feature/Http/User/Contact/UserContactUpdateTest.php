<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\User\Contact;

use App\Models\User\Contact;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserContactUpdateTest
 */
final class UserContactUpdateTest extends TestCase
{
    use DatabaseSetupTrait, MocksApplicationLog;

    /**
     * @var string
     */
    private $path = '/v1/users/';

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupDatabase();
        $this->mockApplicationLog();
        $this->user = User::factory()->create();

        $this->path .= $this->user->id.'/contacts/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->json('PUT', $this->path.$contact->id);

        $response->assertStatus(403);
    }

    public function test_not_found(): void
    {
        $this->actingAs($this->user);

        $response = $this->json('PUT', $this->path.'453');

        $response->assertStatus(404);
    }

    public function test_update_deny_successful(): void
    {
        $this->actingAs($this->user);

        $contact = Contact::factory()->create([
            'requested_id' => $this->user->id,
        ]);

        $response = $this->json('PUT', $this->path.$contact->id, [
            'deny' => true,
        ]);

        $response->assertStatus(200);

        /** @var Contact $updated */
        $updated = Contact::find($contact->id);

        $this->assertNotNull($updated->denied_at);
    }

    public function test_update_confirm_successful(): void
    {
        $this->actingAs($this->user);

        $contact = Contact::factory()->create([
            'requested_id' => $this->user->id,
        ]);

        $response = $this->json('PUT', $this->path.$contact->id, [
            'confirm' => true,
        ]);

        $response->assertStatus(200);

        /** @var Contact $updated */
        $updated = Contact::find($contact->id);

        $this->assertNotNull($updated->confirmed_at);
    }

    public function test_update_fails_protected_fields_present(): void
    {
        $this->actingAs($this->user);

        $contact = Contact::factory()->create([
            'requested_id' => $this->user->id,
        ]);

        $response = $this->json('PUT', $this->path.$contact->id, [
            'initiated_by_id' => 'hi',
            'requested_id' => 'hi',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'requested_id' => ['The requested id field is not allowed or can not be set for this request.'],
                'initiated_by_id' => ['The initiated by id field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_update_fails_invalid_boolean_fields(): void
    {
        $this->actingAs($this->user);

        $contact = Contact::factory()->create([
            'requested_id' => $this->user->id,
        ]);

        $response = $this->json('PUT', $this->path.$contact->id, [
            'deny' => -1,
            'confirm' => -1,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'errors' => [
                'deny' => ['The deny field must be true or false.'],
                'confirm' => ['The confirm field must be true or false.'],
            ],
        ]);
    }
}
