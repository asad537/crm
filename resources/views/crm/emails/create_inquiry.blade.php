@extends('crm.layout')

@section('title', 'Add Inquiry')

@section('content')
@php
    $__openSizeParts = preg_split('/\s*(?:x|\*|×)\s*/i', (string) old('open_size', ''));
    $__openSizeParts = array_values(array_filter(array_map(function ($part) {
        $value = preg_replace('/[^0-9.]/', '', $part);
        return $value === '' ? null : $value;
    }, $__openSizeParts)));
    $__openSizeParts = array_pad(array_slice($__openSizeParts, 0, 2), 2, '');
    $__inquiryWebsite = isset($activeCrmWorkspace) && $activeCrmWorkspace && $activeCrmWorkspace->slug === 'mybox-packaging-app'
        ? 'Al Massa Packaging'
        : 'My Box Printing';
    $__finishingGroups = $savedFinishingGroups ?? [];
    $__selectedFinishing = array_map(function ($value) {
        return strpos($value, 'Embossing — ') === 0
            ? 'Emboss / Deboss — '.substr($value, strlen('Embossing — '))
            : $value;
    }, (array) old('finishing_options', []));
    // Popular Finishing shows a curated subset of children per parent (quick picks).
    // More Finishing below lists the complete option set for every parent.
    $__popularCurated = [
        'Lamination' => ['Gloss', 'Matte', 'Soft Touch', 'Velvet'],
        'Coating' => ['Spot UV', 'Drip-Off UV', 'Matte UV'],
        'Foiling' => ['Gold', 'Silver', 'Copper', 'Holographic'],
        'Emboss / Deboss' => ['Embossing', 'Debossing', 'Blind Embossing', 'Blind Debossing'],
        'Die Cutting' => ['Standard', 'Window', 'Perforation'],
        'Gluing' => ['Tuck End', 'One Side', 'Auto Lock Bottom'],
        'Window Patching' => ['PVC'],
        'Special Effects' => ['Velvet'],
        'Handles' => ['Metal', 'Ribbon', 'Draw String'],
        'Inserts' => ['Grey Foam', 'EVA Foam', 'Cardboard', 'Plastic Tray', 'Blister', 'Velvet Pasting'],
        'Closure' => ['Magnetic Closure'],
        'Special Printing' => ['Pantone', 'CMYK', '0/0', '4/0', '0/4', '4/4'],
    ];
    $__moreFinishingPriority = ['Box Type','Lamination','Coating','Foiling','Embossing','Emboss / Deboss','Die Cutting','Folding','Gluing','Window','Window Patching','Special Effects','Inserts','Closure','Handles','Special Printing','Specialty Printing','Assembly','E-Flute'];

    // Popular = only the curated children that actually exist in the database.
    $__popularFinishingGroups = [];
    foreach ($__popularCurated as $__group => $__curatedChildren) {
        if (array_key_exists($__group, $__finishingGroups)) {
            $__available = array_values(array_intersect($__curatedChildren, $__finishingGroups[$__group]));
            if (!empty($__available)) $__popularFinishingGroups[$__group] = $__available;
        }
    }

    // More = the full option lists; priority parents first, then any remaining groups.
    $__moreFinishingGroups = [];
    foreach ($__moreFinishingPriority as $__group) {
        if (array_key_exists($__group, $__finishingGroups)) {
            $__moreFinishingGroups[$__group] = $__finishingGroups[$__group];
        }
    }
    foreach ($__finishingGroups as $__group => $__options) {
        if (!array_key_exists($__group, $__moreFinishingGroups)) {
            $__moreFinishingGroups[$__group] = $__options;
        }
    }
@endphp
<style>
.mi-backdrop{position:fixed;inset:0;z-index:9000;display:flex;align-items:center;justify-content:center;padding:1.2rem;background:rgba(15,23,42,.55);backdrop-filter:blur(3px)}.mi-modal{width:min(1600px,98vw);max-height:93vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 28px 80px rgba(15,23,42,.3)}.mi-head{position:sticky;top:0;z-index:4;display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.1rem;background:#fff;border-bottom:1px solid #e7ecf2}.mi-title{display:flex;align-items:center;gap:.7rem}.mi-icon{width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:var(--primary-purple);color:#fff;box-shadow:0 6px 16px var(--primary-shadow)}.mi-head h2{margin:0;font-size:1.05rem;color:#1f2b3d}.mi-muted{color:#8897ab;font-size:.68rem}.mi-close{width:34px;height:34px;border:0;border-radius:9px;background:#eef2f7;color:#526176;cursor:pointer}.mi-form{padding:.85rem}.mi-table-block{overflow:hidden;margin-bottom:.75rem;border:1px solid #dfe6ee;border-radius:12px}.mi-grid{display:grid}.mi-client-grid{grid-template-columns:1.1fr 1.1fr 1fr 1.2fr 1fr}.mi-product-grid{grid-template-columns:.9fr .95fr 1.08fr 1.15fr .9fr .9fr 1.15fr .85fr}.mi-label{padding:.55rem .65rem;background:var(--primary-purple);color:#fff;border-right:1px solid rgba(255,255,255,.22);font-size:.62rem;font-weight:850;text-transform:uppercase;letter-spacing:.045em}.mi-cell{min-width:0;padding:.58rem;border-right:1px solid #e8edf3;background:#fff}.mi-cell:last-child,.mi-label:last-child{border-right:0}.mi-control{width:100%;min-height:38px;padding:.5rem .58rem;border:1.4px solid #d9e2ec;border-radius:8px;background:#fff;color:#273348;font-size:.75rem;outline:0;box-sizing:border-box}.mi-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}textarea.mi-control{min-height:38px;resize:vertical}.mi-size{display:grid;grid-template-columns:repeat(3,1fr);gap:.25rem}.mi-size.open{grid-template-columns:repeat(2,1fr)}.mi-size .mi-control{text-align:center;padding:.45rem .25rem}.mi-unit{margin-top:.3rem}.mi-quantity-list{display:flex;flex-wrap:wrap;gap:.3rem}.mi-quantity{display:flex;align-items:center;gap:.2rem;width:100%}.mi-quantity .mi-control{min-width:0}.mi-remove{width:30px;height:30px;flex:0 0 30px;border:0;border-radius:7px;background:#fff0eb;color:#ee5b2a;cursor:pointer}.mi-add{display:inline-flex;align-items:center;gap:.3rem;margin-top:.35rem;padding:.34rem .48rem;border:1px dashed var(--primary-purple);border-radius:7px;background:var(--primary-soft);color:var(--primary-purple);font-size:.62rem;font-weight:850;cursor:pointer}.mi-extra{display:grid;grid-template-columns:1fr 2fr 2fr;gap:.7rem;padding:.75rem;background:#f8fafc;border:1px solid #e4eaf1;border-radius:12px}.mi-field label{display:block;margin-bottom:.3rem;color:#536277;font-size:.65rem;font-weight:800}.mi-upload{min-height:65px;padding:.55rem;border:1.5px dashed #cbd6e2;border-radius:8px;background:#fff;font-size:.7rem}.mi-help{margin-top:.3rem;color:#8b99ad;font-size:.6rem}.mi-route{display:flex;align-items:center;gap:.45rem;margin-top:.7rem;padding:.55rem .7rem;border-radius:9px;background:var(--primary-soft);color:var(--primary-purple);font-size:.67rem}.mi-actions{position:sticky;bottom:0;z-index:4;display:flex;justify-content:flex-end;gap:.55rem;margin:.8rem -.85rem -.85rem;padding:.75rem .85rem;background:rgba(255,255,255,.97);border-top:1px solid #e6ebf2}.mi-btn{min-height:38px;padding:.52rem .8rem;border:0;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;text-decoration:none;font-size:.7rem;font-weight:850;cursor:pointer}.mi-btn-soft{background:#eef2f7;color:#526176}.mi-btn-main{background:var(--primary-purple);color:#fff;box-shadow:0 7px 18px var(--primary-shadow)}.mi-control:-webkit-autofill{-webkit-box-shadow:0 0 0 1000px #fff inset!important}@media(max-width:1100px){.mi-client-grid,.mi-product-grid{grid-template-columns:repeat(2,1fr)}.mi-label{display:none}.mi-cell{border-bottom:1px solid #e8edf3}.mi-cell:before{content:attr(data-label);display:block;margin-bottom:.3rem;color:#68778d;font-size:.6rem;font-weight:850;text-transform:uppercase}.mi-extra{grid-template-columns:1fr}}@media(max-width:650px){.mi-backdrop{padding:0;align-items:flex-end}.mi-modal{width:100%;max-height:96vh;border-radius:18px 18px 0 0}.mi-client-grid,.mi-product-grid{grid-template-columns:1fr}}
.mi-finishing-section{margin-bottom:.75rem;padding:.7rem;border:1px solid #dfe6ee;border-radius:12px;background:#f8fafc}.mi-finishing-title{margin:0 0 .55rem;color:#536277;font-size:.7rem;font-weight:850}.mi-finishing-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem}.mi-finish{position:relative;min-width:0}.mi-finish>label{display:block;margin-bottom:.28rem;color:#536277;font-size:.62rem;font-weight:800}.mi-finish-trigger{display:flex;align-items:center;justify-content:space-between;gap:.35rem;cursor:pointer;text-align:left}.mi-finish-trigger>span:first-child{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.mi-finish-count{flex:0 0 auto;padding:.16rem .34rem;border-radius:99px;background:var(--primary-soft);color:var(--primary-purple);font-size:.56rem;font-weight:850}.mi-finish-panel{display:none;position:absolute;z-index:30;left:0;right:0;top:calc(100% + 5px);padding:.55rem;background:#fff;border:1px solid #dfe6ee;border-radius:10px;box-shadow:0 16px 38px rgba(15,23,42,.16)}.mi-finish.open .mi-finish-panel{display:block}.mi-finish-search{margin-bottom:.4rem}.mi-finish-options{max-height:210px;overflow:auto}.mi-finish-option{display:flex;align-items:center;gap:.35rem;padding:.28rem .2rem;color:#445269;font-size:.65rem;cursor:pointer}.mi-finish-option:hover{background:var(--primary-soft)}.mi-finish-option input{accent-color:var(--primary-purple)}@media(max-width:1100px){.mi-finishing-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:800px){.mi-finishing-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:550px){.mi-finishing-grid{grid-template-columns:1fr}}
.mi-product-block{overflow:visible;position:relative;z-index:6}.mi-finish-all{margin-top:.35rem}.mi-finish-all .mi-finish-panel{right:auto;width:min(620px,82vw)}.mi-finish-group-title{display:block;padding:.35rem .4rem;background:var(--primary-soft);color:var(--primary-purple);font-size:.62rem;font-weight:900}.mi-finish-all .mi-finish-options{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.25rem;max-height:280px}.mi-finish-all .mi-finish-group{border:1px solid #e8edf3;border-radius:8px;overflow:hidden}.mi-finish-all .mi-finish-group .mi-finish-option{padding:.28rem .4rem}.mi-finish-selected{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.45rem}.mi-finish-selected:empty{display:none}.mi-finish-chip{max-width:100%;padding:.24rem .4rem;border-radius:6px;background:var(--primary-soft);color:var(--primary-purple);font-size:.58rem;font-weight:800;line-height:1.25;overflow-wrap:anywhere}@media(max-width:650px){.mi-finish-all .mi-finish-options{grid-template-columns:1fr}.mi-finish-all .mi-finish-panel{width:min(360px,88vw)}}
.mi-size.paper{grid-template-columns:repeat(2,1fr)}
.mi-backdrop{position:static;display:block;padding:0;background:transparent;backdrop-filter:none}
.mi-modal{width:100%;max-height:none;overflow:visible;border:1px solid #dfe6ee;border-radius:16px;box-shadow:0 10px 30px rgba(15,23,42,.06)}
.mi-head{position:static;border-radius:16px 16px 0 0}
.mi-actions{position:static}
.mi-extra{grid-template-columns:1fr 1.5fr 1.5fr 2fr}
@media(max-width:1100px){.mi-extra{grid-template-columns:1fr 1fr}}
@media(max-width:650px){.mi-extra{grid-template-columns:1fr}}
@media(max-width:650px){.mi-backdrop{padding:0}.mi-modal{max-height:none;border-radius:14px}.mi-head{border-radius:14px 14px 0 0}}
.mi-finish-tools{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.45rem;align-items:center;margin-bottom:.45rem}.mi-finish-tools .mi-finish-search{margin:0}.mi-finish-tools .mi-finish-add{width:auto;margin:0;padding:.55rem .7rem;border-style:solid;background:var(--primary-purple);color:#fff;white-space:nowrap;box-shadow:0 5px 14px var(--primary-shadow)}@media(max-width:550px){.mi-finish-tools{grid-template-columns:1fr}.mi-finish-tools .mi-finish-add{width:100%}}
.mi-combo{padding-right:2rem;background-image:linear-gradient(45deg,transparent 50%,#64748b 50%),linear-gradient(135deg,#64748b 50%,transparent 50%);background-position:calc(100% - 16px) 50%,calc(100% - 11px) 50%;background-size:5px 5px,5px 5px;background-repeat:no-repeat;cursor:text}
.mi-combo::-webkit-calendar-picker-indicator{display:none!important;opacity:0!important}
.mi-finish-add{width:100%;margin-top:.45rem;padding:.48rem;border:1px dashed var(--primary-purple);border-radius:8px;background:var(--primary-soft);color:var(--primary-purple);font-size:.64rem;font-weight:900;cursor:pointer}.mi-dialog-backdrop{display:none;position:fixed;inset:0;z-index:10050;align-items:center;justify-content:center;padding:1rem;background:rgba(15,23,42,.48);backdrop-filter:blur(5px)}.mi-dialog-backdrop.open{display:flex}.mi-dialog{width:min(470px,96vw);overflow:hidden;border:1px solid rgba(255,255,255,.7);border-radius:20px;background:#fff;box-shadow:0 28px 80px rgba(15,23,42,.28)}.mi-dialog-head{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.2rem;background:var(--primary-soft);border-bottom:1px solid var(--primary-shadow)}.mi-dialog-title{display:flex;align-items:center;gap:.7rem}.mi-dialog-title i{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:var(--primary-purple);color:#fff}.mi-dialog-title h3{margin:0;color:#202b3d;font-size:1rem}.mi-dialog-title small{display:block;margin-top:.15rem;color:#8190a5}.mi-dialog-close{border:0;background:transparent;color:#8290a4;font-size:1.05rem;cursor:pointer}.mi-dialog-body{display:grid;gap:.8rem;padding:1.2rem}.mi-dialog-field label{display:block;margin-bottom:.35rem;color:#526176;font-size:.66rem;font-weight:850}.mi-dialog-or{text-align:center;color:#9aa7b8;font-size:.6rem;font-weight:850;text-transform:uppercase}.mi-dialog-actions{display:flex;justify-content:flex-end;gap:.55rem;padding:1rem 1.2rem;border-top:1px solid #e8edf3}.mi-dialog-btn{padding:.58rem .85rem;border:0;border-radius:9px;font-size:.68rem;font-weight:900;cursor:pointer}.mi-dialog-cancel{background:#eef2f7;color:#526176}.mi-dialog-save{background:var(--primary-purple);color:#fff;box-shadow:0 8px 20px var(--primary-shadow)}
.mi-finish-section-heading{grid-column:1/-1;display:flex;align-items:center;justify-content:center;gap:.35rem;padding:.42rem .5rem;border-radius:8px;background:linear-gradient(90deg,var(--primary-soft),#fff);color:var(--primary-purple);font-size:.68rem;font-weight:900;text-align:center;text-transform:uppercase;letter-spacing:.04em}.mi-finish-more-heading{margin-top:.25rem;background:#f4f7fa;color:#68778d}
</style>

<div class="mi-backdrop">
<div class="mi-modal">
    <div class="mi-head"><div class="mi-title"><span class="mi-icon"><i class="fas fa-plus-circle"></i></span><div><h2>Add New Inquiry</h2><div class="mi-muted">Customer details, specifications, multiple quantities and files</div></div></div><button class="mi-close" type="button" onclick="window.location='{{ route('crm.inquiries.index') }}'"><i class="fas fa-times"></i></button></div>
    <form class="mi-form" method="POST" action="{{ route('crm.emails.create_manual') }}" enctype="multipart/form-data">{{ csrf_field() }}
        <div class="mi-table-block">
            <div class="mi-grid mi-client-grid">
                <div class="mi-label">Client Name</div><div class="mi-label">Client Email</div><div class="mi-label">Client Mobile / Phone</div><div class="mi-label">Currency</div><div class="mi-label">Website / Project</div>
                <div class="mi-cell" data-label="Client Name"><input class="mi-control" name="client_name" value="{{ old('client_name', optional($prefillEmail ?? null)->client_name) }}" required></div>
                <div class="mi-cell" data-label="Client Email"><input class="mi-control" type="email" name="client_email" value="{{ old('client_email', optional($prefillEmail ?? null)->client_email) }}" required></div>
                <div class="mi-cell" data-label="Client Mobile / Phone"><input class="mi-control" name="client_phone" value="{{ old('client_phone', optional($prefillEmail ?? null)->client_phone) }}"></div>
                <div class="mi-cell" data-label="Currency"><select class="mi-control" name="inquiry_currency" required>@foreach(['USD'=>'USD — US Dollar','AED'=>'AED — UAE Dirham','GBP'=>'GBP — British Pound','EUR'=>'EUR — Euro','CAD'=>'CAD — Canadian Dollar','AUD'=>'AUD — Australian Dollar','PKR'=>'PKR — Pakistani Rupee','SAR'=>'SAR — Saudi Riyal','QAR'=>'QAR — Qatari Riyal'] as $code=>$label)<option value="{{ $code }}" {{ old('inquiry_currency', isset($activeCrmWorkspace) && $activeCrmWorkspace && $activeCrmWorkspace->slug === 'mybox-packaging-app' ? 'AED' : 'USD')===$code?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
                <div class="mi-cell" data-label="Website / Project"><input class="mi-control" value="{{ $__inquiryWebsite }}" readonly><input type="hidden" name="website" value="{{ $__inquiryWebsite }}"></div>
            </div>
        </div>

        <div class="mi-table-block mi-product-block">
            <div class="mi-grid mi-product-grid">
                <div class="mi-label">Product</div><div class="mi-label">Printing</div><div class="mi-label">Dimensions</div><div class="mi-label">Finishing Options</div><div class="mi-label">Open Size</div><div class="mi-label">Stock</div><div class="mi-label">Quantity Options</div><div class="mi-label">Price Offered</div>
                <div class="mi-cell" data-label="Product"><input class="mi-control mi-combo" name="product_name" list="productOptions" autocomplete="off" value="{{ old('product_name') }}" required><datalist id="productOptions">@foreach(['Folding Carton Boxes','Rigid Boxes','Corrugated Boxes','Mailer Boxes','Product Boxes','Cosmetic Boxes','Perfume Boxes','Food Packaging Boxes','Medicine Boxes','Gift Boxes','Jewelry Boxes','Display Boxes','Sleeve Boxes','Pillow Boxes','Gable Boxes','Tuck End Boxes','Auto Lock Bottom Boxes','Window Boxes','Balloon Boxes','Paper Bags','Labels & Stickers','Brochures & Flyers','Business Cards','Booklets & Catalogs'] as $option)<option value="{{ $option }}">@endforeach</datalist></div>
                <div class="mi-cell" data-label="Printing"><input class="mi-control mi-combo" name="printing" list="printingOptions" autocomplete="off" value="{{ old('printing') }}"><datalist id="printingOptions">@foreach(['Full-Color CMYK Offset Printing','Pantone (PMS) Printing','Digital Printing','Flexographic Printing','Screen Printing','UV Printing','Inside & Outside Printing','Outside Printing Only','Single-Color Printing','Two-Color Printing','No Printing / Plain','Metallic Ink Printing','White Ink Printing','Soy-Based Ink Printing'] as $option)<option value="{{ $option }}">@endforeach</datalist></div>
                <div class="mi-cell" data-label="Dimensions">
                    <div class="mi-size" style="margin-bottom: .4rem"><input class="mi-control" id="finishL" name="length" type="number" step="0.01" min="0" inputmode="decimal" value="{{ old('length') }}" placeholder="L"><input class="mi-control" id="finishW" name="width" type="number" step="0.01" min="0" inputmode="decimal" value="{{ old('width') }}" placeholder="W"><input class="mi-control" id="finishH" name="height" type="number" step="0.01" min="0" inputmode="decimal" value="{{ old('height') }}" placeholder="H"></div>
                    <select class="mi-control mi-unit" name="unit"><option value="mm" {{ old('unit','cm') === 'mm' ? 'selected' : '' }}>mm</option><option value="cm" {{ old('unit','cm') === 'cm' ? 'selected' : '' }}>cm</option><option value="inches" {{ old('unit','cm') === 'inches' ? 'selected' : '' }}>inches</option></select>
                    <input type="hidden" name="finish_size" id="finishSize">
                </div>
                <div class="mi-cell" data-label="Finishing Options">
                    <div class="mi-finish mi-finish-all" data-title="Finishing Options">
                        <button class="mi-control mi-finish-trigger" type="button"><span class="mi-finish-label">Select Finishing Options</span><span class="mi-finish-count">0</span></button>
                        <div class="mi-finish-panel">
                            <div class="mi-finish-tools"><input class="mi-control mi-finish-search" placeholder="Search finishing options..."><button class="mi-finish-add" type="button" onclick="openFinishingDialog()"><i class="fas fa-plus"></i> Add Finishing</button></div>
                            <div class="mi-finish-options">
                                <div class="mi-finish-section-heading"><i class="fas fa-star"></i> Popular Finishing</div>
                                @foreach($__popularFinishingGroups as $group => $options)
                                    <div class="mi-finish-group">
                                        <strong class="mi-finish-group-title">{{ $group }}</strong>
                                        @foreach($options as $option)
                                            @php
                                                $finishValue = $group.' — '.$option;
                                            @endphp
                                            <label class="mi-finish-option" data-search="{{ strtolower($group.' '.$option) }}"><input type="checkbox" name="finishing_options[]" value="{{ $finishValue }}" {{ in_array($finishValue,$__selectedFinishing,true)?'checked':'' }}><span>{{ $option }}</span></label>
                                        @endforeach
                                    </div>
                                @endforeach
                                <div class="mi-finish-section-heading mi-finish-more-heading">More Finishing Options</div>
                                @foreach($__moreFinishingGroups as $group => $options)
                                    <div class="mi-finish-group">
                                        <strong class="mi-finish-group-title">{{ $group }}</strong>
                                        @foreach($options as $option)
                                            @php $finishValue = $group.' — '.$option; @endphp
                                            <label class="mi-finish-option" data-search="{{ strtolower($group.' '.$option) }}"><input type="checkbox" name="finishing_options[]" value="{{ $finishValue }}" {{ in_array($finishValue,$__selectedFinishing,true)?'checked':'' }}><span>{{ $option }}</span></label>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="mi-finish-selected" aria-live="polite"></div>
                    </div>
                </div>
                <div class="mi-cell" data-label="Open Size">
                    <div class="mi-size open">
                        <input class="mi-control" id="openL" type="number" step="0.01" min="0" inputmode="decimal" value="{{ $__openSizeParts[0] }}" placeholder="L">
                        <input class="mi-control" id="openW" type="number" step="0.01" min="0" inputmode="decimal" value="{{ $__openSizeParts[1] }}" placeholder="W">
                    </div>
                    <input type="hidden" name="open_size" id="openSize" value="{{ old('open_size') }}">
                </div>
                <div class="mi-cell" data-label="Stock"><input class="mi-control mi-combo" name="stock" list="stockOptions" autocomplete="off" value="{{ old('stock') }}"><datalist id="stockOptions">@foreach(['12pt Cardboard Stock','14pt Cardboard Stock','16pt Cardboard Stock','18pt Cardboard Stock','20pt Cardboard Stock','24pt Cardboard Stock','Kraft Paper Stock','Recycled Kraft Stock','SBS Paperboard','FBB Paperboard','CCNB Paperboard','Corrugated E-Flute','Corrugated B-Flute','Corrugated C-Flute','Double Wall Corrugated','Grey Chipboard','Rigid Board 1.5mm','Rigid Board 2mm','Rigid Board 3mm','Art Paper 128gsm','Art Paper 157gsm','Art Paper 200gsm','Art Paper 250gsm','Art Paper 300gsm','Art Paper 350gsm'] as $option)<option value="{{ $option }}">@endforeach</datalist></div>
                <div class="mi-cell" data-label="Quantity Options"><div class="mi-quantity-list" id="quantityList"><div class="mi-quantity"><input class="mi-control" type="number" name="quantities[]" min="1" placeholder="e.g. 500" required><button class="mi-remove" type="button" onclick="removeQuantity(this)"><i class="fas fa-times"></i></button></div></div><button class="mi-add" type="button" onclick="addQuantity()"><i class="fas fa-plus"></i> Add Quantity</button></div>
                <div class="mi-cell" data-label="Price Offered"><input class="mi-control" type="number" step=".01" min="0" name="price_offered" value="{{ old('price_offered') }}"></div>
            </div>
        </div>

        <div class="mi-extra">
            <div class="mi-field"><label>Inquiry Date *</label><input class="mi-control" type="date" name="inquiry_date" value="{{ old('inquiry_date',date('Y-m-d')) }}" required></div>
            <div class="mi-field"><label>Inquiry Source *</label><select class="mi-control" name="source" required>@foreach(['website'=>'Website','call'=>'Call','walk_in'=>'Walk-in','social_media'=>'Social Media','whatsapp'=>'WhatsApp','live_chat'=>'Live Chat','email'=>'Email'] as $value=>$label)<option value="{{ $value }}" {{ old('source','website')===$value?'selected':'' }}>{{ $label }}</option>@endforeach</select></div>
            <div class="mi-field"><label>Shipping Address</label><textarea class="mi-control" name="shipping_address" rows="2" placeholder="Street, city, state, postal code, country">{{ old('shipping_address') }}</textarea></div>
            <div class="mi-field"><label>Sales Notes / Requirements</label><textarea class="mi-control" name="message" rows="2">{{ old('message') }}</textarea></div>
            <div class="mi-field"><label>Customer Artwork / Reference Files</label><div class="mi-upload"><input type="file" name="attachments[]" multiple><div class="mi-help">Maximum 10 files, 50 MB each. Images, PDF, AI, PSD, CDR, Office and ZIP files.</div></div></div>
        </div>
        <div class="mi-dialog-backdrop" id="finishingDialog" role="dialog" aria-modal="true" aria-labelledby="finishingDialogTitle">
            <div class="mi-dialog">
                <div class="mi-dialog-head"><div class="mi-dialog-title"><i class="fas fa-layer-group"></i><div><h3 id="finishingDialogTitle">Add Finishing Option</h3><small>Choose a category or create a new one</small></div></div><button class="mi-dialog-close" type="button" onclick="closeFinishingDialog()"><i class="fas fa-times"></i></button></div>
                <div class="mi-dialog-body">
                    <div class="mi-dialog-field"><label>Parent Category *</label><input class="mi-control" id="finishingParent" list="finishingParentOptions" maxlength="80" autocomplete="off" placeholder="Type new or select existing parent"><datalist id="finishingParentOptions">@foreach(array_keys($__finishingGroups) as $group)<option value="{{ $group }}">@endforeach</datalist></div>
                    <div class="mi-dialog-field"><label>Child Option *</label><input class="mi-control" id="finishingChild" maxlength="100" placeholder="e.g. Pearl Texture"></div>
                </div>
                <div class="mi-dialog-actions"><button class="mi-dialog-btn mi-dialog-cancel" type="button" onclick="closeFinishingDialog()">Cancel</button><button class="mi-dialog-btn mi-dialog-save" type="button" onclick="saveFinishingOption()"><i class="fas fa-check"></i> Add Option</button></div>
            </div>
        </div>
        <div class="mi-actions">
            <a class="mi-btn mi-btn-soft" href="{{ route('crm.inquiries.index') }}">Cancel</a>
            @if(isset($activeCrmWorkspace) && $activeCrmWorkspace->slug === 'mybox-packaging-app')
                <button class="mi-btn mi-btn-soft" type="submit" name="route_to" value="designer" style="background:#1f2b3d;color:#fff"><i class="fas fa-paint-brush"></i> Save &amp; Send to Designer</button>
                <button class="mi-btn mi-btn-main" type="submit" name="route_to" value="estimator"><i class="fas fa-paper-plane"></i> Save &amp; Send to Estimator</button>
            @else
                <button class="mi-btn mi-btn-main" type="submit"><i class="fas fa-paper-plane"></i> Save Inquiry</button>
            @endif
        </div>
    </form>
</div></div>
@endsection

@section('scripts')
<script>
function addQuantity(){var list=document.getElementById('quantityList'),row=document.createElement('div');row.className='mi-quantity';row.innerHTML='<input class="mi-control" type="number" name="quantities[]" min="1" placeholder="e.g. 1000" required><button class="mi-remove" type="button" onclick="removeQuantity(this)"><i class="fas fa-times"></i></button>';list.appendChild(row)}
function removeQuantity(button){var rows=document.querySelectorAll('.mi-quantity');if(rows.length>1)button.parentNode.remove();else button.parentNode.querySelector('input').value=''}
function openFinishingDialog(){document.getElementById('finishingDialog').classList.add('open');document.getElementById('finishingParent').focus()}
function closeFinishingDialog(){document.getElementById('finishingDialog').classList.remove('open')}
async function saveFinishingOption(){var parentField=document.getElementById('finishingParent'),child=document.getElementById('finishingChild'),parent=parentField.value.trim(),childValue=child.value.trim();if(!parent){parentField.focus();return}if(!childValue){child.focus();return}var saveButton=document.querySelector('#finishingDialog .mi-dialog-save'),originalHtml=saveButton.innerHTML;saveButton.disabled=true;saveButton.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving';try{var response=await fetch(@json(route('crm.emails.finishing_options.store')),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},body:JSON.stringify({parent_name:parent,child_name:childValue})});var result=await response.json();if(!response.ok)throw new Error(result.message||'Unable to save finishing option.');parent=result.parent_name;childValue=result.child_name;var value=result.value,picker=document.querySelector('.mi-finish-all'),options=picker.querySelector('.mi-finish-options'),group=Array.from(options.querySelectorAll('.mi-finish-group')).find(function(item){return item.querySelector('.mi-finish-group-title').textContent.trim().toLowerCase()===parent.toLowerCase()});if(!group){group=document.createElement('div');group.className='mi-finish-group';group.innerHTML='<strong class="mi-finish-group-title"></strong>';group.querySelector('strong').textContent=parent;options.appendChild(group)}var parentList=document.getElementById('finishingParentOptions');if(parentList&&!Array.from(parentList.options).some(function(option){return option.value.toLowerCase()===parent.toLowerCase()})){var parentOption=document.createElement('option');parentOption.value=parent;parentList.appendChild(parentOption)}var existing=Array.from(group.querySelectorAll('input')).find(function(input){return input.value.toLowerCase()===value.toLowerCase()});if(existing){existing.checked=true}else{var option=document.createElement('label');option.className='mi-finish-option';option.dataset.search=(parent+' '+childValue).toLowerCase();var input=document.createElement('input');input.type='checkbox';input.name='finishing_options[]';input.value=value;input.checked=true;var span=document.createElement('span');span.textContent=childValue;option.appendChild(input);option.appendChild(span);group.appendChild(option)}picker.dispatchEvent(new Event('change',{bubbles:true}));parentField.value='';child.value='';closeFinishingDialog()}catch(error){alert(error.message)}finally{saveButton.disabled=false;saveButton.innerHTML=originalHtml}}
document.querySelectorAll('.mi-finish').forEach(function(picker){
    var trigger=picker.querySelector('.mi-finish-trigger'),search=picker.querySelector('.mi-finish-search'),label=picker.querySelector('.mi-finish-label'),counter=picker.querySelector('.mi-finish-count'),selectedList=picker.querySelector('.mi-finish-selected'),title=picker.dataset.title||'Finishing Options';
    function update(){var checked=picker.querySelectorAll('input[type="checkbox"]:checked'),count=checked.length;counter.textContent=count;label.textContent='Select '+title;if(selectedList){selectedList.innerHTML='';Array.from(checked).forEach(function(item){var chip=document.createElement('span');chip.className='mi-finish-chip';chip.textContent=item.value.split(' — ')[1]||item.value;selectedList.appendChild(chip)})}}
    trigger.addEventListener('click',function(){document.querySelectorAll('.mi-finish.open').forEach(function(other){if(other!==picker)other.classList.remove('open')});picker.classList.toggle('open');if(picker.classList.contains('open'))search.focus()});
    search.addEventListener('input',function(){var term=this.value.trim().toLowerCase();picker.querySelectorAll('.mi-finish-option').forEach(function(option){option.style.display=!term||option.dataset.search.indexOf(term)!==-1?'flex':'none'});picker.querySelectorAll('.mi-finish-group').forEach(function(group){group.style.display=Array.from(group.querySelectorAll('.mi-finish-option')).some(function(option){return option.style.display!=='none'})?'block':'none'})});
    picker.addEventListener('change',update);update();
});
document.addEventListener('click',function(event){document.querySelectorAll('.mi-finish.open').forEach(function(picker){if(!picker.contains(event.target))picker.classList.remove('open')})});
document.querySelector('.mi-form').addEventListener('submit',function(){
    var paper=[document.getElementById('finishL').value,document.getElementById('finishW').value,document.getElementById('finishH').value].filter(Boolean);
    var open=[document.getElementById('openL').value,document.getElementById('openW').value].filter(Boolean);
    document.getElementById('finishSize').value=paper.length>=2?paper.join(' x '):'';
    document.getElementById('openSize').value=open.length?open.join(' x '):'';
});
</script>
@endsection
