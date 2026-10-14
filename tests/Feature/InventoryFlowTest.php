<?php

namespace Tests\Feature;

use App\CrmInventoryItem;
use App\CrmInventoryMovement;
use App\CrmUser;
use App\DemandRequest;
use App\DesignJob;
use App\DesignJobCard;
use App\Http\Controllers\Crm\DesignJobCardController;
use App\Services\InventoryService;
use App\Support\CrmWorkspaceContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

/** Demand Request "received" → stock IN; Job Card procurement rows → stock OUT; inline item edit/delete. */
class InventoryFlowTest extends TestCase
{
    private const WS = 1;

    protected function setUp(): void { parent::setUp(); DB::beginTransaction(); }
    protected function tearDown(): void { DB::rollBack(); parent::tearDown(); }

    private function admin(): CrmUser
    {
        $u = CrmUser::create(['name' => 'Inv Admin', 'email' => 'inv-'.uniqid('', true).'@example.com', 'password' => Hash::make('x'), 'role' => 'admin']);
        $u->workspaces()->attach(self::WS, ['role' => 'admin']);
        return $u;
    }

    private function as(CrmUser $u) { return $this->actingAs($u, 'crm')->withSession(['crm_workspace_id' => self::WS]); }

    private function demand(CrmUser $u, array $lines): DemandRequest
    {
        $dr = DemandRequest::create(['workspace_id' => self::WS, 'created_by' => $u->id, 'request_no' => random_int(900000, 999999), 'request_date' => now()->toDateString(), 'status' => 'Approved', 'estimated_total' => 0]);
        foreach ($lines as $i => $l) $dr->items()->create($l + ['position' => $i + 1, 'pay_by' => 'Company', 'estimated_total' => 0]);
        return $dr;
    }

    public function test_receiving_a_demand_line_stocks_it_in_and_undoing_or_editing_keeps_the_ledger_in_step(): void
    {
        $u = $this->admin();
        $name = 'Art Matte Paper '.uniqid();
        $dr = $this->demand($u, [['category' => 'Stock / Inventory', 'description' => $name, 'specification' => '100x70', 'gsm' => '150', 'qty' => '6,250', 'estimated_price' => 0.6, 'vendor_name' => 'Silver Star']]);
        $line = $dr->items()->first();

        $this->as($u)->postJson(route('crm.demand_requests.item_receive', [$dr->id, $line->id]))->assertOk()->assertJson(['received' => true]);
        $this->assertNull(CrmInventoryItem::withoutGlobalScopes()->where('name', $name)->first(), 'receiving alone does not touch inventory');
        // explicit entry (user confirms / corrects the values)
        $this->as($u)->postJson(route('crm.demand_requests.item_stock_in', [$dr->id, $line->id]), ['name' => $name, 'paper_size' => '100x70', 'gsm' => '150', 'unit' => 'sheets', 'qty' => 6250, 'unit_cost' => 0.6])->assertOk();
        $item = CrmInventoryItem::withoutGlobalScopes()->where('name', $name)->first();
        $this->assertNotNull($item, 'an inventory item is created from the demand line');
        $this->assertSame(6250.0, (float) $item->quantity);
        $this->assertSame('100x70', $item->paper_size);
        $this->assertSame('150', $item->gsm);
        $this->assertSame('sheets', $item->unit);
        $this->as($u)->postJson(route('crm.demand_requests.item_stock_in', [$dr->id, $line->id]), ['name' => $name, 'qty' => 1])->assertStatus(422); // not twice
        $this->assertSame(1, CrmInventoryMovement::where('inventory_item_id', $item->id)->where('type', 'in')->count());

        // receiving twice does not double-count
        $this->assertNull(app(InventoryService::class)->receiveDemandLine($line->fresh(), $u->id));
        $this->assertSame(6250.0, (float) $item->fresh()->quantity);

        // inline edit of a received line's qty adjusts stock by the difference
        $this->as($u)->putJson(route('crm.demand_requests.item_update', [$dr->id, $line->id]), ['description' => $name, 'specification' => '100x70', 'gsm' => '150', 'qty' => '6000', 'estimated_price' => 0.6, 'vat_percentage' => 5, 'pay_by' => 'Company'])->assertOk();
        $this->assertSame(6000.0, (float) $item->fresh()->quantity);
        $this->assertSame(3780.0, (float) $line->fresh()->estimated_total, '6000 × 0.60 + 5% VAT');
        $this->assertSame(3780.0, (float) $dr->fresh()->estimated_total);

        // undo received → stock goes back out
        $this->as($u)->postJson(route('crm.demand_requests.item_receive', [$dr->id, $line->id]))->assertOk()->assertJson(['received' => false]);
        $this->assertSame(0.0, (float) $item->fresh()->quantity);

        // stock in again (fresh entry), then delete the row → reversed and row gone
        $this->as($u)->postJson(route('crm.demand_requests.item_stock_in', [$dr->id, $line->id]), ['name' => $name, 'paper_size' => '100x70', 'gsm' => '150', 'qty' => 6000])->assertOk();
        $this->assertSame(6000.0, (float) $item->fresh()->quantity);
        $this->assertSame(1, CrmInventoryItem::withoutGlobalScopes()->where('name', $name)->count(), 'matched the existing item, no duplicate');
        $this->as($u)->deleteJson(route('crm.demand_requests.item_destroy', [$dr->id, $line->id]))->assertOk();
        $this->assertSame(0.0, (float) $item->fresh()->quantity);
        $this->assertSame(0, $dr->items()->count());
    }

    public function test_job_card_materials_consume_stock_and_refuse_more_than_available(): void
    {
        $u = $this->admin();
        CrmWorkspaceContext::class; // keep the workspace scope resolvable
        $this->as($u);
        $item = CrmInventoryItem::create(['workspace_id' => self::WS, 'name' => 'Food Board '.uniqid(), 'unit' => 'sheets', 'quantity' => 500]);
        $job = DesignJob::create(['workspace_id' => self::WS, 'designer_id' => $u->id, 'job_number' => 'JT-'.random_int(1000, 9999), 'title' => 'Boxes', 'status' => 'designing']);
        $card = DesignJobCard::create(['design_job_id' => $job->id]);
        $sync = new ReflectionMethod(DesignJobCardController::class, 'syncMaterials');
        $sync->setAccessible(true);
        $ctl = new DesignJobCardController();

        $sync->invoke($ctl, $card, [['item' => $item->name, 'inventory_item_id' => $item->id, 'qty' => '200']]);
        $this->assertSame(300.0, (float) $item->fresh()->quantity);
        $this->assertSame(200.0, (float) $card->materials()->first()->consumed_qty);
        $mv = CrmInventoryMovement::where('inventory_item_id', $item->id)->where('type', 'out')->first();
        $this->assertSame($job->job_number, $mv->job_number);

        // re-saving with a smaller qty returns the difference
        $sync->invoke($ctl, $card, [['item' => $item->name, 'inventory_item_id' => $item->id, 'qty' => '150']]);
        $this->assertSame(350.0, (float) $item->fresh()->quantity);

        // asking for more than available is refused and nothing moves
        try {
            $sync->invoke($ctl, $card, [['item' => $item->name, 'inventory_item_id' => $item->id, 'qty' => '1000']]);
            $this->fail('expected a validation error');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Not enough stock', $e->getMessage() ?: implode(' ', $e->errors()['materials']));
        }
        $this->assertSame(350.0, (float) $item->fresh()->quantity);

        // removing the material gives everything back
        $sync->invoke($ctl, $card, []);
        $this->assertSame(500.0, (float) $item->fresh()->quantity);
    }
}
