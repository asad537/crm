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
</style>
<div class="vpc-page">
<div class="vpc-hero"><span class="vpc-icon"><i id="vpcHeroIcon" class="fas {{ $isEditingPurchase ? 'fa-pen' : 'fa-truck-loading' }}"></i></span><div><h1 id="vpcHeroTitle">{{ $isEditingPurchase ? 'Edit Vendor Purchase' : 'Add Vendor Purchase' }}</h1><p id="vpcHeroCopy">{{ $isEditingPurchase ? 'Update this supplier purchase, products and payment details.' : 'Record supplier, material, invoice and payment details in one complete purchase entry.' }}</p></div></div>
@if($errors->any())<div class="vpc-errors"><strong>Please check the form:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="vpc-card" id="vendorPurchaseCreateForm" method="POST" action="{{ $isEditingPurchase ? route('crm.vendor_purchases.update',$purchase->id) : route('crm.vendor_purchases.store') }}" enctype="multipart/form-data">{{ csrf_field() }}@if($isEditingPurchase){{ method_field('PUT') }}@endif
<div class="vpc-section"><i class="fas fa-building"></i> <span id="vpcPartySection">Vendor &amp; Invoice</span></div><div class="vpc-grid">
<div class="vpc-field vpc-6"><label id="vpcVendorLabel">Vendor <span class="vpc-required">*</span></label><select class="vpc-control" name="vendor_id" onchange="fillVendor(this)" required><option value="">Choose saved vendor / payee</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" data-phone="{{ $vendor->phone }}" data-email="{{ $vendor->email }}" data-category="{{ $vendor->category }}" {{ (int)old('vendor_id',$selectedVendorId)===$vendor->id?'selected':'' }}>{{ $vendor->name }}</option>@endforeach</select></div>
<div class="vpc-field vpc-3"><label>Purchase Date <span class="vpc-required">*</span></label><input class="vpc-control" type="date" name="purchase_date" value="{{ old('purchase_date', $isEditingPurchase ? optional($purchase->purchase_date)->format('Y-m-d') : date('Y-m-d')) }}" required></div><div class="vpc-field vpc-3"><label>Invoice Number</label><input class="vpc-control" name="invoice_number" value="{{ old('invoice_number', $isEditingPurchase ? $purchase->invoice_number : '') }}"></div><div class="vpc-field vpc-3"><label>Job ID</label><input class="vpc-control" name="job_id" value="{{ old('job_id', $isEditingPurchase ? $purchase->job_id : '') }}" placeholder="e.g. JOB-1024 / INQ-0312"></div>
<div class="vpc-field"><label>Vendor Phone</label><input class="vpc-control" name="vendor_phone" value="{{ old('vendor_phone', $isEditingPurchase ? $purchase->vendor_phone : '') }}"></div><div class="vpc-field"><label>Vendor Email</label><input class="vpc-control" type="email" name="vendor_email" value="{{ old('vendor_email', $isEditingPurchase ? $purchase->vendor_email : '') }}"></div><div class="vpc-field"><label>Payment Due Date</label><input class="vpc-control" type="date" name="due_date" value="{{ old('due_date', $isEditingPurchase && $purchase->due_date ? optional($purchase->due_date)->format('Y-m-d') : '') }}"></div></div>
<div class="vpc-field vpc-12"><label id="vpcAttachmentLabel">Invoice / Purchase Attachment</label><input class="vpc-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv"><span class="vpc-help" id="vpcAttachmentHelp">PDF, image, Word, Excel or CSV — maximum 20 MB.</span>@if($isEditingPurchase && $purchase->attachment_path)<span class="vpc-help">Current file: <a href="{{ asset(ltrim(preg_replace('#^public/#','',$purchase->attachment_path),'/')) }}" target="_blank" rel="noopener">{{ $purchase->attachment_name ?: 'View attachment' }}</a>. Upload a new file only to replace it.</span>@endif</div>
<div class="vpc-mode-note"><i class="fas fa-receipt"></i><div><strong>Personal receipt entry</strong><br>Add every receipt line separately. Choose whether VAT is already included in the printed total; the system will extract the VAT correctly without charging it twice.</div></div>
{{-- Invoice OCR extract button is temporarily disabled while hosting OCR options are reviewed. --}}
<div class="vpc-section"><i class="fas fa-boxes"></i> <span id="vpcItemsSection">Purchase Products</span></div>
@php($purchaseItems = old('items', $purchaseItems ?? [['category'=>'Paper & Board','quantity'=>1,'unit'=>'Sheets','line_total'=>'']]))
<div class="vpc-items" id="vpcItems">
@foreach($purchaseItems as $index => $item)
<div class="vpc-item" data-index="{{ $index }}"><div class="vpc-item-head"><div class="vpc-item-title"><span class="vpc-item-number">{{ $index + 1 }}</span><span>Product {{ $index + 1 }}</span></div><button class="vpc-remove" type="button" onclick="removePurchaseItem(this)" title="Remove product"><i class="fas fa-trash"></i></button></div><div class="vpc-grid">
<div class="vpc-field"><label>Category <span class="vpc-required">*</span></label><div class="vpc-combo"><input class="vpc-control" name="items[{{ $index }}][category]" value="{{ $item['category'] ?? 'Paper & Board' }}" autocomplete="off" onfocus="openItemCategories(this,true)" oninput="openItemCategories(this,false)" required><button class="vpc-combo-toggle" type="button" onclick="toggleItemCategories(this,event)" aria-label="Show all categories"><i class="fas fa-chevron-down"></i></button><div class="vpc-combo-menu">@foreach(['Paper & Board','Ink & Printing','Finishing Material','Adhesive','Shipping Material','Other'] as $category)<button type="button" data-mode="production" data-value="{{ $category }}" onclick="chooseItemCategory(this)">{{ $category }}</button>@endforeach @foreach(['Meals & Dining','Fuel & Transport','Personal Shopping','Travel','Medical','Utilities','Entertainment','Accommodation','Other Personal'] as $category)<button type="button" data-mode="personal" data-value="{{ $category }}" onclick="chooseItemCategory(this)">{{ $category }}</button>@endforeach</div></div><span class="vpc-help">Select or type a custom category.</span></div>
<div class="vpc-field vpc-6"><label class="vpc-item-name-label">Item Name <span class="vpc-required">*</span></label><input class="vpc-control" name="items[{{ $index }}][item_name]" value="{{ $item['item_name'] ?? '' }}" required></div><div class="vpc-field vpc-3 vpc-production-only"><label>Material</label><input class="vpc-control" name="items[{{ $index }}][material]" value="{{ $item['material'] ?? '' }}"></div>
<div class="vpc-field vpc-production-only"><label>Specification / Grade</label><input class="vpc-control" name="items[{{ $index }}][specification]" value="{{ $item['specification'] ?? '' }}"></div><div class="vpc-field vpc-3 vpc-production-only"><label>Size (L x W x H)</label><div class="vpc-size"><input class="vpc-control" type="number" step=".01" min="0" name="items[{{ $index }}][size_length]" value="{{ $item['size_length'] ?? '' }}" placeholder="L"><input class="vpc-control" type="number" step=".01" min="0" name="items[{{ $index }}][size_width]" value="{{ $item['size_width'] ?? '' }}" placeholder="W"><input class="vpc-control" type="number" step=".01" min="0" name="items[{{ $index }}][size_height]" value="{{ $item['size_height'] ?? '' }}" placeholder="H"></div></div><div class="vpc-field vpc-3 vpc-production-only"><label>GSM / Thickness</label><input class="vpc-control" name="items[{{ $index }}][gsm]" value="{{ $item['gsm'] ?? '' }}"></div><div class="vpc-field vpc-3 vpc-production-only"><label>Color</label><input class="vpc-control" name="items[{{ $index }}][color]" value="{{ $item['color'] ?? '' }}"></div>
<div class="vpc-field vpc-3"><label>Quantity <span class="vpc-required">*</span></label><input class="vpc-control vpc-item-calc" data-role="quantity" type="number" step=".01" min=".01" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required></div><div class="vpc-field vpc-3"><label>Unit <span class="vpc-required">*</span></label><select class="vpc-control" name="items[{{ $index }}][unit]">@foreach(['Sheets','Kg','Rolls','Pieces','Boxes','Liters','Meters','Pallets','Items','Services','Meals','Trips'] as $unit)<option {{ ($item['unit'] ?? 'Sheets')===$unit?'selected':'' }}>{{ $unit }}</option>@endforeach</select></div><div class="vpc-field vpc-3"><label>Unit Price <span class="vpc-required">*</span></label><input class="vpc-control" data-role="unit-price" type="number" step=".0001" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" readonly required></div><div class="vpc-field vpc-3"><label>Total Price <span class="vpc-required">*</span></label><input class="vpc-control vpc-item-calc" data-role="line-total" type="number" step=".01" min="0" name="items[{{ $index }}][line_total]" value="{{ $item['line_total'] ?? '' }}" required></div>
</div></div>
@endforeach
</div>
<div class="vpc-add-wrap"><button class="vpc-btn vpc-btn-outline" id="vpcAddItemButton" type="button" onclick="addPurchaseItem()"><i class="fas fa-plus"></i> <span>Add Another Product</span></button></div>
<div class="vpc-section"><i class="fas fa-calculator"></i> Payment Summary</div><div class="vpc-grid">
<div class="vpc-field vpc-3"><label>Currency</label><select class="vpc-control" name="currency">@foreach(['AED','USD','EUR','GBP','PKR'] as $currency)<option {{ old('currency', $isEditingPurchase ? $purchase->currency : 'AED')===$currency?'selected':'' }}>{{ $currency }}</option>@endforeach</select></div>
<div class="vpc-field vpc-3"><label>VAT %</label><input class="vpc-control vpc-calc" type="number" step=".01" min="0" max="100" name="vat_percentage" value="{{ old('vat_percentage', $isEditingPurchase ? $purchase->vat_percentage : 0) }}"></div><div class="vpc-field vpc-3"><label id="vpcShippingLabel">Shipping Cost</label><input class="vpc-control vpc-calc" type="number" step=".01" min="0" name="shipping_cost" value="{{ old('shipping_cost', $isEditingPurchase ? $purchase->shipping_cost : 0) }}"></div><div class="vpc-field vpc-3"><label>Paid Amount</label><input class="vpc-control vpc-calc" type="number" step=".01" min="0" name="paid_amount" value="{{ old('paid_amount', $isEditingPurchase ? $purchase->paid_amount : 0) }}"></div><div class="vpc-field vpc-3"><label>Payment Status</label><select class="vpc-control vpc-calc" name="payment_status">@foreach(['Unpaid','Partial','Paid'] as $status)<option {{ old('payment_status', $isEditingPurchase ? $purchase->payment_status : 'Unpaid')===$status?'selected':'' }}>{{ $status }}</option>@endforeach</select></div><div class="vpc-field vpc-3"><label>Payment Method</label><select class="vpc-control" name="payment_method"><option value="">Select method</option>@foreach(['Cash','Bank Transfer','Card','Cheque','Credit'] as $method)<option {{ old('payment_method', $isEditingPurchase ? $purchase->payment_method : '')===$method?'selected':'' }}>{{ $method }}</option>@endforeach</select></div>
<div class="vpc-field vpc-12"><div class="vpc-summary"><div><span>Net Subtotal</span><strong id="vpcSubtotal">0.00</strong></div><div><span>VAT</span><strong id="vpcVat">0.00</strong></div><div><span>Grand Total</span><strong id="vpcTotal">0.00</strong></div><div><span>Balance</span><strong id="vpcBalance">0.00</strong></div><div><span>Status</span><strong id="vpcStatus">Unpaid</strong></div></div></div><div class="vpc-field vpc-12"><label>Notes</label><textarea class="vpc-control" name="notes">{{ old('notes', $isEditingPurchase ? $purchase->notes : '') }}</textarea></div><div class="vpc-field vpc-12"><span class="vpc-help">The invoice or receipt selected above will be saved with this entry.</span></div></div>
<div class="vpc-actions"><a class="vpc-btn vpc-btn-light" href="{{ route('crm.vendor_purchases.index',['vendor_id'=>$selectedVendorId]) }}">Cancel</a><button class="vpc-btn vpc-btn-primary" type="submit"><i class="fas fa-check-circle"></i> <span id="vpcSubmitLabel">{{ $isEditingPurchase ? 'Save Changes' : 'Save Purchase' }}</span></button></div></form></div>
@endsection
@section('scripts')<script>
var vpcIsEditing={{ $isEditingPurchase ? 'true' : 'false' }},vpcMode='production',vpcModeInitialized=false;
function fillVendor(select){var option=select.options[select.selectedIndex],form=document.getElementById('vendorPurchaseCreateForm');form.elements.vendor_phone.value=option?option.dataset.phone||'':'';form.elements.vendor_email.value=option?option.dataset.email||'':'';applyExpenseMode(option&&option.dataset.category==='Personal Expense'?'personal':'production')}
function applyExpenseMode(mode){
    var form=document.getElementById('vendorPurchaseCreateForm'),changed=vpcMode!==mode;vpcMode=mode;
    form.classList.toggle('vpc-personal',mode==='personal');
    document.getElementById('vpcPartySection').textContent=mode==='personal'?'Payee & Receipt':'Vendor & Invoice';
    document.getElementById('vpcVendorLabel').innerHTML=(mode==='personal'?'Payee / Merchant':'Vendor')+' <span class="vpc-required">*</span>';
    document.getElementById('vpcItemsSection').textContent=mode==='personal'?'Receipt Items':'Purchase Products';
    document.getElementById('vpcAttachmentLabel').textContent=mode==='personal'?'Receipt Attachment':'Invoice / Purchase Attachment';
    document.getElementById('vpcAttachmentHelp').textContent=mode==='personal'?'Upload the full receipt as PDF or image — maximum 20 MB.':'PDF, image, Word, Excel or CSV — maximum 20 MB.';
    document.getElementById('vpcShippingLabel').textContent=mode==='personal'?'Additional Charges':'Shipping Cost';
    document.getElementById('vpcHeroTitle').textContent=(vpcIsEditing?'Edit ':'Add ')+(mode==='personal'?'Personal Expense':'Vendor Purchase');
    document.getElementById('vpcHeroCopy').textContent=mode==='personal'?'Record a receipt with multiple items, VAT and payment details.':'Record supplier, material, invoice and payment details in one complete purchase entry.';
    document.getElementById('vpcHeroIcon').className='fas '+(mode==='personal'?'fa-receipt':(vpcIsEditing?'fa-pen':'fa-truck-loading'));
    document.querySelector('#vpcAddItemButton span').textContent=mode==='personal'?'Add Another Receipt Item':'Add Another Product';
    document.getElementById('vpcSubmitLabel').textContent=vpcIsEditing?(mode==='personal'?'Save Expense Changes':'Save Changes'):(mode==='personal'?'Save Personal Expense':'Save Purchase');
    document.querySelectorAll('.vpc-item-name-label').forEach(function(label){label.innerHTML=(mode==='personal'?'Receipt Item / Description':'Item Name')+' <span class="vpc-required">*</span>'});
    document.querySelectorAll('.vpc-combo-menu button[data-mode]').forEach(function(button){button.style.display=button.dataset.mode===mode?'block':'none'});
    document.querySelectorAll('#vpcItems .vpc-item').forEach(function(card){
        var category=card.querySelector('[name$="[category]"]'),unit=card.querySelector('[name$="[unit]"]');
        if(changed&&!vpcModeInitialized&&vpcIsEditing){return}
        if(changed&&mode==='personal'&&['Paper & Board','Ink & Printing','Finishing Material','Adhesive','Shipping Material','Other'].indexOf(category.value)!==-1)category.value='Meals & Dining';
        if(changed&&mode==='production'&&['Meals & Dining','Fuel & Transport','Personal Shopping','Travel','Medical','Utilities','Entertainment','Accommodation','Other Personal'].indexOf(category.value)!==-1)category.value='Paper & Board';
        if(changed&&mode==='personal'&&unit.value==='Sheets')unit.value='Items';
    });
    vpcModeInitialized=true;renumberPurchaseItems();calculatePurchase();
}
function closeItemCategories(except){document.querySelectorAll('.vpc-combo-menu.show').forEach(function(menu){if(menu!==except)menu.classList.remove('show')})}
function filterItemCategories(combo,query){query=(query||'').toLowerCase();combo.querySelectorAll('.vpc-combo-menu button').forEach(function(option){option.style.display=option.dataset.mode===vpcMode&&(!query||option.dataset.value.toLowerCase().indexOf(query)!==-1)?'block':'none'})}
function openItemCategories(input,showAll){var combo=input.closest('.vpc-combo'),menu=combo.querySelector('.vpc-combo-menu');closeItemCategories(menu);filterItemCategories(combo,showAll?'':input.value);menu.classList.add('show')}
function toggleItemCategories(button,event){event.stopPropagation();var combo=button.closest('.vpc-combo'),menu=combo.querySelector('.vpc-combo-menu'),willOpen=!menu.classList.contains('show');closeItemCategories(menu);if(willOpen){filterItemCategories(combo,'');menu.classList.add('show');combo.querySelector('input').focus()}}
function chooseItemCategory(button){var combo=button.closest('.vpc-combo');combo.querySelector('input').value=button.dataset.value;combo.querySelector('.vpc-combo-menu').classList.remove('show')}
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
    var lineTotal=parseFloat(card.querySelector('[data-role="line-total"]').value)||0;
    card.querySelector('[data-role="unit-price"]').value=quantity>0?(lineTotal/quantity).toFixed(4):'';
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
    card.querySelector('[name$="[category]"]').value=vpcMode==='personal'?'Meals & Dining':'Paper & Board';
    if(vpcMode==='personal')card.querySelector('[name$="[unit]"]').value='Items';
    document.getElementById('vpcItems').appendChild(card);
    renumberPurchaseItems();bindPurchaseItem(card);card.scrollIntoView({behavior:'smooth',block:'center'});
}
function removePurchaseItem(button){
    if(document.querySelectorAll('#vpcItems .vpc-item').length===1)return;
    button.closest('.vpc-item').remove();renumberPurchaseItems();calculatePurchase();
}
function calculatePurchase(){
    var form=document.getElementById('vendorPurchaseCreateForm'),subtotal=0;
    document.querySelectorAll('#vpcItems [data-role="line-total"]').forEach(function(field){subtotal+=parseFloat(field.value)||0});
    var percentage=purchaseValue('vat_percentage'),vat=Math.round(subtotal*percentage/100*100)/100;
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
fillVendor(document.querySelector('[name="vendor_id"]'));renumberPurchaseItems();calculatePurchase();

</script>
@include('crm.partials.unsaved_guard', ['formSelector' => '#vendorPurchaseCreateForm'])
@endsection
