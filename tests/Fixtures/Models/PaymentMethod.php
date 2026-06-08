<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

/**
 * Fixture stub for App\Models\Payment\PaymentMethod.
 *
 * See User.php for the rationale. AllowDynamicProperties lets tests set
 * fields like `payment_method_type` directly on instances.
 */
#[\AllowDynamicProperties]
class PaymentMethod {}

if (! class_exists(\App\Models\Payment\PaymentMethod::class, false)) {
    class_alias(
        PaymentMethod::class,
        \App\Models\Payment\PaymentMethod::class,
    );
}
