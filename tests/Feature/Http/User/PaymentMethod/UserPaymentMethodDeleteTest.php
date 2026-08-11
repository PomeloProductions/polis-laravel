<?php

declare(strict_types=1);

namespace Polis\Tests\Feature\Http\User\PaymentMethod;

use App\Models\Payment\PaymentMethod;
use App\Models\User\User;
use Polis\Tests\Application\ApplicationTestCase;
use Polis\Tests\Traits\MocksApplicationLog;

/**
 * Class UserPaymentMethodDeleteTest
 */
final class UserPaymentMethodDeleteTest extends ApplicationTestCase
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
        $this->path .= $this->user->id.'/payment-methods/';
    }

    public function test_not_logged_in_user_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);
        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_incorrect_user_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->actAsUser();

        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_user_does_not_own_payment_method_blocked(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $this->actingAs($this->user);

        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

        $response->assertStatus(403);
    }

    public function test_delete_successful(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->json('DELETE', $this->path.$paymentMethod->id);

        $response->assertStatus(204);

        $this->assertCount(0, PaymentMethod::all());
    }
}
