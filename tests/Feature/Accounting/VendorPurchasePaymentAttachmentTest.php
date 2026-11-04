<?php

namespace Tests\Feature\Accounting;

use App\Support\CrmWorkspaceContext;
use App\Vendor;
use App\VendorPurchase;
use Illuminate\Http\UploadedFile;

/** The attachment field in the Payment Summary of the Add / Edit Vendor Purchase form. */
class VendorPurchasePaymentAttachmentTest extends AccountingTestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $path) {
            if (is_file(public_path($path))) {
                @unlink(public_path($path));
            }
        }
        parent::tearDown();
    }

    private function formData(Vendor $vendor, array $extra = []): array
    {
        return array_merge([
            'vendor_id' => $vendor->id,
            'purchase_date' => '2026-10-10',
            'payment_status' => 'Unpaid',
            'currency' => 'AED',
            'items' => [['category' => 'Paper', 'item_name' => 'Board', 'quantity' => 1, 'unit_price' => 100]],
        ], $extra);
    }

    private function vendor(): Vendor
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return Vendor::create(['name' => 'Attachment Vendor']);
    }

    private function purchase(Vendor $vendor): VendorPurchase
    {
        CrmWorkspaceContext::set($this->workspace->id);

        return VendorPurchase::create([
            'vendor_id' => $vendor->id, 'vendor_name' => $vendor->name, 'purchase_date' => '2026-10-10', 'item_name' => 'Board',
            'category' => 'Paper', 'currency' => 'AED', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 100, 'subtotal' => 100,
            'total_amount' => 100, 'paid_amount' => 0, 'balance_amount' => 100, 'payment_status' => 'Unpaid',
        ]);
    }

    private function remember(VendorPurchase $purchase): VendorPurchase
    {
        $purchase->refresh();
        if ($purchase->payment_attachment_path) {
            $this->files[] = $purchase->payment_attachment_path;
        }

        return $purchase;
    }

    public function test_editing_a_purchase_saves_the_file_on_the_record_and_shows_it_on_the_form(): void
    {
        $vendor = $this->vendor();
        $purchase = $this->purchase($vendor);

        $this->get(route('crm.vendor_purchases.edit', $purchase->id))->assertOk()->assertSee('name="payment_attachment"', false);

        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->create('bank-slip.pdf', 50, 'application/pdf'),
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $this->remember($purchase);
        $this->assertSame('bank-slip.pdf', $purchase->payment_attachment_name);
        $this->assertFileExists(public_path($purchase->payment_attachment_path));
        $this->assertNull($purchase->attachment_path, 'the invoice attachment is a separate field');

        $this->get(route('crm.vendor_purchases.edit', $purchase->id))->assertOk()->assertSee('bank-slip.pdf');
    }

    public function test_saving_without_a_new_file_keeps_the_current_one_and_a_new_file_replaces_it(): void
    {
        $vendor = $this->vendor();
        $purchase = $this->purchase($vendor);
        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->create('first.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasNoErrors();
        $firstPath = $this->remember($purchase)->payment_attachment_path;

        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor))->assertSessionHasNoErrors();
        $this->assertSame($firstPath, $this->remember($purchase)->payment_attachment_path);

        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->image('second.jpg'),
        ]))->assertSessionHasNoErrors();
        $this->remember($purchase);
        $this->assertSame('second.jpg', $purchase->payment_attachment_name);
        $this->assertNotSame($firstPath, $purchase->payment_attachment_path);
        $this->assertFileDoesNotExist(public_path($firstPath), 'the replaced file is removed from disk');
        $this->assertFileExists(public_path($purchase->payment_attachment_path));
    }

    public function test_a_short_csv_detected_as_plain_text_is_accepted_but_a_txt_file_is_not(): void
    {
        $vendor = $this->vendor();
        $purchase = $this->purchase($vendor);

        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->createWithContent('notes.txt', "just text\n"),
        ]))->assertSessionHasErrors(['payment_attachment' => 'The payment attachment must be a PDF, image, Word, Excel or CSV file.']);
        $this->assertNull($this->remember($purchase)->payment_attachment_path);

        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->createWithContent('short.csv', "Name;Amount\nA;1\n"),
        ]))->assertSessionHasNoErrors();
        $this->assertSame('short.csv', $this->remember($purchase)->payment_attachment_name);
    }

    public function test_adding_a_purchase_saves_the_file_too(): void
    {
        $vendor = $this->vendor();

        $this->post(route('crm.vendor_purchases.store'), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        CrmWorkspaceContext::set($this->workspace->id);
        $purchase = $this->remember(VendorPurchase::where('vendor_id', $vendor->id)->firstOrFail());
        $this->assertSame('proof.pdf', $purchase->payment_attachment_name);
    }

    public function test_only_allowed_file_types_are_accepted(): void
    {
        $vendor = $this->vendor();
        $purchase = $this->purchase($vendor);

        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->create('script.php', 1, 'text/x-php'),
        ]))->assertSessionHasErrors('payment_attachment');

        $this->assertNull($this->remember($purchase)->payment_attachment_path);
    }

    public function test_deleting_the_purchase_removes_the_file(): void
    {
        $vendor = $this->vendor();
        $purchase = $this->purchase($vendor);
        $this->put(route('crm.vendor_purchases.update', $purchase->id), $this->formData($vendor, [
            'payment_attachment' => UploadedFile::fake()->create('gone.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasNoErrors();
        $path = $this->remember($purchase)->payment_attachment_path;
        $this->assertFileExists(public_path($path));

        $this->delete(route('crm.vendor_purchases.destroy', $purchase->id))->assertRedirect();

        $this->assertFileDoesNotExist(public_path($path));
    }
}
