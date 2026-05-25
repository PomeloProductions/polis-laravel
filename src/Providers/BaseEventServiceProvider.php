<?php

declare(strict_types=1);

namespace Polis\Providers;

use App\Listeners\Organization\OrganizationManagerCreatedListener;
use App\Listeners\User\Contact\ContactCreatedListener;
use App\Listeners\User\SignUpListener;
use App\Listeners\Vote\VoteCreatedListener;
use App\Models\Collection\CollectionItem;
use App\Models\Payment\PaymentMethod;
use App\Models\User\ArticleNote;
use App\Models\User\User;
use App\Models\Wiki\Article;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Polis\Events\Article\ArticleVersionCreatedEvent;
use Polis\Events\Messaging\MessageCreatedEvent;
use Polis\Events\Messaging\MessageSentEvent;
use Polis\Events\Organization\OrganizationManagerCreatedEvent;
use Polis\Events\Payment\DefaultPaymentMethodSetEvent;
use Polis\Events\Payment\PaymentReversedEvent;
use Polis\Events\Statistic\StatisticCreatedEvent;
use Polis\Events\Statistic\StatisticDeletedEvent;
use Polis\Events\Statistic\StatisticUpdatedEvent;
use Polis\Events\User\Contact\ContactCreatedEvent;
use Polis\Events\User\ForgotPasswordEvent;
use Polis\Events\User\InvitationAcceptedEvent;
use Polis\Events\User\SignUpEvent;
use Polis\Events\User\UserMergeEvent;
use Polis\Events\Vote\VoteCreatedEvent;
use Polis\Listeners\Article\ArticleVersionCreatedListener;
use Polis\Listeners\Messaging\MessageCreatedListener;
use Polis\Listeners\Messaging\MessageSentListener;
use Polis\Listeners\Payment\DefaultPaymentMethodSetListener;
use Polis\Listeners\Statistic\StatisticCreatedListener;
use Polis\Listeners\Statistic\StatisticDeletedListener;
use Polis\Listeners\Statistic\StatisticUpdatedListener;
use Polis\Listeners\User\ForgotPasswordListener;
use Polis\Listeners\User\InvitationAcceptedListener;
use Polis\Listeners\User\UserMerge\UserBallotCompletionsMergeListener;
use Polis\Listeners\User\UserMerge\UserCreatedArticlesMergeListener;
use Polis\Listeners\User\UserMerge\UserCreatedIterationsMergeListener;
use Polis\Listeners\User\UserMerge\UserMessagesMergeListener;
use Polis\Listeners\User\UserMerge\UserPropertiesMergeListener;
use Polis\Listeners\User\UserMerge\UserSubscriptionsMergeListener;
use Polis\Observers\AggregatedModelObserver;
use Polis\Observers\IndexableModelObserver;
use Polis\Observers\Payment\PaymentMethodObserver;

/**
 * Class EventServiceProvider
 */
abstract class BaseEventServiceProvider extends ServiceProvider
{
    /**
     * Gets all listeners and events for the whole app
     */
    public function listens(): array
    {
        return array_merge([
            ArticleVersionCreatedEvent::class => [
                ArticleVersionCreatedListener::class,
            ],
            ContactCreatedEvent::class => [
                ContactCreatedListener::class,
            ],
            DefaultPaymentMethodSetEvent::class => [
                DefaultPaymentMethodSetListener::class,
            ],
            ForgotPasswordEvent::class => [
                ForgotPasswordListener::class,
            ],
            InvitationAcceptedEvent::class => [
                InvitationAcceptedListener::class,
            ],
            MessageCreatedEvent::class => [
                MessageCreatedListener::class,
            ],
            MessageSentEvent::class => [
                MessageSentListener::class,
            ],
            OrganizationManagerCreatedEvent::class => [
                OrganizationManagerCreatedListener::class,
            ],
            PaymentReversedEvent::class => [

            ],
            SignUpEvent::class => [
                SignUpListener::class,
            ],
            UserMergeEvent::class => array_merge([
                UserBallotCompletionsMergeListener::class,
                UserCreatedArticlesMergeListener::class,
                UserCreatedIterationsMergeListener::class,
                UserMessagesMergeListener::class,
                UserPropertiesMergeListener::class,
                UserSubscriptionsMergeListener::class,
            ], $this->getAppUserMergeListeners()),
            VoteCreatedEvent::class => [
                VoteCreatedListener::class,
            ],
            StatisticUpdatedEvent::class => [
                StatisticUpdatedListener::class,
            ],
            StatisticCreatedEvent::class => [
                StatisticCreatedListener::class,
            ],
            StatisticDeletedEvent::class => [
                StatisticDeletedListener::class,
            ],
        ], $this->getAppListenerMapping());
    }

    /**
     * Gets all application level event and mappings
     */
    abstract public function getAppListenerMapping(): array;

    /**
     * Gets all application specific listeners for when a user is merged within the Athenia pipeline
     */
    abstract public function getAppUserMergeListeners(): array;

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Article::observe(IndexableModelObserver::class);
        User::observe(IndexableModelObserver::class);
        PaymentMethod::observe(PaymentMethodObserver::class);
        CollectionItem::observe(AggregatedModelObserver::class);
        ArticleNote::observe(AggregatedModelObserver::class);

        $this->registerObservers();
    }

    /**
     * Registers any application specific observers
     */
    abstract public function registerObservers(): void;
}
