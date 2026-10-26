<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmPurchaseInvoiceItem extends Model
{
    protected $table = 'crm_purchase_invoice_items';

    /** Category every fixed-asset line gets; these lines go to the balance sheet (Fixed Assets), not Profit & Loss. */
    const ASSET_CATEGORY = 'Fixed Assets';

    /** Category every stock line gets; these lines go to Inventory (balance sheet) and become Cost of Sales when used. */
    const STOCK_CATEGORY = 'Inventory (Stock)';

    protected $fillable = ['purchase_invoice_id', 'fixed_asset_id', 'inventory_item_id', 'name', 'category', 'quantity', 'unit_price', 'line_total', 'position'];

    protected $casts = ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function invoice() { return $this->belongsTo(CrmPurchaseInvoice::class, 'purchase_invoice_id'); }

    public function fixedAsset() { return $this->belongsTo(CrmFixedAsset::class, 'fixed_asset_id'); }

    public function inventoryItem() { return $this->belongsTo(CrmInventoryItem::class, 'inventory_item_id'); }
}
