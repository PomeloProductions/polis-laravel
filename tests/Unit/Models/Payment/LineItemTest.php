<?php

declare(strict_types=1);

namespace Polis\Tests\Unit\Models\Payment;

use App\Models\Payment\LineItem;
use Polis\Tests\TestCase;

/**
 * Class LineItemTest
 */
final class LineItemTest extends TestCase
{
    public function test_item(): void
    {
        $model = new LineItem;
        $relation = $model->item();

        $this->assertEquals('line_items.item_id', $relation->getQualifiedForeignKeyName());
        $this->assertEquals('item_type', $relation->getMorphType());
    }

    public function test_payment(): void
    {
        $model = new LineItem;
        $relation = $model->payment();

        $this->assertEquals('payments.id', $relation->getQualifiedOwnerKeyName());
        $this->assertEquals('line_items.payment_id', $relation->getQualifiedForeignKeyName());
    }
}
