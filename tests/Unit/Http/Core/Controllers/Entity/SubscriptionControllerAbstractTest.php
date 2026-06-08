<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Http\Core\Controllers\Entity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Mockery;
use Polis\Contracts\Models\IsAnEntityContract;
use Polis\Contracts\Repositories\Subscription\SubscriptionRepositoryContract;
use Polis\Contracts\Services\EntitySubscriptionCreationServiceContract;
use Polis\Tests\Fixtures\Controllers\Entity\SubscriptionController;
use Polis\Tests\Fixtures\Models\Subscription as SubscriptionFixture;
use Polis\Tests\Unit\Http\Core\Controllers\ControllerTestCase;

/**
 * Unit coverage for Entity\SubscriptionControllerAbstract.
 *
 * index() injects subscriber_* filters keyed on the entity (via
 * morphRelationName). store() delegates to the entity subscription
 * creation service. update() handles a "cancel: true" shortcut that
 * translates to canceled_at = now().
 */
final class SubscriptionControllerAbstractTest extends ControllerTestCase
{
    public function test_index_appends_subscriber_filters_for_the_entity(): void
    {
        $repo = Mockery::mock(SubscriptionRepositoryContract::class);
        $creator = Mockery::mock(EntitySubscriptionCreationServiceContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);
        $entity->id = 21;
        $entity->shouldReceive('morphRelationName')->andReturn('users');

        $request = $this->makeIndexRequest('App\\Http\\Core\\Requests\\Entity\\Subscription\\IndexRequest');
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $repo->shouldReceive('findAll')
            ->once()
            ->with(
                Mockery::on(fn (array $f) => in_array(['subscriber_id', '=', 21], $f, true)
                    && in_array(['subscriber_type', '=', 'users'], $f, true)),
                [], [], [], 10, [], 1,
            )
            ->andReturn($paginator);

        $this->assertSame($paginator, (new SubscriptionController($repo, $creator))->index($request, $entity));
    }

    public function test_store_delegates_to_entity_subscription_creation_service(): void
    {
        $repo = Mockery::mock(SubscriptionRepositoryContract::class);
        $creator = Mockery::mock(EntitySubscriptionCreationServiceContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);

        $payload = ['membership_plan_rate_id' => 7, 'payment_method_id' => 99];
        $request = $this->makeRequest('App\\Http\\Core\\Requests\\Entity\\Subscription\\StoreRequest', $payload);

        $created = Mockery::mock(SubscriptionFixture::class);
        $created->shouldReceive('toJson')->andReturn('{"id":1}');
        $creator->shouldReceive('createSubscription')
            ->once()
            ->with($entity, $payload)
            ->andReturn($created);

        $response = (new SubscriptionController($repo, $creator))->store($request, $entity);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_update_translates_cancel_true_into_canceled_at_timestamp(): void
    {
        $repo = Mockery::mock(SubscriptionRepositoryContract::class);
        $creator = Mockery::mock(EntitySubscriptionCreationServiceContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);

        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Entity\\Subscription\\UpdateRequest',
            ['cancel' => true],
        );

        $sub = new SubscriptionFixture;
        $updated = Mockery::mock(SubscriptionFixture::class);
        $repo->shouldReceive('update')
            ->once()
            ->with(
                $sub,
                Mockery::on(fn (array $data) => isset($data['canceled_at'])
                    && ! isset($data['cancel'])),
            )
            ->andReturn($updated);

        $this->assertSame(
            $updated,
            (new SubscriptionController($repo, $creator))->update($request, $entity, $sub),
        );
    }

    public function test_update_passes_other_fields_through_unchanged(): void
    {
        $repo = Mockery::mock(SubscriptionRepositoryContract::class);
        $creator = Mockery::mock(EntitySubscriptionCreationServiceContract::class);
        $entity = Mockery::mock(IsAnEntityContract::class);

        $payload = ['payment_method_id' => 12];
        $request = $this->makeRequest(
            'App\\Http\\Core\\Requests\\Entity\\Subscription\\UpdateRequest',
            $payload,
        );

        $sub = new SubscriptionFixture;
        $updated = Mockery::mock(SubscriptionFixture::class);
        $repo->shouldReceive('update')->once()->with($sub, $payload)->andReturn($updated);

        $this->assertSame(
            $updated,
            (new SubscriptionController($repo, $creator))->update($request, $entity, $sub),
        );
    }
}
