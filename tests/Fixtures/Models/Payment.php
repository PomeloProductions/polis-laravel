<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Payment\Payment.
 *
 * Required because StripePaymentServiceContract::createPayment() return
 * type and reversePayment()/issuePartialRefund() parameters reference
 * App\Models\Payment\Payment. See User.php for the rationale.
 */
class Payment {}

if (! class_exists(\App\Models\Payment\Payment::class, false)) {
    class_alias(
        Payment::class,
        \App\Models\Payment\Payment::class,
    );
}
