<?php

declare(strict_types=1);

namespace Polis\Providers;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\ServiceProvider;
use Polis\Validators\ArticleVersion\SelectedIterationBelongsToArticleValidator;
use Polis\Validators\ForgotPassword\TokenIsNotExpiredValidator;
use Polis\Validators\ForgotPassword\UserOwnsTokenValidator;
use Polis\Validators\InvitationTokenIsValidValidator;
use Polis\Validators\NotPresentValidator;
use Polis\Validators\OwnedByValidator;
use Polis\Validators\Subscription\MembershipPlanRateIsActiveValidator;
use Polis\Validators\Subscription\PaymentMethodIsOwnedByEntityValidator;

/**
 * Class AppValidatorProvider
 */
abstract class BaseValidatorProvider extends ServiceProvider
{
    /**
     * Registers all application validators
     */
    public function boot(): void
    {
        /** @var Factory $validator */
        $validator = $this->app->make(Factory::class);

        $validator->extend('token_is_not_expired', TokenIsNotExpiredValidator::class);
        $validator->extend('user_owns_token', UserOwnsTokenValidator::class);
        $validator->extend('not_present', NotPresentValidator::class);
        $validator->extend(InvitationTokenIsValidValidator::KEY, InvitationTokenIsValidValidator::class);
        $validator->extend(MembershipPlanRateIsActiveValidator::KEY, MembershipPlanRateIsActiveValidator::class);
        $validator->extend(OwnedByValidator::KEY, OwnedByValidator::class);
        $validator->extend(PaymentMethodIsOwnedByEntityValidator::KEY, PaymentMethodIsOwnedByEntityValidator::class);
        $validator->extend(SelectedIterationBelongsToArticleValidator::KEY, SelectedIterationBelongsToArticleValidator::class);

        $this->registerValidators($validator);
    }

    /**
     * Register any of your application specific validators here
     */
    abstract public function registerValidators(Factory $validatorFactory): void;
}
