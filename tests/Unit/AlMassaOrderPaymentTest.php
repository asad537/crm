<?php

namespace Tests\Unit;

use App\CrmEmail;
use App\CrmOrderItem;
use App\CrmOrderPayment;
use PHPUnit\Framework\TestCase;

class AlMassaOrderPaymentTest extends TestCase
{
    public function test_unpaid_partial_and_paid_are_derived_from_vat_inclusive_total(): void
    {
        $order = new CrmEmail(['vat_percentage' => 5]);
        $order->setRelation('orderItems', collect([
            new CrmOrderItem(['line_total' => 100]),
        ]));
        $order->setRelation('orderPayments', collect());

        $this->assertSame(105.0, $order->orderInvoiceTotal());
        $this->assertSame('Unpaid', $order->orderPaymentLabel());
        $this->assertSame(105.0, $order->orderBalanceDue());

        $order->setRelation('orderPayments', collect([
            new CrmOrderPayment(['amount' => 40]),
        ]));
        $this->assertSame('Partial', $order->orderPaymentLabel());
        $this->assertSame(65.0, $order->orderBalanceDue());

        $order->setRelation('orderPayments', collect([
            new CrmOrderPayment(['amount' => 40]),
            new CrmOrderPayment(['amount' => 65]),
        ]));
        $this->assertSame('Paid', $order->orderPaymentLabel());
        $this->assertSame(0.0, $order->orderBalanceDue());
    }

    public function test_legacy_opening_balance_is_counted_without_a_fake_receipt(): void
    {
        $order = new CrmEmail([
            'order_price' => 50,
            'order_quantity' => 2,
            'vat_percentage' => 0,
            'order_paid_opening_amount' => 25,
        ]);
        $order->setRelation('orderItems', collect());
        $order->setRelation('orderPayments', collect());

        $this->assertSame(25.0, $order->orderReceivedTotal());
        $this->assertSame(75.0, $order->orderBalanceDue());
        $this->assertSame('Partial', $order->orderPaymentLabel());
    }
}
