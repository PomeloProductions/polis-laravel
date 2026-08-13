<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Polis\Contracts\Repositories\Messaging\MessageRepositoryContract;
use Polis\Tests\Fixtures\Controllers\MessageController;
use Polis\Tests\Fixtures\Models\Message as MessageFixture;

/**
 * Unit coverage for MessageControllerAbstract.
 *
 * Only exposes store(). Three behaviors to pin:
 *   1. If Auth::user() is non-null, from_type='user' + from_id=<userId>
 *      get attached to the payload.
 *   2. If reply_to_email is missing but data.email is set, the email gets
 *      promoted to reply_to_email.
 *   3. If reply_to_name is missing, build it from data.name, or
 *      first_name + last_name fallback.
 */
final class MessageControllerAbstractTest extends ControllerTestCase
{
    public function test_store_promotes_logged_in_user_and_data_fields_into_payload(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);

        $user = Mockery::mock(Authenticatable::class);
        $user->id = 42;
        Auth::shouldReceive('user')->once()->andReturn($user);

        $payload = [
            'data' => [
                'email' => 'inbound@example.test',
                'first_name' => 'Inbound',
                'last_name' => 'Sender',
            ],
        ];

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Message\\StoreRequest', $payload);

        $created = Mockery::mock(MessageFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['from_type'] === 'user'
                    && $data['from_id'] === 42
                    && $data['reply_to_email'] === 'inbound@example.test'
                    && $data['reply_to_name'] === 'Inbound Sender';
            }))
            ->andReturn($created);

        $response = (new MessageController($repo))->store($request);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_store_uses_data_name_when_present_for_reply_to_name(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);
        Auth::shouldReceive('user')->once()->andReturn(null);

        $payload = ['data' => ['name' => 'Combined Name']];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Message\\StoreRequest', $payload);

        $created = Mockery::mock(MessageFixture::class);
        $created->shouldReceive('toJson')->andReturn('{}');
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return ! isset($data['from_type'])
                    && $data['reply_to_name'] === 'Combined Name';
            }))
            ->andReturn($created);

        $response = (new MessageController($repo))->store($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_store_preserves_existing_reply_to_fields_when_provided(): void
    {
        $repo = Mockery::mock(MessageRepositoryContract::class);
        Auth::shouldReceive('user')->once()->andReturn(null);

        $payload = [
            'reply_to_email' => 'custom@example.test',
            'reply_to_name' => 'Custom Name',
            'data' => ['email' => 'ignored@example.test', 'name' => 'Ignored'],
        ];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\Message\\StoreRequest', $payload);

        $created = Mockery::mock(MessageFixture::class);
        $created->shouldReceive('toJson')->andReturn('{}');
        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['reply_to_email'] === 'custom@example.test'
                    && $data['reply_to_name'] === 'Custom Name';
            }))
            ->andReturn($created);

        $response = (new MessageController($repo))->store($request);
        $this->assertSame(201, $response->getStatusCode());
    }
}
