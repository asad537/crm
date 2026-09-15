@extends('crm.layout')
@php($isEditingPurchase = isset($purchase))
@section('title', $isEditingPurchase ? 'Edit Vendor Purchase' : 'Add Vendor Purchase')
@section('header_actions')
<a class="vpc-btn vpc-btn-light" href="{{ route('crm.vendor_purchases.index',['vendor_id'=>$selectedVendorId]) }}"><i class="fas fa-arrow-left"></i> Back to Purchases</a>
@endsection
@section('content')
<style>
.vpc-ocr{display:flex;align-items:center;gap:.65rem;margin:-.35rem 0 1.25rem;padding:.8rem;border:1px dashed var(--primary-purple);border-radius:12px;background:var(--primary-soft)}.vpc-ocr-copy{flex:1;min-width:0}.vpc-ocr-copy strong{display:block;color:#27364b;font-size:.8rem}.vpc-ocr-copy span{display:block;margin-top:.18rem;color:#718096;font-size:.68rem}.vpc-ocr-status{margin-top:.45rem;color:#64748b;font-size:.7rem}.vpc-ocr-status.error{color:#b91c1c}.vpc-ocr-status.success{color:#047857}@media(max-width:800px){.vpc-ocr{align-items:flex-start;flex-direction:column}}
.vpc-page{max-width:1320px;margin:0 auto}.vpc-hero{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;padding:1.2rem 1.35rem;border:1px solid #e5ebf2;border-radius:17px;background:linear-gradient(135deg,var(--primary-soft),#fff 72%);box-shadow:0 8px 28px rgba(15,23,42,.05)}.vpc-icon{display:flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:14px;background:var(--primary-purple);color:#fff;font-size:1.1rem}.vpc-hero h1{margin:0;color:#172033;font-size:1.35rem}.vpc-hero p{margin:.25rem 0 0;color:#8290a3;font-size:.78rem}.vpc-card{overflow:visible;padding:1.25rem 1.35rem;background:#fff;border:1px solid #e5ebf2;border-radius:17px;box-shadow:0 8px 28px rgba(15,23,42,.05)}.vpc-section{display:flex;align-items:center;gap:.5rem;margin:0 0 .8rem;color:#8a99ae;font-size:.7rem;font-weight:850;letter-spacing:.08em;text-transform:uppercase}.vpc-section:after{content:'';flex:1;height:1px;background:#e8edf3}.vpc-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:.85rem;margin-bottom:1.25rem}.vpc-field{grid-column:span 4;min-width:0}.vpc-3{grid-column:span 3}.vpc-6{grid-column:span 6}.vpc-12{grid-column:1/-1}.vpc-field label{display:block;margin-bottom:.38rem;color:#425168;font-size:.75rem;font-weight:780}.vpc-required{color:#ef4444}.vpc-control{width:100%;min-height:43px;padding:.62rem .78rem;border:1px solid #d8e1eb;border-radius:10px;background:#fff;color:#263449;font-size:.82rem;outline:0}.vpc-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}.vpc-control[readonly]{background:#f5f7fa;color:#475569;font-weight:750}textarea.vpc-control{min-height:86px;resize:vertical}.vpc-size{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.4rem}.vpc-size input{text-align:center}.vpc-help{display:block;margin-top:.28rem;color:#94a3b8;font-size:.67rem}.vpc-combo{position:relative}.vpc-combo .vpc-control{padding-right:42px}.vpc-combo-toggle{position:absolute;z-index:2;top:1px;right:1px;width:40px;height:41px;border:0;border-radius:0 9px 9px 0;background:transparent;color:#475569;cursor:pointer}.vpc-combo-menu{display:none;position:absolute;z-index:100;top:calc(100% + 7px);left:0;right:0;max-height:230px;overflow-y:auto;padding:6px;border:1px solid #dbe3ed;border-radius:11px;background:#fff;box-shadow:0 16px 36px rgba(15,23,42,.18)}.vpc-combo-menu.show{display:block}.vpc-combo-menu button{display:block;width:100%;padding:9px 10px;border:0;border-radius:7px;background:#fff;color:#334155;font:inherit;text-align:left;cursor:pointer}.vpc-combo-menu button:hover{background:var(--primary-soft);color:var(--primary-purple)}.vpc-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:.7rem;padding:.9rem;border:1px solid var(--primary-shadow);border-radius:12px;background:var(--primary-soft)}.vpc-summary span{display:block;color:#718096;font-size:.67rem;font-weight:800;text-transform:uppercase}.vpc-summary strong{display:block;margin-top:.24rem;color:var(--primary-purple);font-size:1rem}.vpc-actions{display:flex;justify-content:flex-end;gap:.65rem;margin:1.2rem -1.35rem -1.25rem;padding:1rem 1.35rem;border-top:1px solid #e8edf3;border-radius:0 0 17px 17px;background:#f8fafc}.vpc-btn{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;min-height:42px;padding:.62rem 1rem;border:0;border-radius:10px;text-decoration:none;font-weight:800;cursor:pointer}.vpc-btn-light{color:#475569;background:#eef2f7}.vpc-btn-primary{color:#fff;background:var(--primary-purple);box-shadow:0 8px 18px var(--primary-shadow)}.vpc-btn-outline{color:var(--primary-purple);border:1px solid var(--primary-shadow);background:var(--primary-soft)}.vpc-items{display:grid;gap:1rem;margin-bottom:1rem}.vpc-item{padding:1rem;border:1px solid #e2e8f0;border-radius:14px;background:#fbfcfe}.vpc-item-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:.9rem}.vpc-item-title{display:flex;align-items:center;gap:.55rem;color:#27364b;font-weight:850}.vpc-item-number{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9px;background:var(--primary-soft);color:var(--primary-purple)}.vpc-remove{width:34px;height:34px;border:0;border-radius:9px;background:#fff1f2;color:#e11d48;cursor:pointer}.vpc-add-wrap{display:flex;justify-content:flex-start;margin-bottom:1.3rem}.vpc-errors{margin-bottom:1rem;padding:.8rem 1rem;border:1px solid #fecaca;border-radius:10px;background:#fff5f5;color:#b91c1c;font-size:.75rem}@media(max-width:800px){.vpc-field,.vpc-3,.vpc-6{grid-column:1/-1}.vpc-summary{grid-template-columns:repeat(2,1fr)}}
.vpc-mode-note{display:none;align-items:flex-start;gap:.7rem;margin:0 0 1.1rem;padding:.8rem 1rem;border:1px solid var(--primary-shadow);border-radius:12px;background:var(--primary-soft);color:var(--primary-purple);font-size:.74rem}.vpc-mode-note i{margin-top:.12rem;color:var(--primary-purple)}.vpc-personal .vpc-mode-note{display:flex}.vpc-personal .vpc-production-only{display:none}.vpc-personal .vpc-item{border-color:var(--primary-shadow);background:linear-gradient(135deg,var(--primary-soft),#fff)}.vpc-personal .vpc-item-number{background:var(--primary-soft);color:var(--primary-purple)}.vpc-personal .vpc-icon{background:var(--primary-purple)}.vpc-personal .vpc-summary{border-color:var(--primary-shadow);background:var(--primary-soft)}.vpc-vat-help{font-size:.66rem;color:#7c8799}
.vpc-summary{grid-template-columns:repeat(5,minmax(0,1fr))}@media(max-width:1000px){.vpc-summary{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:800px){.vpc-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:480px){.vpc-summary{grid-template-columns:1fr}}
.vpc-price-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.85rem}.vpc-price-grid .vpc-field{grid-column:auto}@media(max-width:720px){.vpc-price-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:480px){.vpc-price-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
/* Compact item cards — tighter spacing/height so each product takes less vertical room */
.vpc-items{gap:.7rem}
.vpc-item{padding:.75rem .85rem}
.vpc-item .vpc-item-head{margin-bottom:.5rem}
.vpc-item .vpc-grid{gap:.5rem .6rem;margin-bottom:0}
.vpc-item .vpc-field label{margin-bottom:.22rem;font-size:.72rem}
.vpc-item .vpc-control{min-height:36px;padding:.44rem .62rem;font-size:.8rem}
.vpc-item .vpc-help{margin-top:.12rem;font-size:.63rem}
.vpc-item .vpc-price-grid{gap:.5rem .6rem}
.vpc-item .vpc-extra{gap:.5rem .6rem!important}
.vpc-item .vpc-combo-toggle{height:34px}
.vpc-item .vpc-item-head{flex-wrap:wrap;gap:.5rem .7rem;justify-content:flex-start}
.vpc-head-job{display:flex;align-items:center;gap:.5rem;margin-left:.75rem;max-width:100%}
.vpc-item-head .vpc-remove{margin-left:auto}
.vpc-head-job label{margin:0;white-space:nowrap;color:#425168;font-size:.72rem;font-weight:780}
.vpc-head-job .vpc-opt{font-weight:600;color:#94a3b8;font-size:.68rem}
.vpc-head-job input{width:230px;max-width:100%;min-height:34px}
@media(max-width:600px){.vpc-head-job{width:100%;margin-left:0}.vpc-head-job input{flex:1;width:auto}}
/* Max-2-row item layout: row 1 = identity + description, row 2 = category fields + pricing */
.vpc-item-rows{display:flex;flex-direction:column;gap:.5rem}
/* Rows pack tightly so even a 9-field row (identity + up to 7 category fields) stays on ONE line on desktop; wraps only on small screens */
.vpc-row{display:flex;flex-wrap:wrap;gap:.5rem .55rem;align-items:flex-start}
.vpc-row-1{padding:.45rem;border:1px solid #cbd5e1;border-radius:11px;background:#fff}
.vpc-row .vpc-field{grid-column:auto;min-width:0}
.vpc-row-1 .vpc-f-etype{flex:1 1 120px}
.vpc-row-1 .vpc-f-cat{flex:1.3 1 150px}
.vpc-row-1 .vpc-f-price{flex:1 1 90px}
.vpc-row-1 .vpc-f-desc{flex:2 1 150px}
.vpc-row-2 .vpc-field{flex:0 1 110px;max-width:100%}
.vpc-row-2 .vpc-field:first-child{flex-basis:160px}
.vpc-row-1 .vpc-row-2{flex:1 0 100%;width:100%}
.vpc-price-split,.vpc-price-group{display:contents}
.vpc-row-1 .vpc-price-split{display:flex;align-items:stretch;gap:.6rem;flex:2 1 430px;min-width:0}
.vpc-row-1 .vpc-price-group{display:grid;align-content:start;gap:.5rem .55rem;min-width:0;border-left:2px solid var(--primary-purple);padding-left:.65rem}
.vpc-row-1 .vpc-price-group:first-child{grid-template-columns:repeat(2,minmax(0,1fr));flex:1 1 160px}
.vpc-row-1 .vpc-price-group+.vpc-price-group{grid-template-columns:repeat(3,minmax(0,1fr));flex:1.4 1 230px}
.vpc-personal .vpc-price-split{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0;width:100%;flex:1 0 100%;border:1px solid #dbe3ed;border-radius:11px;background:#fff}
.vpc-personal .vpc-price-group{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-content:start;gap:.55rem .7rem;padding:.7rem}
.vpc-personal .vpc-price-group+.vpc-price-group{grid-template-columns:repeat(3,minmax(0,1fr));border-left:2px solid var(--primary-purple);padding-left:.85rem}
.vpc-personal .vpc-price-group:first-child{border-left:2px solid var(--primary-purple);padding-left:.7rem}
.vpc-personal .vpc-price-group .vpc-field{min-width:0}
.vpc-item .vpc-extra{display:contents}
.vpc-item .vpc-help{display:none}
@media(max-width:600px){.vpc-row .vpc-field{flex:1 1 100%}}
@media(max-width:600px){.vpc-personal .vpc-price-split{grid-template-columns:1fr}.vpc-personal .vpc-price-group+.vpc-price-group{grid-template-columns:repeat(3,minmax(0,1fr));border-top:1px solid #dbe3ed;border-left:0;padding:.7rem}.vpc-personal .vpc-price-group{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.vpc-row-1 .vpc-price-split{flex:1 0 100%}}
/* Computed Total & Gross stand out in the primary colour */
.vpc-item .vpc-control[data-role="line-total"],.vpc-item .vpc-control[data-role="gross"]{color:var(--primary-purple);background:var(--primary-soft);border-color:var(--primary-shadow);font-weight:800}
</style>
<div class="vpc-page">
<div class="vpc-hero"><span class="vpc-icon"><i id="vpcHeroIcon" class="fas {{ $isEditingPurchase ? 'fa-pen' : 'fa-truck-loading' }}"></i></span><div><h1 id="vpcHeroTitle">{{ $isEditingPurchase ? 'Edit Vendor Purchase' : 'Add Vendor Purchase' }}</h1><p id="vpcHeroCopy">{{ $isEditingPurchase ? 'Update this supplier purchase, products and payment details.' : 'Record supplier, material, invoice and payment details in one complete purchase entry.' }}</p></div></div>
@if($errors->any())<div class="vpc-errors"><strong>Please check the form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="vpc-card" id="vendorPurchaseCreateForm" method="POST" action="{{ $isEditingPurchase ? route('crm.vendor_purchases.update',$purchase->id) : route('crm.vendor_purchases.store') }}" enctype="multipart/form-data">{{ csrf_field() }}@if($isEditingPurchase){{ method_field('PUT') }}@endif
<div class="vpc-section"><i class="fas fa-building"></i> <span id="vpcPartySection">Vendor &amp; Invoice</span></div><div class="vpc-grid">
<div class="vpc-field vpc-3"><label id="vpcVendorLabel">Vendor <span class="vpc-required">*</span></label><select class="vpc-control" name="vendor_id" onchange="fillVendor(this)" required><option value="">Choose saved vendor / payee</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" data-phone="{{ $vendor->phone }}" data-email="{{ $vendor->email }}" data-category="{{ $vendor->category }}" {{ (int)old('vendor_id',$selectedVendorId)===$vendor->id?'selected':'' }}>{{ $vendor->name }}</option>@endforeach</select></div>
@php($__et = old('expense_type', $isEditingPurchase ? ($purchase->expense_type ?: 'Production Expense') : 'Production Expense'))
<input type="hidden" name="expense_type" id="vpcExpenseType" value="{{ $__et }}">
<div class="vpc-field vpc-3"><label>Purchase Date <span class="vpc-required">*</span></label><input class="vpc-control" type="date" name="purchase_date" value="{{ old('purchase_date', $isEditingPurchase ? optional($purchase->purchase_date)->format('Y-m-d') : date('Y-m-d')) }}" required></div><div class="vpc-field vpc-3"><label>Invoice Number</label><input class="vpc-control" name="invoice_number" value="{{ old('invoice_number', $isEditingPurchase ? $purchase->invoice_number : '') }}"></div><div class="vpc-field vpc-3"><label>Job ID</label><input class="vpc-control" name="job_id" value="{{ old('job_id', $isEditingPurchase ? $purchase->job_id : '') }}" placeholder="e.g. JOB-1024 / INQ-0312"></div>
<div class="vpc-field"><label>Vendor Phone</label><input class="vpc-control" name="vendor_phone" value="{{ old('vendor_phone', $isEditingPurchase ? $purchase->vendor_phone : '') }}"></div><div class="vpc-field"><label>Vendor Email</label><input class="vpc-control" type="email" name="vendor_email" value="{{ old('vendor_email', $isEditingPurchase ? $purchase->vendor_email : '') }}"></div><div class="vpc-field"><label>Payment Due Date</label><input class="vpc-control" type="date" name="due_date" value="{{ old('due_date', $isEditingPurchase && $purchase->due_date ? optional($purchase->due_date)->format('Y-m-d') : '') }}"></div></div>
<div class="vpc-field vpc-12"><label id="vpcAttachmentLabel">Invoice / Purchase Attachment</label><input class="vpc-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv"><span class="vpc-help" id="vpcAttachmentHelp">PDF, image, Word, Excel or CSV — maximum 20 MB.</span>@if($isEditingPurchase && $purchase->attachment_path)<span class="vpc-help">Current file: <a href="{{ asset(ltrim(preg_replace('#^public/#','',$purchase->attachment_path),'/')) }}" target="_blank" rel="noopener">{{ $purchase->attachment_name ?: 'View attachment' }}</a>. Upload a new file only to replace it.</span>@endif</div>
<div class="vpc-mode-note"><i class="fas fa-receipt"></i><div><strong>Personal receipt entry</strong><br>Add every receipt line separately. Choose whether VAT is already included in the printed total; the system will extract the VAT correctly without charging it twice.</div></div>
{{-- Invoice OCR extract button is temporarily disabled while hosting OCR options are reviewed. --}}
<div class="vpc-section"><i class="fas fa-boxes"></i> <span id="vpcItemsSection">Purchase Products</span></div>
@php($__vpCats = ['production' => ['Paper Board & Stock','Label & stickers','Special Paper','CTP Plates','Die Making','Foil Block Making','Embossing/Debossing Block','Digital Print','Outsource Printing','Outsource Labor','Sampling Charge','Magnets','PVC Sheets','Ribbons','Foam','Velvet','Leather','Production Misc'], 'consumable' => ['Inks, Varnish & Coatings','Chemicals, IPA, Liquids','Press Blankets & Rollers','Foil Rolls','Lamination Films','Glue','Adhesive Tapes','Machine Oil, Lubricants & Grease','Powder & Sprays','Consumable Misc'], 'admin' => ['Salaries & Wages','Staff Visa, Labour Card & Medical','Staff Accommodation & Transport','Rent (Ejari)','DEWA (Electricity & Water)','Telecom & Internet','Trade License & Government Fees','Vehicle Fuel, Salik & Repair','Generator Diesel & Repair','Meals & Late Night Meals','Kitchen / Pantry Stock','Stationery & Printing','IT Expense','Marketing & Advertising','Bank Charges & VAT Adjustments','Professional Fees','Insurance','Travel & Fare Charges','Electric Work & Office Repairs','Admin Other Expenses','Machine Repair & Maintenance','Production Wastage & Rejections','Freight & Delivery','Admin/General Misc']])
@php($purchaseItems = old('items', $purchaseItems ?? [['category'=>'Paper Board & Stock','quantity'=>1,'unit'=>'Sheets','line_total'=>'']]))
<div class="vpc-items" id="vpcItems">
@foreach($purchaseItems as $index => $item)
<div class="vpc-item" data-index="{{ $index }}"><div class="vpc-item-head"><div class="vpc-item-title"><span class="vpc-item-number">{{ $index + 1 }}</span><span>Product {{ $index + 1 }}</span></div><div class="vpc-head-job vpc-jobid"><label>Job ID <span class="vpc-opt">(optional)</span></label><input class="vpc-control" name="items[{{ $index }}][extra][job_id]" value="{{ $item['extra']['job_id'] ?? '' }}" placeholder="e.g. JOB-1024 / INQ-0312"></div><button class="vpc-remove" type="button" onclick="removePurchaseItem(this)" title="Remove product"><i class="fas fa-trash"></i></button></div><div class="vpc-item-rows">
<div class="vpc-row vpc-row-1">
<div class="vpc-field vpc-f-etype"><label>Expense Type <span class="vpc-required">*</span></label><select class="vpc-control vpc-item-etype" name="items[{{ $index }}][expense_type]" onchange="vpcItemTypeChanged(this)">@foreach(['Production Expense'=>'Production','Consumable Expense'=>'Consumable','Admin/General Expense'=>'Admin/General'] as $etv=>$etl)<option value="{{ $etv }}" {{ ($item['expense_type'] ?? 'Production Expense')===$etv?'selected':'' }}>{{ $etl }}</option>@endforeach</select></div>
<div class="vpc-field vpc-f-cat"><label>Category <span class="vpc-required">*</span></label><div class="vpc-combo"><input class="vpc-control" name="items[{{ $index }}][category]" value="{{ $item['category'] ?? 'Paper Board & Stock' }}" autocomplete="off" onfocus="openItemCategories(this,true)" oninput="openItemCategories(this,false)" required><button class="vpc-combo-toggle" type="button" onclick="toggleItemCategories(this,event)" aria-label="Show all categories"><i class="fas fa-chevron-down"></i></button><div class="vpc-combo-menu">@foreach($__vpCats as $__cm => $__catList)@foreach($__catList as $category)<button type="button" data-mode="{{ $__cm }}" data-value="{{ $category }}" onclick="chooseItemCategory(this)">{{ $category }}</button>@endforeach @endforeach</div></div></div>
<div class="vpc-field vpc-f-desc"><label class="vpc-item-name-label">Description <span class="vpc-required">*</span></label><input class="vpc-control" name="items[{{ $index }}][item_name]" value="{{ $item['item_name'] ?? '' }}" required></div>
<div class="vpc-price-split">
<div class="vpc-price-group" role="group" aria-label="Quantity and unit price">
<div class="vpc-field vpc-f-price"><label>Qty <span class="vpc-required">*</span></label><input class="vpc-control vpc-item-calc" data-role="quantity" type="number" step=".01" min=".01" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required></div><div class="vpc-field vpc-f-price"><label>P/Unit <span class="vpc-required">*</span></label><input class="vpc-control vpc-item-calc" data-role="unit-price" type="number" step=".0001" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" required></div>
</div>
<div class="vpc-price-group" role="group" aria-label="Total, VAT and gross">
<div class="vpc-field vpc-f-price"><label>Total</label><input class="vpc-control" data-role="line-total" type="number" step=".01" min="0" name="items[{{ $index }}][line_total]" value="{{ $item['line_total'] ?? '' }}" readonly></div><div class="vpc-field vpc-f-price"><label>VAT %</label><input class="vpc-control vpc-item-calc" data-role="vat" type="number" step=".01" min="0" max="100" name="items[{{ $index }}][vat_percentage]" value="{{ $item['vat_percentage'] ?? 0 }}" placeholder="e.g. 5"></div><div class="vpc-field vpc-f-price"><label>Gross</label><input class="vpc-control" data-role="gross" type="number" step=".01" min="0" readonly value="{{ isset($item['line_total']) ? number_format((float)$item['line_total'] * (1 + ((float)($item['vat_percentage'] ?? 0))/100), 2, '.', '') : '' }}"></div>
</div>
</div>
<div class="vpc-row vpc-row-2">
<div class="vpc-extra" data-index="{{ $index }}" data-extra="{{ isset($item['extra']) ? (is_array($item['extra']) ? json_encode($item['extra']) : $item['extra']) : '{}' }}" style="display:contents"></div>
<input type="hidden" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? 'Items' }}">
</div>
</div>
</div></div>
@endforeach
</div>
<div class="vpc-add-wrap"><button class="vpc-btn vpc-btn-outline" id="vpcAddItemButton" type="button" onclick="addPurchaseItem()"><i class="fas fa-plus"></i> <span>Add Another Product</span></button></div>
<div class="vpc-section"><i class="fas fa-calculator"></i> Payment Summary</div><div class="vpc-grid">
<div class="vpc-field vpc-3"><label>Currency</label><select class="vpc-control" name="currency">@foreach(['AED','USD','EUR','GBP','PKR'] as $currency)<option {{ old('currency', $isEditingPurchase ? $purchase->currency : 'AED')===$currency?'selected':'' }}>{{ $currency }}</option>@endforeach</select></div>
<div class="vpc-field vpc-3"><label id="vpcShippingLabel">Shipping Cost</label><input class="vpc-control vpc-calc" type="number" step=".01" min="0" name="shipping_cost" value="{{ old('shipping_cost', $isEditingPurchase ? $purchase->shipping_cost : 0) }}"></div><div class="vpc-field vpc-3"><label>Paid Amount</label><input class="vpc-control vpc-calc" type="number" step=".01" min="0" name="paid_amount" value="{{ old('paid_amount', $isEditingPurchase ? $purchase->paid_amount : 0) }}"></div><div class="vpc-field vpc-3"><label>Payment Status</label><select class="vpc-control vpc-calc" name="payment_status">@foreach(['Unpaid','Partial','Paid'] as $status)<option {{ old('payment_status', $isEditingPurchase ? $purchase->payment_status : 'Unpaid')===$status?'selected':'' }}>{{ $status }}</option>@endforeach</select></div><div class="vpc-field vpc-3"><label>Payment Method</label><select class="vpc-control" name="payment_method"><option value="">Select method</option>@foreach(['Cash','Bank Transfer','Card','Cheque','Credit'] as $method)<option {{ old('payment_method', $isEditingPurchase ? $purchase->payment_method : '')===$method?'selected':'' }}>{{ $method }}</option>@endforeach</select></div>
<div class="vpc-field vpc-12"><div class="vpc-summary"><div><span>Net Subtotal</span><strong id="vpcSubtotal">0.00</strong></div><div><span>VAT</span><strong id="vpcVat">0.00</strong></div><div><span>Grand Total</span><strong id="vpcTotal">0.00</strong></div><div><span>Balance</span><strong id="vpcBalance">0.00</strong></div><div><span>Status</span><strong id="vpcStatus">Unpaid</strong></div></div></div><div class="vpc-field vpc-12"><label>Notes</label><textarea class="vpc-control" name="notes">{{ old('notes', $isEditingPurchase ? $purchase->notes : '') }}</textarea></div><div class="vpc-field vpc-12"><span class="vpc-help">The invoice or receipt selected above will be saved with this entry.</span></div></div>
<div class="vpc-actions"><a class="vpc-btn vpc-btn-light" href="{{ route('crm.vendor_purchases.index',['vendor_id'=>$selectedVendorId]) }}">Cancel</a><button class="vpc-btn vpc-btn-primary" type="submit"><i class="fas fa-check-circle"></i> <span id="vpcSubmitLabel">{{ $isEditingPurchase ? 'Save Changes' : 'Save Purchase' }}</span></button></div></form></div>
@endsection
@section('scripts')<script>
var vpcIsEditing={{ $isEditingPurchase ? 'true' : 'false' }},vpcMode='production',vpcModeInitialized=false;
var vpcCats=@json($__vpCats);
var vpcCatMode=@json($__et==='Consumable Expense'?'consumable':($__et==='Admin/General Expense'?'admin':'production'));
function vpcAllHeads(){var a=[];for(var k in vpcCats)a=a.concat(vpcCats[k]);return a;}
// Switch the item Category list to match the chosen Expense Type. Known heads from another
// list are reset to the new default; custom (typed) values are left untouched.
// Production & Consumable share one combined category list (so CTP / Lamination / Paper / etc.
// and their fields are pickable in both). Admin/General keeps its own list and is excluded from
// the Production/Consumable combo.
function vpcAllowedModes(){return vpcCatMode==='admin'?['admin']:['production','consumable'];}
function vpcApplyCatMode(newMode){vpcCatMode=newMode;var am=vpcAllowedModes();document.querySelectorAll('.vpc-combo-menu button[data-mode]').forEach(function(b){b.style.display=am.indexOf(b.dataset.mode)!==-1?'block':'none';});}
function fillVendor(select){var option=select.options[select.selectedIndex],form=document.getElementById('vendorPurchaseCreateForm');form.elements.vendor_phone.value=option?option.dataset.phone||'':'';form.elements.vendor_email.value=option?option.dataset.email||'':'';}
// The per-purchase Expense Type select drives the form layout: Production shows the full
// production fields; Consumable and General Admin use the simple (personal-style) layout.
function applyExpenseTypeSelect(val){vpcApplyCatMode(val==='Consumable Expense'?'consumable':(val==='Admin/General Expense'?'admin':'production'));applyExpenseMode(val==='Production Expense'?'production':'personal');var t=document.getElementById('vpcHeroTitle');if(t){var lbl=val==='Production Expense'?'Vendor Purchase':(val==='Admin/General Expense'?'Admin/General Expense':'Consumable Expense');t.textContent=(vpcIsEditing?'Edit ':'Add ')+lbl;}var showJob=vpcCatMode!=='admin';document.querySelectorAll('.vpc-jobid').forEach(function(el){el.style.display=showJob?'':'none';});}
function applyExpenseMode(mode){
    var form=document.getElementById('vendorPurchaseCreateForm'),changed=vpcMode!==mode;vpcMode=mode;
    form.classList.toggle('vpc-personal',mode==='personal');
    document.getElementById('vpcPartySection').textContent=mode==='personal'?'Payee & Receipt':'Vendor & Invoice';
    document.getElementById('vpcVendorLabel').innerHTML=(mode==='personal'?'Payee / Merchant':'Vendor')+' <span class="vpc-required">*</span>';
    document.getElementById('vpcItemsSection').textContent=mode==='personal'?'Receipt Items':'Purchase Products';
    document.getElementById('vpcAttachmentLabel').textContent=mode==='personal'?'Receipt Attachment':'Invoice / Purchase Attachment';
    document.getElementById('vpcAttachmentHelp').textContent=mode==='personal'?'Upload the full receipt as PDF or image — maximum 20 MB.':'PDF, image, Word, Excel or CSV — maximum 20 MB.';
    document.getElementById('vpcShippingLabel').textContent=mode==='personal'?'Additional Charges':'Shipping Cost';
    document.getElementById('vpcHeroTitle').textContent=(vpcIsEditing?'Edit ':'Add ')+(mode==='personal'?'Consumable Expense':'Vendor Purchase');
    document.getElementById('vpcHeroCopy').textContent=mode==='personal'?'Record a receipt with multiple items, VAT and payment details.':'Record supplier, material, invoice and payment details in one complete purchase entry.';
    document.getElementById('vpcHeroIcon').className='fas '+(mode==='personal'?'fa-receipt':(vpcIsEditing?'fa-pen':'fa-truck-loading'));
    document.querySelector('#vpcAddItemButton span').textContent=mode==='personal'?'Add Another Receipt Item':'Add Another Product';
    document.getElementById('vpcSubmitLabel').textContent=vpcIsEditing?(mode==='personal'?'Save Expense Changes':'Save Changes'):(mode==='personal'?'Save Consumable Expense':'Save Purchase');
    document.querySelectorAll('.vpc-item-name-label').forEach(function(label){label.innerHTML='Description <span class="vpc-required">*</span>'});
    document.querySelectorAll('#vpcItems .vpc-item').forEach(function(card){
        var unit=card.querySelector('[name$="[unit]"]');
        if(changed&&!vpcModeInitialized&&vpcIsEditing){return}
        if(changed&&mode==='personal'&&unit.value==='Sheets')unit.value='Items';
    });
    vpcModeInitialized=true;renumberPurchaseItems();calculatePurchase();
}
function closeItemCategories(except){document.querySelectorAll('.vpc-combo-menu.show').forEach(function(menu){if(menu!==except)menu.classList.remove('show')})}
// Expense type is per item now (one invoice can mix Production + Consumable). Each item's
// Expense Type dropdown drives its own category list + Job ID visibility.
function vpcItemAllowedModes(card){var s=card&&card.querySelector('.vpc-item-etype');var v=s?s.value:'Production Expense';return v==='Admin/General Expense'?['admin']:(v==='Consumable Expense'?['consumable']:['production']);}
function filterItemCategories(combo,query){query=(query||'').toLowerCase();var card=combo.closest('.vpc-item'),am=vpcItemAllowedModes(card);combo.querySelectorAll('.vpc-combo-menu button').forEach(function(option){option.style.display=(am.indexOf(option.dataset.mode)!==-1&&(!query||option.dataset.value.toLowerCase().indexOf(query)!==-1))?'block':'none'})}
function vpcApplyJobId(card){var jb=card.querySelector('.vpc-jobid');if(!jb)return;var s=card.querySelector('.vpc-item-etype'),v=s?s.value:'Production Expense',inp=jb.querySelector('input'),lab=jb.querySelector('label');if(v==='Admin/General Expense'){jb.style.display='none';if(inp){inp.required=false;}return;}jb.style.display='';var req=(v==='Production Expense');if(inp)inp.required=req;if(lab)lab.innerHTML='Job ID '+(req?'<span class="vpc-required">*</span>':'<span class="vpc-opt">(optional)</span>');}
function vpcItemTypeChanged(sel){
  var card=sel.closest('.vpc-item'),am=vpcItemAllowedModes(card);
  var list=vpcCats[am[0]]||vpcCats.production;
  var catInp=card.querySelector('[name$="[category]"]');
  if(list.indexOf(catInp.value)===-1)catInp.value=list[0];
  vpcApplyJobId(card);
  vpcRenderExtra(card);vpcSyncHeaderType();
}
function vpcSyncHeaderType(){var c={};document.querySelectorAll('.vpc-item-etype').forEach(function(s){c[s.value]=(c[s.value]||0)+1;});var top='Production Expense',m=0;for(var k in c){if(c[k]>m){m=c[k];top=k;}}var h=document.getElementById('vpcExpenseType');if(h)h.value=top;}
function vpcInitItems(){document.querySelectorAll('#vpcItems .vpc-item').forEach(function(card){vpcApplyJobId(card);});vpcSyncHeaderType();}
function openItemCategories(input,showAll){var combo=input.closest('.vpc-combo'),menu=combo.querySelector('.vpc-combo-menu');closeItemCategories(menu);filterItemCategories(combo,showAll?'':input.value);menu.classList.add('show')}
function toggleItemCategories(button,event){event.stopPropagation();var combo=button.closest('.vpc-combo'),menu=combo.querySelector('.vpc-combo-menu'),willOpen=!menu.classList.contains('show');closeItemCategories(menu);if(willOpen){filterItemCategories(combo,'');menu.classList.add('show');combo.querySelector('input').focus()}}
function chooseItemCategory(button){var combo=button.closest('.vpc-combo');combo.querySelector('input').value=button.dataset.value;combo.querySelector('.vpc-combo-menu').classList.remove('show');vpcRenderExtra(combo.closest('.vpc-item'));}
// Category-specific extra fields: selecting a category renders its own fields (stored in items[N][extra][key]).
var VPC_CAT_FIELDS={
'Paper Board & Stock':[{k:'paper_type',l:'Paper Type',t:'select',o:['Bleach Card / Food Board','Art Paper Gloss','Art Paper Matte','Grey Board','Corrugation','Kraft','BUX Board','Other']},{k:'size',l:'Size (L × W)'},{k:'gsm',l:'GSM'},{k:'sheets',l:'Sheets',t:'number'}],
'Lamination Films':[{k:'lamination_type',l:'Lamination Type',t:'select',o:['Gloss','Matte','Soft Touch','Other']},{k:'lam_desc',l:'Desc'},{k:'size',l:'Size'},{k:'length',l:'Length'}],
'CTP Plates':[{k:'size',l:'Size (L × W)'},{k:'color',l:'Color'}],
'Die Making':[{k:'size',l:'Size (L × W)'}],
'Foil Block Making':[{k:'size',l:'Size (L × W)'}]
};
function vpcRenderExtra(card){
  if(!card)return;var catInp=card.querySelector('[name$="[category]"]');if(!catInp)return;
  var box=card.querySelector('.vpc-extra');if(!box)return;
  var idxM=/items\[(\d+)\]/.exec(catInp.name);var idx=idxM?idxM[1]:'0';
  var fields=VPC_CAT_FIELDS[(catInp.value||'').trim()]||[];
  var saved={};try{saved=JSON.parse(box.dataset.extra||'{}')||{};}catch(e){}
  box.innerHTML=fields.map(function(f){
    var val=(saved[f.k]!=null?saved[f.k]:'');var nm='items['+idx+'][extra]['+f.k+']';
    if(f.t==='select'){return '<div class="vpc-field vpc-3"><label>'+f.l+'</label><select class="vpc-control" name="'+nm+'"><option value=""></option>'+f.o.map(function(o){return '<option '+(o===String(val)?'selected':'')+'>'+o+'</option>';}).join('')+'</select></div>';}
    return '<div class="vpc-field vpc-3"><label>'+f.l+'</label><input class="vpc-control" type="'+(f.t==='number'?'number':'text')+'" name="'+nm+'" value="'+String(val).replace(/"/g,'&quot;')+'"></div>';
  }).join('');
}
function vpcRenderAllExtra(){document.querySelectorAll('#vpcItems .vpc-item').forEach(vpcRenderExtra);}
document.addEventListener('click',function(event){if(!event.target.closest('.vpc-combo'))closeItemCategories()});
function purchaseValue(name){var field=document.querySelector('[name="'+name+'"]');return parseFloat(field&&field.value)||0}
function renumberPurchaseItems(){
    document.querySelectorAll('#vpcItems .vpc-item').forEach(function(card,index){
        card.dataset.index=index;
        card.querySelector('.vpc-item-number').textContent=index+1;
        card.querySelector('.vpc-item-title span:last-child').textContent=(vpcMode==='personal'?'Receipt Item ':'Product ')+(index+1);
        card.querySelectorAll('[name]').forEach(function(field){field.name=field.name.replace(/items\[\d+\]/,'items['+index+']')});
        card.querySelector('.vpc-remove').style.visibility=document.querySelectorAll('#vpcItems .vpc-item').length===1?'hidden':'visible';
    });
}
function calculatePurchaseItem(card){
    var quantity=parseFloat(card.querySelector('[data-role="quantity"]').value)||0;
    var unitPrice=parseFloat(card.querySelector('[data-role="unit-price"]').value)||0;
    var vat=parseFloat(card.querySelector('[data-role="vat"]').value)||0;
    var total=quantity*unitPrice;
    card.querySelector('[data-role="line-total"]').value=total.toFixed(2);
    var g=card.querySelector('[data-role="gross"]');if(g)g.value=(total*(1+vat/100)).toFixed(2);
    calculatePurchase();
}
function bindPurchaseItem(card){
    card.querySelectorAll('.vpc-item-calc').forEach(function(field){
        field.addEventListener('input',function(){calculatePurchaseItem(card)});
        field.addEventListener('change',function(){calculatePurchaseItem(card)});
    });
    calculatePurchaseItem(card);
}
function addPurchaseItem(){
    var source=document.querySelector('#vpcItems .vpc-item'),card=source.cloneNode(true);
    card.querySelectorAll('input').forEach(function(field){
        if(field.dataset.role==='quantity')field.value='1';
        else field.value='';
    });
    card.querySelectorAll('select').forEach(function(field){field.selectedIndex=0});
    var etype=card.querySelector('.vpc-item-etype'),ev=etype?etype.value:'Production Expense',am=ev==='Admin/General Expense'?['admin']:(ev==='Consumable Expense'?['consumable']:['production']);
    var list=vpcCats[am[0]]||vpcCats.production;
    card.querySelector('[name$="[category]"]').value=list[0];
    card.querySelector('[name$="[unit]"]').value='Items';
    var ex=card.querySelector('.vpc-extra');if(ex)ex.dataset.extra='{}';
    vpcApplyJobId(card);
    document.getElementById('vpcItems').appendChild(card);
    renumberPurchaseItems();vpcRenderExtra(card);bindPurchaseItem(card);vpcSyncHeaderType();card.scrollIntoView({behavior:'smooth',block:'center'});
}
function removePurchaseItem(button){
    if(document.querySelectorAll('#vpcItems .vpc-item').length===1)return;
    button.closest('.vpc-item').remove();renumberPurchaseItems();calculatePurchase();
}
function calculatePurchase(){
    var form=document.getElementById('vendorPurchaseCreateForm'),subtotal=0;
    var vat=0;
    document.querySelectorAll('#vpcItems .vpc-item').forEach(function(card){var t=parseFloat(card.querySelector('[data-role="line-total"]').value)||0;var v=parseFloat(card.querySelector('[data-role="vat"]').value)||0;subtotal+=t;vat+=t*v/100;});
    vat=Math.round(vat*100)/100;
    var total=subtotal+vat+purchaseValue('shipping_cost'),status=form.elements.payment_status.value,paidField=form.elements.paid_amount;
    if(status==='Unpaid')paidField.value=0;
    if(status==='Paid')paidField.value=total.toFixed(2);
    paidField.readOnly=status!=='Partial';
    var paid=Math.min(parseFloat(paidField.value)||0,total);
    document.getElementById('vpcSubtotal').textContent=subtotal.toFixed(2);
    document.getElementById('vpcVat').textContent=vat.toFixed(2);
    document.getElementById('vpcTotal').textContent=total.toFixed(2);
    document.getElementById('vpcBalance').textContent=Math.max(total-paid,0).toFixed(2);
    document.getElementById('vpcStatus').textContent=status;
}
function setItemValue(card,suffix,value){var field=card.querySelector('[name$="['+suffix+']"]');if(field&&value!==undefined&&value!==null)field.value=value}
function applyExtractedInvoice(data){
    var form=document.getElementById('vendorPurchaseCreateForm');
    ['invoice_number','purchase_date','due_date','vat_percentage'].forEach(function(name){if(data[name]!==undefined&&form.elements[name])form.elements[name].value=data[name]});
    if(data.currency&&form.elements.currency){Array.prototype.some.call(form.elements.currency.options,function(option){if(option.value===data.currency){option.selected=true;return true}})}
    if(data.vendor_name&&form.elements.vendor_id){Array.prototype.some.call(form.elements.vendor_id.options,function(option){if(option.text.trim().toLowerCase()===String(data.vendor_name).trim().toLowerCase()){option.selected=true;fillVendor(form.elements.vendor_id);return true}})}
    Array.prototype.slice.call(document.querySelectorAll('#vpcItems .vpc-item'),1).forEach(function(card){card.remove()});
    var items=data.items||[];if(!items.length){calculatePurchase();return}
    for(var i=1;i<items.length;i++)addPurchaseItem();
    Array.prototype.forEach.call(document.querySelectorAll('#vpcItems .vpc-item'),function(card,index){var item=items[index]||{};['category','item_name','material','specification','size_length','size_width','size_height','gsm','color','quantity','unit','line_total'].forEach(function(key){setItemValue(card,key,item[key])});calculatePurchaseItem(card)});
    renumberPurchaseItems();calculatePurchase();
}
function extractInvoice(){
    var input=document.getElementById('invoiceDocument'),button=document.getElementById('vpcExtractButton'),status=document.getElementById('vpcOcrStatus');
    if(!input.files.length){status.className='vpc-ocr-status error';status.textContent='Select a PDF, JPG, PNG or WEBP invoice first.';return}
    var data=new FormData();data.append('_token','{{ csrf_token() }}');data.append('invoice_document',input.files[0]);button.disabled=true;status.className='vpc-ocr-status';status.textContent='Reading invoice with local OCR…';
    fetch('{{ route('crm.vendor_purchases.extract_invoice') }}',{method:'POST',body:data,credentials:'same-origin'}).then(function(response){return response.json().then(function(body){return {ok:response.ok,body:body}})}).then(function(result){if(!result.ok||!result.body.ok)throw new Error(result.body.message||'Unable to extract invoice data.');applyExtractedInvoice(result.body.data);status.className='vpc-ocr-status success';status.textContent='Invoice data extracted. Please review the form and select a saved vendor if it was not matched automatically.'}).catch(function(error){status.className='vpc-ocr-status error';status.textContent=error.message||'Unable to extract invoice data.'}).then(function(){button.disabled=false});
}
document.querySelectorAll('#vpcItems .vpc-item').forEach(bindPurchaseItem);
document.querySelectorAll('.vpc-calc').forEach(function(field){field.addEventListener('input',calculatePurchase);field.addEventListener('change',calculatePurchase)});
fillVendor(document.querySelector('[name="vendor_id"]'));applyExpenseMode('production');renumberPurchaseItems();vpcRenderAllExtra();vpcInitItems();calculatePurchase();

</script>
@include('crm.partials.unsaved_guard', ['formSelector' => '#vendorPurchaseCreateForm'])
@endsection
