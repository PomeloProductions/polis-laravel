<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\PaymentMethod;

use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use Polis\Tests\DatabaseSetupTrait;
use Polis\Tests\TestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserPaymentMethodUpdateTest
 */
final class UserPaymentMethodUpdateTest extends TestCase
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
        $this->path .= $this->user->id.'/payment-methods/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);
        $response = $this->json('PUT', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->actAsUser();

        $response = $this->json('PUT', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_user_does_not_own_payment_method_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $this->actingAs($this->user);

        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_update_successful(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->json('PUT', $this->path.$paymentMethod->id, [
            'default' => false,
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'default' => false,
        ]);
    }

    public function test_update_fails_not_allowed_fields_present(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->json('PUT', $this->path.$paymentMethod->id, [
            'token' => 'hi',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'token' => ['The token field is not allowed or can not be set for this request.'],
            ],
        ]);
    }

    public function test_create_fails_invalid_boolean_fields(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->json('PUT', $this->path.$paymentMethod->id, [
            'default' => 'hello',
        ]);

        $response->assertStatus(400);

        $response->assertJson([
            'errors' => [
                'default' => ['The default field must be true or false.'],
            ],
        ]);
    }
}
