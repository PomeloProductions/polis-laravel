<?php

declare(strict_types=1);

namespace Polis\Providers;

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
use Polis\Listeners\Organization\OrganizationManagerCreatedListener;
use Polis\Listeners\Payment\DefaultPaymentMethodSetListener;
use Polis\Listeners\Statistic\StatisticCreatedListener;
use Polis\Listeners\Statistic\StatisticDeletedListener;
use Polis\Listeners\Statistic\StatisticUpdatedListener;
use Polis\Listeners\User\Contact\ContactCreatedListener;
use Polis\Listeners\User\ForgotPasswordListener;
use Polis\Listeners\User\InvitationAcceptedListener;
use Polis\Listeners\User\SignUpListener;
use Polis\Listeners\User\UserMerge\UserBallotCompletionsMergeListener;
use Polis\Listeners\User\UserMerge\UserCreatedArticlesMergeListener;
use Polis\Listeners\User\UserMerge\UserCreatedIterationsMergeListener;
use Polis\Listeners\User\UserMerge\UserMessagesMergeListener;
use Polis\Listeners\User\UserMerge\UserPropertiesMergeListener;
use Polis\Listeners\User\UserMerge\UserSubscriptionsMergeListener;
use Polis\Listeners\Vote\VoteCreatedListener;
use Polis\Observers\AggregatedModelObserver;
use Polis\Observers\IndexableModelObserver;
use Polis\Observers\Payment\PaymentMethodObserver;

/**
 * Base event service provider for polis-laravel.
 *
 * Auto-bind behaviour
 * -------------------
 * Observer registrations (`Article::observe(...)` etc.) resolve the model
 * FQN via {@see BaseServiceProvider::resolveConsumerOrPackage()}: the
 * consumer's `App\Models\...` subclass is preferred and the package's
 * `Polis\Models\...` concrete is used as the fallback.
 *
 * All listener FQNs in this provider reference `Polis\Listeners\...` only
 * (the package ships concrete listeners and consumer apps add their own
 * via {@see BaseEventServiceProvider::getAppListenerMapping()}). No
 * listener-side shimming is required.
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

        $articleClass = BaseServiceProvider::resolveConsumerOrPackage(
            Article::class,
            \Polis\Models\Wiki\Article::class,
        );
        $userClass = BaseServiceProvider::resolveConsumerOrPackage(
            User::class,
            \Polis\Models\User\User::class,
        );
        $paymentMethodClass = BaseServiceProvider::resolveConsumerOrPackage(
            PaymentMethod::class,
            \Polis\Models\Payment\PaymentMethod::class,
        );
        $collectionItemClass = BaseServiceProvider::resolveConsumerOrPackage(
            CollectionItem::class,
            \Polis\Models\Collection\CollectionItem::class,
        );
        $articleNoteClass = BaseServiceProvider::resolveConsumerOrPackage(
            ArticleNote::class,
            \Polis\Models\User\ArticleNote::class,
        );

        $articleClass::observe(IndexableModelObserver::class);
        $userClass::observe(IndexableModelObserver::class);
        $paymentMethodClass::observe(PaymentMethodObserver::class);
        $collectionItemClass::observe(AggregatedModelObserver::class);
        $articleNoteClass::observe(AggregatedModelObserver::class);

        $this->registerObservers();
    }

    /**
     * Registers any application specific observers
     */
    abstract public function registerObservers(): void;
}
