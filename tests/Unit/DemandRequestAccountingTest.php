<?php

namespace Tests\Unit;

use App\DemandRequest;
use App\DemandRequestItem;
use App\DemandRequestPayment;
use PHPUnit\Framework\TestCase;

class DemandRequestAccountingTest extends TestCase
{
    public function test_account_allocation_decreases_as_the_accountant_records_spending(): void
    {
        $submitted = $this->demand('Submitted', 5693, 0);
        $this->assertEqualsWithDelta(0, $submitted->accountOutstanding(), 0.001);
        $this->assertSame('', $submitted->paymentStatus());

        $approved = $this->demand('Approved', 5693, 0);
        $this->assertEqualsWithDelta(5693, $approved->accountOutstanding(), 0.001);
        $this->assertEqualsWithDelta(5693, $approved->owedTotal(), 0.001);
        $this->assertSame('Unpaid', $approved->paymentStatus());

        $partial = $this->demand('Partially Paid', 5693, 4598.78);
        $this->assertEqualsWithDelta(1094.22, $partial->accountOutstanding(), 0.001);
        $this->assertEqualsWithDelta(1094.22, $partial->owedTotal(), 0.001);
        $this->assertSame('Partial', $partial->paymentStatus());

        $settled = $this->demand('Completed', 5693, 5693);
        $this->assertEqualsWithDelta(0, $settled->accountOutstanding(), 0.001);
        $this->assertEqualsWithDelta(0, $settled->owedTotal(), 0.001);
        $this->assertSame('Paid', $settled->paymentStatus());
    }

    public function test_account_overspend_is_negative_but_not_an_unpaid_vendor_balance(): void
    {
        $demand = $this->demand('Completed', 100, 120);

        $this->assertEqualsWithDelta(-20, $demand->accountOutstanding(), 0.001);
        $this->assertEqualsWithDelta(0, $demand->owedTotal(), 0.001);
        $this->assertSame('Paid', $demand->paymentStatus());
    }

    public function test_cash_in_hand_draw_does_not_settle_unpaid_items(): void
    {
        $demand = $this->demand('Partially Paid', 100, 90, 10);

        $this->assertEqualsWithDelta(10, $demand->accountOutstanding(), 0.001);
        $this->assertEqualsWithDelta(10, $demand->owedTotal(), 0.001);
        $this->assertSame('Partial', $demand->paymentStatus());
    }

    public function test_untagged_account_payment_reduces_the_unspent_allocation(): void
    {
        $demand = $this->demand('Partially Paid', 100, 0);
        $demand->setRelation('payments', collect([
            new DemandRequestPayment(['pay_type' => 'Account', 'amount' => 30]),
        ]));

        $this->assertEqualsWithDelta(70, $demand->accountOutstanding(), 0.001);
        $this->assertEqualsWithDelta(70, $demand->owedTotal(), 0.001);
    }

    public function test_company_shortfall_remains_separate_from_account_surplus(): void
    {
        $demand = $this->demand('Partially Paid', 100, 60, 0, 80, 20);

        $this->assertEqualsWithDelta(40, $demand->accountOutstanding(), 0.001);
        $this->assertEqualsWithDelta(-60, $demand->companyOutstanding(), 0.001);
        $this->assertEqualsWithDelta(100, $demand->owedTotal(), 0.001);
    }

    private function demand(
        string $status,
        float $accountBudget,
        float $accountPaid,
        float $cashUsed = 0,
        float $companyBudget = 0,
        float $companyPaid = 0
    ): DemandRequest {
        $demand = new DemandRequest([
            'status' => $status,
            'estimated_total' => $accountBudget + $companyBudget,
            'cash_in_hand_used' => $cashUsed,
        ]);
        $items = collect();
        $payments = collect();

        if ($accountBudget > 0) {
            $item = new DemandRequestItem(['pay_by' => 'Account', 'estimated_total' => $accountBudget]);
            $item->id = 1;
            $items->push($item);
            if ($accountPaid > 0) {
                $payments->push(new DemandRequestPayment(['item_id' => 1, 'amount' => $accountPaid]));
            }
        }
        if ($companyBudget > 0) {
            $item = new DemandRequestItem(['pay_by' => 'Company', 'estimated_total' => $companyBudget]);
            $item->id = 2;
            $items->push($item);
            if ($companyPaid > 0) {
                $payments->push(new DemandRequestPayment(['item_id' => 2, 'amount' => $companyPaid, 'pay_type' => 'Direct']));
            }
        }

        $demand->setRelation('items', $items);
        $demand->setRelation('payments', $payments);

        return $demand;
    }
}
