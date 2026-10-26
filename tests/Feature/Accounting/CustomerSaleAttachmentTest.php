<?php

namespace Tests\Feature\Accounting;

use App\CrmCustomer;
use App\CrmWorkspace;
use App\CustomerSale;
use App\CustomerSaleAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Files attached to a customer sale on the Customers tab. */
class CustomerSaleAttachmentTest extends AccountingTestCase
{
    use BuildsMoneyIn;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_files_can_be_attached_when_adding_a_sale_and_later_from_the_files_window(): void
    {
        $customer = $this->createCustomer('Files Customer');
        $this->post(route('crm.customer_sales.store'), [
            'customer_id' => $customer->id, 'order_number' => 'SO-FILES-1', 'order_date' => '2026-10-20', 'item_name' => 'Boxes',
            'quantity' => 5, 'unit' => 'Pieces', 'unit_price' => 10, 'currency' => 'AED', 'order_status' => 'Confirmed',
            'attachments' => [UploadedFile::fake()->create('purchase-order.pdf', 120, 'application/pdf')],
        ])->assertSessionHasNoErrors()->assertSessionHas('success', fn ($m) => str_contains($m, '1 file(s) attached'));
        $sale = CustomerSale::where('order_number', 'SO-FILES-1')->firstOrFail();
        $this->assertSame(['purchase-order.pdf'], $sale->attachments->pluck('original_name')->all());

        $this->from(route('crm.customer_sales.index', ['customer_id' => $customer->id]))
            ->post(route('crm.customer_sales.attachments.store', $sale->id), ['attachments' => [
                UploadedFile::fake()->image('delivery-note.jpg'),
                UploadedFile::fake()->create('invoice.xlsx', 30),
            ]])->assertSessionHasNoErrors()->assertSessionHas('success', '2 file(s) attached to SO-FILES-1.');

        $files = $sale->fresh()->attachments;
        $this->assertSame(['purchase-order.pdf', 'delivery-note.jpg', 'invoice.xlsx'], $files->pluck('original_name')->all());
        $this->assertSame($this->admin->id, (int) $files[0]->uploaded_by);
        foreach ($files as $file) Storage::disk('local')->assertExists($file->path);

        // The list shows the count and the Files window data.
        $page = $this->get(route('crm.customer_sales.index', ['customer_id' => $customer->id]))->assertOk()->assertSee('3 files');
        $page->assertSee(trim(json_encode(route('crm.customer_sales.attachments.show', [$sale->id, $files[0]->id])), '"'), false);

        // Opening a file streams it inline.
        $this->get(route('crm.customer_sales.attachments.show', [$sale->id, $files[0]->id]))->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="purchase-order.pdf"');

        // Editing the sale can add more.
        $this->put(route('crm.customer_sales.update', $sale->id), [
            'customer_id' => $customer->id, 'order_number' => 'SO-FILES-1', 'order_date' => '2026-10-20', 'item_name' => 'Boxes',
            'quantity' => 5, 'unit' => 'Pieces', 'unit_price' => 10, 'currency' => 'AED', 'order_status' => 'Confirmed',
            'attachments' => [UploadedFile::fake()->create('signed-invoice.pdf', 50, 'application/pdf')],
        ])->assertSessionHasNoErrors();
        $this->assertSame(4, $sale->fresh()->attachments->count());

        // Removing one deletes the file and logs it.
        $first = $files[0];
        $this->from(route('crm.customer_sales.index', ['customer_id' => $customer->id]))
            ->delete(route('crm.customer_sales.attachments.destroy', [$sale->id, $first->id]))->assertSessionHas('success');
        $this->assertNull(CustomerSaleAttachment::find($first->id));
        Storage::disk('local')->assertMissing($first->path);
        $this->assertDatabaseHas('crm_deletion_logs', ['entity_type' => 'customer_sale_attachment', 'entity_id' => $first->id]);
    }

    public function test_uploads_are_validated(): void
    {
        $sale = $this->createSale($this->createCustomer('Validate Files Customer'));
        $upload = fn (array $data) => $this->from(route('crm.customer_sales.index'))->post(route('crm.customer_sales.attachments.store', $sale->id), $data);

        $upload([])->assertSessionHasErrors('attachments');
        $upload(['attachments' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('attachments.0');
        $upload(['attachments' => [UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf')]])->assertSessionHasErrors('attachments.0');
        $upload(['attachments' => array_fill(0, 11, UploadedFile::fake()->create('a.pdf', 1, 'application/pdf'))])->assertSessionHasErrors('attachments');
        $this->assertSame(0, $sale->attachments()->count());
    }

    public function test_another_workspaces_sale_files_cannot_be_reached(): void
    {
        $sale = $this->createSale($this->createCustomer('Own Files Customer'));
        $this->post(route('crm.customer_sales.attachments.store', $sale->id), ['attachments' => [UploadedFile::fake()->create('mine.pdf', 5, 'application/pdf')]]);
        $file = $sale->attachments()->firstOrFail();

        $other = CrmWorkspace::create(['name' => 'Other Files', 'slug' => 'other-files-'.uniqid(), 'is_active' => true]);
        $foreignCustomer = CrmCustomer::create(['workspace_id' => $other->id, 'name' => 'Foreign', 'country' => 'UAE', 'currency' => 'AED', 'customer_type' => 'sales']);
        $foreignSale = CustomerSale::create(['workspace_id' => $other->id, 'customer_id' => $foreignCustomer->id, 'order_number' => 'SO-FOREIGN', 'order_date' => '2026-10-01',
            'item_name' => 'X', 'quantity' => 1, 'unit' => 'Pieces', 'unit_price' => 1, 'subtotal' => 1, 'total_amount' => 1, 'paid_amount' => 0, 'balance_amount' => 1,
            'currency' => 'AED', 'payment_status' => 'Unpaid', 'order_status' => 'Confirmed']);

        $this->post(route('crm.customer_sales.attachments.store', $foreignSale->id), ['attachments' => [UploadedFile::fake()->create('x.pdf', 5, 'application/pdf')]])->assertNotFound();
        $this->get(route('crm.customer_sales.attachments.show', [$foreignSale->id, $file->id]))->assertNotFound();
        $this->delete(route('crm.customer_sales.attachments.destroy', [$foreignSale->id, $file->id]))->assertNotFound();
        $this->assertNotNull($file->fresh());
    }
}
