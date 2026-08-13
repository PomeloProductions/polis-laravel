<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\User;

use Carbon\Carbon;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Repositories\User\ContactRepositoryContract;
use Polis\Events\User\Contact\ContactCreatedEvent;
use Polis\Tests\Fixtures\Controllers\User\ContactController;
use Polis\Tests\Fixtures\Models\Contact as ContactFixture;
use Polis\Tests\Fixtures\Models\User as UserFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for User\ContactControllerAbstract.
 *
 * store() stamps the initiating user_id and dispatches a
 * ContactCreatedEvent. update() translates confirm/deny flags into
 * timestamped fields.
 */
final class ContactControllerAbstractTest extends ControllerTestCase
{
    public function test_index_scopes_find_all_to_parent_user(): void
    {
        $repo = Mockery::mock(ContactRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $user = Mockery::mock(UserFixture::class);
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $request = $this->makeIndexRequest('Polis\\Http\\Core\\Requests\\User\\Contact\\IndexRequest');
        $repo->shouldReceive('findAll')
            ->once()
            ->with([], [], [], [], 10, [$user], 1)
            ->andReturn($paginator);

        $this->assertSame($paginator, (new ContactController($repo, $dispatcher))->index($request, $user));
    }

    public function test_store_stamps_initiated_by_and_dispatches_event(): void
    {
        $repo = Mockery::mock(ContactRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $user = Mockery::mock(UserFixture::class);
        $user->id = 21;

        $payload = ['requested_id' => 99];
        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\User\\Contact\\StoreRequest', $payload);

        $created = Mockery::mock(ContactFixture::class);
        $created->shouldReceive('toJson')->andReturn('{}');
        $repo->shouldReceive('create')
            ->once()
            ->with(['requested_id' => 99, 'initiated_by_id' => 21])
            ->andReturn($created);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ContactCreatedEvent::class));

        $response = (new ContactController($repo, $dispatcher))->store($request, $user);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_confirm_flag_sets_confirmed_at_timestamp(): void
    {
        $repo = Mockery::mock(ContactRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $user = Mockery::mock(UserFixture::class);
        $contact = Mockery::mock(ContactFixture::class);
        $updated = Mockery::mock(ContactFixture::class);

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\User\\Contact\\UpdateRequest', [
            'confirm' => true,
        ]);

        $repo->shouldReceive('update')
            ->once()
            ->with(
                $contact,
                Mockery::on(fn (array $data) => $data['confirmed_at'] instanceof Carbon
                    && ! isset($data['confirm'])),
            )
            ->andReturn($updated);

        $this->assertSame(
            $updated,
            (new ContactController($repo, $dispatcher))->update($request, $user, $contact),
        );
    }

    public function test_update_deny_flag_sets_denied_at_timestamp(): void
    {
        $repo = Mockery::mock(ContactRepositoryContract::class);
        $dispatcher = Mockery::mock(Dispatcher::class);
        $user = Mockery::mock(UserFixture::class);
        $contact = Mockery::mock(ContactFixture::class);
        $updated = Mockery::mock(ContactFixture::class);

        $request = $this->makeRequest('Polis\\Http\\Core\\Requests\\User\\Contact\\UpdateRequest', [
            'deny' => true,
        ]);

        $repo->shouldReceive('update')
            ->once()
            ->with(
                $contact,
                Mockery::on(fn (array $data) => $data['denied_at'] instanceof Carbon),
            )
            ->andReturn($updated);

        (new ContactController($repo, $dispatcher))->update($request, $user, $contact);
        $this->addToAssertionCount(1);
    }
}
