<?php

declare(strict_types=1);

namespace Polis\Tests\Fixtures\Models;

use Polis\Models\BaseModelAbstract;
use Polis\Tests\Fixtures\Traits\MockeryFriendlyAttributesTrait;

/**
 * Fixture stub for App\Models\Payment\PaymentMethod.
 *
 * Originally a parentless stub. Now exercised by the Entity
 * PaymentMethodControllerAbstract whose update()/delete() forward the
 * model into BaseModelAbstract-typed repository calls, so it extends
 * BaseModelAbstract. See Category.php for the shared rationale.
 */
class PaymentMethod extends BaseModelAbstract
{
    use MockeryFriendlyAttributesTrait;
}

if (! class_exists(\App\Models\Payment\PaymentMethod::class, false)) {
    class_alias(
        PaymentMethod::class,
        \App\Models\Payment\PaymentMethod::class,
    );
}
