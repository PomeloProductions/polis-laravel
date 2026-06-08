<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;

/**
 * Fixture stub for App\Models\Payment\Payment.
 *
 * Required because StripePaymentServiceContract::createPayment() return
 * type and reversePayment()/issuePartialRefund() parameters reference
 * App\Models\Payment\Payment. Extends BaseModelAbstract so repositories
 * (notably PaymentRepository syncChildModels) can pass Payment doubles
 * as the BaseModelAbstract $parentModel argument. See User.php for the
 * broader rationale.
 */
class Payment extends BaseModelAbstract
{
    use \Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Payment\Payment::class, false)) {
    class_alias(
        Payment::class,
        \App\Models\Payment\Payment::class,
    );
}
