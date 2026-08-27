@extends('crm.layout')

@section('title', 'Vendor Purchases')

@php
    $vendorPageUser = Auth::guard('crm')->user();
    $canDeleteVendors = $vendorPageUser && ($vendorPageUser->isSuperAdmin() || $vendorPageUser->isAdmin() || $vendorPageUser->isAccounts());
@endphp

@section('header_actions')
    <a href="{{ route('crm.vendor_purchases.jobs') }}" class="vp-primary-btn" style="text-decoration:none;background:#fff;color:var(--primary-purple);border:1px solid var(--primary-purple);margin-right:.5rem;"><i class="fas fa-briefcase"></i> Job Expenses</a>
    <button type="button" class="vp-primary-btn vp-header-add" style="margin-right:.5rem;" onclick="openVendorModal()"><i class="fas fa-truck"></i> Add Vendor</button>
    @if(isset($selectedVendor) && $selectedVendor)<a class="vp-primary-btn" style="text-decoration:none;" href="{{ route('crm.vendor_purchases.create',['vendor_id'=>$selectedVendor->id]) }}">
        <i class="fas fa-plus"></i> Add Purchase
    </a>@endif
@endsection

@section('content')
<style>
    .vp-page { color:#1e293b; }
    .vp-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1.2rem; }
    .vp-stat { background:#fff; border:1px solid #e8edf3; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 3px 12px rgba(15,23,42,.04); }
    .vp-stat-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:.65rem; }
    .vp-stat-label { color:#718096; font-size:.72rem; font-weight:750; text-transform:uppercase; letter-spacing:.055em; }
    .vp-stat-icon { width:34px; height:34px; border-radius:10px; display:flex; align-items:center; justify-content:center; }
    .vp-stat-value { font-size:1.15rem; font-weight:850; color:#172033; }
    .vp-directory-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}.vp-directory-stat{display:flex;align-items:center;gap:1rem;min-height:104px;padding:1rem 1.2rem;background:#fff;border:1px solid #e7edf4;border-radius:17px;box-shadow:0 7px 24px rgba(15,23,42,.05)}.vp-directory-stat-icon{width:52px;height:52px;flex:0 0 52px;display:flex;align-items:center;justify-content:center;border-radius:15px;font-size:1.2rem}.vp-directory-stat-copy span{display:block;color:#91a0b7;font-size:.69rem;font-weight:850;letter-spacing:.065em;text-transform:uppercase}.vp-directory-stat-copy strong{display:block;margin-top:.18rem;color:#111827;font-size:1.55rem;line-height:1.1}.vp-directory-stat-copy small{display:block;margin-top:.28rem;color:#10a875;font-size:.68rem;font-weight:800}
    .vp-vendor-hero{display:flex;justify-content:space-between;align-items:center;padding:1.25rem 1.4rem;margin-bottom:1rem;background:linear-gradient(135deg,#fff,var(--primary-soft));border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 6px 22px rgba(15,23,42,.05)}.vp-vendor-hero h2{margin:0;font-size:1.35rem}.vp-vendor-grid{display:none !important;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:1rem}.vp-vendor-card{display:block;padding:1.1rem;background:#fff;border:1px solid #e2e8f0;border-radius:16px;text-decoration:none;color:#1e293b;box-shadow:0 5px 18px rgba(15,23,42,.06);transition:.2s}.vp-vendor-card:hover{border-color:var(--primary-purple);transform:translateY(-3px);box-shadow:0 12px 28px var(--primary-shadow)}.vp-vendor-head{display:flex;align-items:center;gap:.8rem}.vp-vendor-avatar{width:46px;height:46px;display:flex;align-items:center;justify-content:center;border-radius:13px;background:var(--primary-soft);color:var(--primary-purple);font-size:1.05rem;font-weight:850}.vp-vendor-card h3{margin:0 0 .25rem;font-size:.98rem}.vp-vendor-contact{display:flex;gap:.45rem;align-items:center;margin-top:.55rem;color:#64748b;font-size:.75rem}.vp-vendor-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:.45rem;margin-top:1rem;padding-top:.85rem;border-top:1px solid #edf1f5}.vp-vendor-metric span{display:block;color:#94a3b8;font-size:.62rem;text-transform:uppercase;font-weight:750}.vp-vendor-metric strong{display:block;margin-top:.2rem;font-size:.78rem}.vp-vendor-open{display:flex;justify-content:space-between;align-items:center;margin-top:.9rem;color:var(--primary-purple);font-size:.75rem;font-weight:800}.vp-directory-tools{display:flex;gap:.55rem;align-items:center}.vp-directory-search{min-width:240px}.vp-directory-table{display:table !important;width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0}.vp-directory-table th,.vp-directory-table td{padding:.85rem 1rem;border-bottom:1px solid #edf1f5;text-align:left}.vp-directory-table th{font-size:.68rem;text-transform:uppercase;color:#718096;background:#f8fafc}.vp-directory-table td{color:#334155}.vp-directory-table tr:hover{background:var(--primary-soft)}.vp-back{display:inline-flex;gap:.4rem;align-items:center;margin-bottom:1rem;color:var(--primary-purple);text-decoration:none;font-weight:750}
    .vp-card { background:#fff; border:1px solid #e8edf3; border-radius:16px; box-shadow:0 4px 18px rgba(15,23,42,.045); overflow:hidden; }
    .vp-filter-card{position:relative;z-index:20;overflow:visible}
    .vp-toolbar { display:flex; align-items:center; gap:.65rem; padding:1rem; border-bottom:1px solid #edf1f5; background:#fbfcfe; }
    .vp-search { position:relative; flex:1; }
    .vp-search>i { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.85rem; pointer-events:none; }
    .vp-search-btn{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:0;border-radius:8px;background:var(--primary-purple);color:#fff;cursor:pointer;font-size:.75rem;transition:background .15s;}
    .vp-search-btn:hover{background:var(--primary-hover);}
    .vp-control { min-height:39px; box-sizing:border-box; border:1px solid #dce4ed !important; border-radius:9px !important; background:#fff; padding:.55rem .75rem !important; color:#334155; font-size:.8rem !important; outline:none; }
    .vp-search .vp-control { width:100%; padding-left:2.4rem !important; padding-right:3.1rem !important; }
    .vp-control:focus { border-color:var(--primary-purple) !important; box-shadow:0 0 0 3px var(--primary-shadow); }
    .vp-filter-btn,.vp-primary-btn { min-height:39px; display:inline-flex; align-items:center; justify-content:center; gap:.45rem; padding:.55rem .9rem; border-radius:9px; border:0; font-size:.8rem; font-weight:750; cursor:pointer; text-decoration:none; }
    .vp-primary-btn:hover, .vp-primary-btn:focus { text-decoration:none; }
    .vp-primary-btn { color:#fff; background:var(--primary-purple); box-shadow:0 7px 16px var(--primary-shadow); }
    .vp-header-add { color:#fff !important; background:var(--primary-purple) !important; border-color:var(--primary-purple) !important; }
    .vp-filter-btn { color:#475569; background:#eef2f7; }
    .vp-toolbar-row{display:flex;align-items:center;border-bottom:1px solid #edf1f5;background:#fbfcfe}.vp-toolbar-row .vp-toolbar{flex:1;border-bottom:0}.vp-export-bar{display:flex;gap:.45rem;align-items:center;padding:1rem 1rem 1rem 0;white-space:nowrap}.vp-export-btn{color:#fff;background:#2563eb}.vp-export-btn.vp-al-massa{background:#f45a24}.vp-export-menu{position:relative}.vp-export-options{display:none;position:absolute;right:0;top:calc(100% + 6px);z-index:30;min-width:130px;padding:6px;background:#fff;border:1px solid #e2e8f0;border-radius:9px;box-shadow:0 12px 28px rgba(15,23,42,.15)}.vp-export-options.show{display:block}.vp-export-options button{display:block;width:100%;border:0;background:#fff;padding:9px 11px;text-align:left;border-radius:6px;cursor:pointer;color:#334155}.vp-export-options button:hover{background:#f8fafc}
    .vp-table-wrap { overflow-x:auto; }
    .vp-table { width:100%; border-collapse:collapse; font-size:.78rem; }
    .vp-table th { padding:.7rem .85rem; text-align:left; color:#718096; background:#f8fafc; border-bottom:1px solid #e8edf3; font-size:.67rem; letter-spacing:.05em; text-transform:uppercase; white-space:nowrap; }
    .vp-table td { padding:.72rem .85rem; border-bottom:1px solid #eef2f6; color:#3f4d60; vertical-align:middle; }
    .vp-table tr:last-child td { border-bottom:0; }
    .vp-table tbody tr:hover { background:#fbfefd; }
    .vp-vendor { font-weight:780; color:#1f2937; }
    .vp-muted { color:#94a3b8; font-size:.69rem; margin-top:.17rem; }
    .vp-money { font-weight:780; white-space:nowrap; }
    .vp-status { display:inline-flex; align-items:center; gap:.32rem; padding:.3rem .55rem; border-radius:999px; font-size:.67rem; font-weight:800; }
    .vp-status-paid { color:#047857; background:#dff8ed; }
    .vp-status-partial { color:#a16207; background:#fff5cc; }
    .vp-status-unpaid { color:#be123c; background:#ffe4e8; }
    .vp-pay-form { display:flex; align-items:center; gap:.35rem; }
    .vp-pay-form input { width:84px; min-height:31px; padding:.35rem .45rem !important; font-size:.72rem !important; }
    .vp-pay-btn { width:31px; height:31px; border:0; border-radius:8px; color:#fff; background:var(--primary-purple); cursor:pointer; }
    .vp-edit-btn{display:inline-flex;align-items:center;justify-content:center;width:31px;height:31px;border:0;border-radius:8px;color:#fff;background:var(--primary-purple);text-decoration:none;cursor:pointer}
    .vp-action-group{display:flex;align-items:center;gap:.35rem}.vp-action-group form{margin:0}.vp-delete-btn{width:31px;height:31px;border:1px solid #fecaca;border-radius:8px;color:#dc2626;background:#fff5f5;cursor:pointer}.vp-delete-btn:hover{color:#fff;background:#dc2626}.vp-directory-delete{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;margin-left:.35rem;border:1px solid #fecaca;border-radius:8px;color:#dc2626;background:#fff5f5;cursor:pointer}
    .vp-attachment{display:inline-flex;align-items:center;gap:.35rem;padding:.38rem .55rem;border:1px solid #dbe5ef;border-radius:8px;background:#fff;color:var(--primary-purple);font-size:.69rem;font-weight:800;text-decoration:none;white-space:nowrap}.vp-attachment:hover{background:var(--primary-soft);border-color:var(--primary-shadow)}
    .vp-empty { text-align:center; padding:3rem 1rem; color:#94a3b8; }
    .vp-pagination { padding:1rem; border-top:1px solid #edf1f5; display:flex; justify-content:center; }
.vp-pagination ul,.vp-pagination .pagination { display:flex; flex-wrap:wrap; align-items:center; gap:.3rem; list-style:none; margin:0; padding:0; }
.vp-pagination li>*,.vp-pagination a,.vp-pagination span { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 .5rem; border:1px solid #e2e8f0; border-radius:8px; background:#fff; color:#475569; font-size:.82rem; font-weight:700; text-decoration:none; }
.vp-pagination a:hover { border-color:var(--primary-purple); color:var(--primary-purple); }
.vp-pagination .active>*,.vp-pagination [aria-current]>* { background:var(--primary-purple); border-color:var(--primary-purple); color:#fff; }
.vp-pagination .disabled>* { opacity:.45; }
    .vp-modal-backdrop { display:none; position:fixed; inset:0; z-index:11000; align-items:center; justify-content:center; padding:1rem; background:rgba(15,23,42,.64); backdrop-filter:blur(6px); }
    .vp-modal { width:100%; max-width:780px; max-height:94vh; overflow-y:auto; border-radius:18px; background:#fff; box-shadow:0 30px 90px rgba(15,23,42,.36); }
    .vp-modal-header { display:flex; justify-content:space-between; align-items:center; padding:1.15rem 1.35rem; border-bottom:1px solid #e8edf3; background:linear-gradient(135deg,#effcf7,#fff 70%); }
    .vp-modal-heading { display:flex; align-items:center; gap:.75rem; }
    .vp-modal-heading-icon { width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:12px; color:#fff; background:var(--primary-purple); }
    .vp-modal h3 { margin:0; font-size:1.08rem; color:#172033; }
    .vp-modal-subtitle { margin:.18rem 0 0; color:#8290a3; font-size:.73rem; }
    .vp-close { width:34px; height:34px; border:1px solid #dce4ed; border-radius:9px; color:#64748b; background:#fff; cursor:pointer; }
    .vp-form { padding:1.15rem 1.35rem 0; }
    .vp-section { display:flex; align-items:center; gap:.45rem; margin:0 0 .7rem; color:#94a3b8; font-size:.67rem; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .vp-section:after { content:''; flex:1; height:1px; background:#edf1f5; }
    .vp-grid { display:grid; grid-template-columns:repeat(12,minmax(0,1fr)); gap:.75rem; margin-bottom:1rem; }
    .vp-field { grid-column:span 4; }
    .vp-field-3 { grid-column:span 3; }
    .vp-field-6 { grid-column:span 6; }
    .vp-field-8 { grid-column:span 8; }
    .vp-field-12 { grid-column:1/-1; }
    .vp-field label { display:block; margin:0 0 .35rem; color:#425168; font-size:.73rem; font-weight:750; }
    .vp-required { color:#ef4444; }
    .vp-field .vp-control { width:100%; }
    .vp-field textarea { min-height:70px; resize:vertical; }
    .vp-size-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.4rem}.vp-size-grid .vp-control{text-align:center}
    .vp-combobox{position:relative}.vp-combobox .vp-control{padding-right:2.5rem !important}.vp-combobox-toggle{position:absolute;right:1px;top:1px;width:38px;height:37px;border:0;border-radius:0 8px 8px 0;background:transparent;color:#475569;cursor:pointer}.vp-combobox-menu{display:none;position:absolute;z-index:80;top:calc(100% + 6px);left:0;right:0;max-height:230px;overflow-y:auto;padding:.4rem;background:#fff;border:1px solid #dce4ed;border-radius:10px;box-shadow:0 14px 34px rgba(15,23,42,.18)}.vp-combobox-menu.show{display:block}.vp-combobox-option{display:block;width:100%;padding:.65rem .75rem;border:0;border-radius:7px;background:#fff;color:#334155;text-align:left;font-size:.78rem;cursor:pointer}.vp-combobox-option:hover{background:var(--primary-soft);color:var(--primary-purple)}
    .vp-total-panel { display:grid; grid-template-columns:repeat(4,1fr); gap:.65rem; padding:.8rem; border:1px solid var(--primary-shadow); border-radius:11px; background:var(--primary-soft); }
    .vp-total-label { color:#718096; font-size:.65rem; font-weight:750; text-transform:uppercase; }
    .vp-total-value { margin-top:.2rem; color:var(--primary-purple); font-size:.92rem; font-weight:850; }
    .vp-modal-actions { display:flex; justify-content:flex-end; gap:.65rem; margin:1rem -1.35rem 0; padding:.9rem 1.35rem; border-top:1px solid #e8edf3; border-radius:0 0 18px 18px; background:#f8fafc; }
    .vp-guard{width:min(520px,94vw);padding:0}.vp-guard-body{padding:1.3rem 1.4rem;color:#64748b;line-height:1.55}.vp-guard-actions{display:flex;justify-content:flex-end;gap:.55rem;padding:1rem 1.3rem;border-top:1px solid #e8edf3;background:#f8fafc}.vp-danger-btn{min-height:39px;padding:.55rem .9rem;border:1px solid #fecaca;border-radius:9px;background:#fff5f5;color:#c24141;font-weight:800;cursor:pointer}.vp-danger-btn-solid{color:#fff;background:#dc2626;border-color:#dc2626;box-shadow:0 8px 20px rgba(220,38,38,.2)}.vp-delete-heading .vp-modal-heading-icon{background:#fff0f0;color:#dc2626}.vp-delete-warning{display:flex;gap:.7rem;align-items:flex-start;margin-top:1rem;padding:.8rem;border:1px solid #fee2e2;border-radius:10px;background:#fff7f7;color:#991b1b;font-size:.74rem}.vp-delete-target{font-weight:850;color:#1e293b}
    @media(max-width:850px){ .vp-stats{grid-template-columns:repeat(2,1fr)} .vp-directory-stats{grid-template-columns:1fr}.vp-toolbar{flex-wrap:wrap}.vp-search{min-width:100%} }
    @media(max-width:650px){ .vp-field,.vp-field-3,.vp-field-6,.vp-field-8{grid-column:1/-1}.vp-total-panel{grid-template-columns:repeat(2,1fr)}.vp-modal-backdrop{padding:0;align-items:flex-end}.vp-modal{border-radius:18px 18px 0 0}.vp-stats{grid-template-columns:1fr 1fr} }
    .vp-directory-table{border-radius:14px;overflow:hidden;box-shadow:0 8px 24px rgba(15,23,42,.055);border:1px solid #e6ebf1;margin-top:.35rem}.vp-directory-table th{padding:.85rem 1rem;background:#f7f9fc;color:#718096;border-bottom:2px solid #ffd6c8;letter-spacing:.055em;font-size:.66rem}.vp-directory-table td{padding:.9rem 1rem;font-size:.82rem;vertical-align:middle;border-bottom:1px solid #edf1f5}.vp-directory-table tbody tr{transition:background .16s}.vp-directory-table tbody tr:nth-child(even){background:#fcfdff}.vp-directory-table tbody tr:hover{background:#fff8f5;transform:none}.vp-directory-table td:first-child strong{color:#1e293b;font-size:.86rem}.vp-directory-table .vp-back{display:inline-flex;margin:0;padding:.38rem .68rem;border:1px solid #ffd5c7;border-radius:8px;background:#fff;color:#f15b2a;transition:.18s}.vp-directory-table .vp-back:hover{background:#f45a24;color:#fff;border-color:#f45a24;transform:none}.vp-directory-search{border-radius:11px;box-shadow:0 3px 12px rgba(15,23,42,.04)}.vp-directory-vendor{display:flex;align-items:center;gap:.7rem}.vp-directory-avatar{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;background:#fff0eb;color:#f15b2a;font-weight:850}.vp-directory-sub{margin-top:.12rem;color:#94a3b8;font-size:.68rem}.vp-directory-contact{display:flex;align-items:center;gap:.42rem;color:#475569}.vp-directory-contact i{width:14px;color:#94a3b8}.vp-directory-number{font-weight:750;color:#334155}.vp-directory-outstanding{color:#e0562b;font-weight:800}
    .vp-expense-dashboard{margin-bottom:1.15rem}.vp-expense-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}.vp-expense-card{display:flex;align-items:center;gap:1rem;min-height:118px;padding:1.2rem 1.25rem;background:#fff;border:1px solid #e9edf3;border-radius:18px;box-shadow:0 9px 28px rgba(15,23,42,.055)}.vp-expense-icon{width:56px;height:56px;flex:0 0 56px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-size:1.2rem}.vp-expense-label{display:block;color:#68758a;font-size:.72rem;font-weight:760;text-transform:none;letter-spacing:0}.vp-expense-value{display:block;margin-top:.2rem;color:#111827;font-size:1.52rem;font-weight:850;line-height:1.1}.vp-expense-note{display:block;margin-top:.4rem;color:#8995a8;font-size:.68rem;font-weight:650}.vp-expense-payment-split{display:flex;align-items:center;gap:.65rem;margin-top:.45rem;font-size:.64rem;font-weight:800}.vp-expense-payment-split span{display:inline-flex;align-items:center;gap:.25rem;white-space:nowrap}.vp-expense-payment-split .paid{color:#159447}.vp-expense-payment-split .unpaid{color:#d58a0b}.vp-expense-grid{display:grid;grid-template-columns:minmax(330px,.72fr) minmax(0,1.55fr);gap:1rem;margin-top:1rem}.vp-expense-panel{min-height:290px;padding:1.2rem 1.3rem;background:#fff;border:1px solid #e9edf3;border-radius:18px;box-shadow:0 9px 28px rgba(15,23,42,.045)}.vp-expense-panel h3{margin:0 0 1rem;font-size:1rem;color:#111827}.vp-expense-split{display:flex;align-items:center;justify-content:center;gap:1.4rem;min-height:220px}.vp-expense-legend{display:grid;gap:1rem}.vp-expense-legend span{display:block;color:#64748b;font-size:.74rem}.vp-expense-legend strong{display:block;margin-top:.18rem;color:#1e293b;font-size:.9rem}.vp-expense-chart-wrap{position:relative;height:225px}.vp-expense-donut-wrap{position:relative;width:190px;height:190px;flex:0 0 190px}.vp-expense-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;color:#64748b;font-size:.72rem}.vp-expense-donut-center strong{color:#162033;font-size:1.08rem}.vp-expense-legend-dot{display:inline-block;width:9px;height:9px;margin-right:.45rem;border-radius:50%}.vp-expense-panel-head{display:flex;align-items:center;justify-content:space-between;gap:1rem}.vp-expense-panel-legend{display:flex;gap:1rem;color:#64748b;font-size:.7rem}.vp-expense-change{display:inline-flex;align-items:center;margin-left:.35rem;padding:.14rem .38rem;border-radius:999px;background:#e9f8ed;color:#159447;font-size:.62rem;font-weight:850}.vp-expense-change.down{background:#fff0ed;color:#ef5b42}.vp-expense-card-copy{min-width:0}.vp-expense-dashboard + .vp-vendor-hero,.vp-expense-dashboard + .vp-vendor-hero + .vp-directory-stats,.vp-vendor-grid{display:none!important}.vp-filter-card{border-radius:17px!important;box-shadow:0 7px 24px rgba(15,23,42,.05)!important}.vp-filter-card .vp-toolbar-row{border-radius:17px}.vp-directory-table{border-radius:17px}.vp-directory-table th{background:#fbfcfe}.vp-expense-type{display:inline-flex;padding:.3rem .55rem;border-radius:8px;background:#e8f7eb;color:#159447;font-size:.68rem;font-weight:850}.vp-expense-type.personal{background:#fff0e8;color:#f15b2a}.vp-directory-status{display:inline-flex;align-items:center;justify-content:center;min-width:58px;padding:.3rem .55rem;border-radius:8px;background:#e4f7e9;color:#159447;font-size:.68rem;font-weight:850}.vp-directory-status.pending{background:#fff3dc;color:#d58a0b}.vp-directory-status.empty{min-width:92px;background:#eef2f7;color:#64748b}
    .vp-expense-cards{grid-template-columns:repeat(auto-fit,minmax(260px,1fr));width:100%}
    .vp-expense-card{min-width:0;overflow:hidden}
    /* Cards are anchors (clickable to filter). Neutralise link styling. */
    a.vp-expense-card-link{position:relative;text-decoration:none;color:inherit;cursor:pointer;transition:transform .15s ease, box-shadow .15s ease, background .15s ease}
    a.vp-expense-card-link *{text-decoration:none!important}
    a.vp-expense-card-link:hover{transform:translateY(-2px);box-shadow:0 14px 32px rgba(15,23,42,.09);background:#fbfcfe}
    a.vp-expense-card-link.is-active{box-shadow:0 12px 28px rgba(15,23,42,.08)}
    /* Slim accent bar on top when active — no clunky full border */
    a.vp-expense-card-link.is-active::before{content:'';position:absolute;top:0;left:14px;right:14px;height:3px;border-radius:0 0 3px 3px;background:var(--primary-purple);}
    .vp-expense-card-copy{width:100%;min-width:0;max-width:100%}
    .vp-expense-payment-split{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:center;gap:4px;width:100%;max-width:220px;margin-top:7px;font-size:inherit;font-weight:inherit}
    .vp-expense-payment-split span{display:flex;align-items:center;justify-content:center;gap:3px;min-width:0;padding:4px 4px;border-radius:999px;font-size:8px;font-weight:800;line-height:1;white-space:nowrap}
    .vp-expense-payment-split .paid{color:#168447;background:#eaf8ef}
    .vp-expense-payment-split .unpaid{color:#b87508;background:#fff5df}
    .vp-expense-payment-split i{font-size:9px}
    @media(max-width:1500px){.vp-expense-card{gap:.7rem;padding:1rem}.vp-expense-icon{width:48px;height:48px;flex-basis:48px}.vp-expense-value{font-size:1.35rem}.vp-expense-label{font-size:.68rem}.vp-expense-payment-split{max-width:200px}}
    @media(max-width:1100px){.vp-expense-cards{grid-template-columns:repeat(2,minmax(0,1fr))}.vp-expense-grid{grid-template-columns:1fr}}
    @media(max-width:650px){.vp-expense-cards{grid-template-columns:1fr}.vp-expense-card{min-height:100px}.vp-expense-split{justify-content:center;flex-wrap:wrap}.vp-expense-panel-head{align-items:flex-start;flex-direction:column}.vp-expense-grid{display:block}.vp-expense-panel{margin-top:1rem}.vp-directory-table{display:block!important;overflow-x:auto}}
</style>
<style>.vp-row-link{cursor:pointer}.vp-row-link:hover{background:var(--primary-soft) !important}.vp-row-link .vp-action-group,.vp-row-link .vp-pay-form,.vp-row-link a,.vp-row-link button,.vp-row-link input,.vp-row-link form{cursor:auto}.vp-date-filter{display:flex;align-items:center;gap:.45rem;min-width:290px;position:relative}.vp-date-filter select{min-width:128px}.vp-date-trigger{display:inline-flex;align-items:center;justify-content:space-between;gap:.65rem;min-width:188px;min-height:40px;padding:.55rem .75rem;border:1px solid #d8e1eb;border-radius:10px;background:#fff;color:#475569;font:inherit;cursor:pointer}.vp-date-trigger:hover{border-color:var(--primary-purple)}.vp-date-popover{display:none;position:absolute;z-index:90;top:calc(100% + 7px);right:0;width:300px;padding:.8rem;border:1px solid #dbe3ed;border-radius:12px;background:#fff;box-shadow:0 18px 36px rgba(15,23,42,.18)}.vp-date-popover.show{display:block}.vp-date-popover-label{display:block;margin-bottom:.5rem;color:#64748b;font-size:.7rem;font-weight:800;text-transform:uppercase}.vp-date-popover-row{display:flex;align-items:center;gap:.4rem}.vp-date-popover input{width:100%;min-width:0}.vp-date-popover-actions{display:flex;justify-content:flex-end;gap:.45rem;margin-top:.7rem}.vp-date-popover-actions button{min-height:34px;padding:.42rem .65rem;border:0;border-radius:8px;font-weight:800;cursor:pointer}.vp-date-clear{background:#eef2f7;color:#475569}.vp-date-apply{background:var(--primary-purple);color:#fff}@media(max-width:850px){.vp-date-filter{min-width:100%;flex-wrap:wrap}.vp-date-trigger{flex:1}.vp-date-popover{left:0;right:auto;max-width:100%}}</style>

<div class="vp-page">
    @if(!$selectedVendor)
    @php
        $expensePurchases = $vendors->flatMap(function ($vendor) { return $vendor->purchases->map(function ($purchase) use ($vendor) { $purchase->expense_category = $vendor->category ?: 'Production Expense'; return $purchase; }); });
        $expenseTotal = (float) $expensePurchases->sum('total_amount');
        $productionTotal = (float) $expensePurchases->where('expense_category', '!=', 'Personal Expense')->sum('total_amount');
        $personalTotal = (float) $expensePurchases->where('expense_category', 'Personal Expense')->sum('total_amount');
        $productionPaid = (float) $expensePurchases->where('expense_category', '!=', 'Personal Expense')->sum('paid_amount');
        $productionUnpaid = (float) $expensePurchases->where('expense_category', '!=', 'Personal Expense')->sum('balance_amount');
        $personalPaid = (float) $expensePurchases->where('expense_category', 'Personal Expense')->sum('paid_amount');
        $personalUnpaid = (float) $expensePurchases->where('expense_category', 'Personal Expense')->sum('balance_amount');
        $expenseProductionPct = $expenseTotal > 0 ? round(($productionTotal / $expenseTotal) * 100, 1) : 0;
        $expensePersonalPct = $expenseTotal > 0 ? round(($personalTotal / $expenseTotal) * 100, 1) : 0;
        $expensePending = (float) $expensePurchases->sum('balance_amount');
        $expensePaid = (float) $expensePurchases->sum('paid_amount');
        $expenseDaily = $expensePurchases->filter(function ($purchase) { return (bool) $purchase->purchase_date; })->groupBy(function ($purchase) { return $purchase->purchase_date->format('Y-m-d'); })->map(function ($rows) { return ['production' => (float) $rows->where('expense_category', '!=', 'Personal Expense')->sum('total_amount'), 'personal' => (float) $rows->where('expense_category', 'Personal Expense')->sum('total_amount')]; });
        $expenseTrend = collect();
        $trendCursor = now()->copy()->startOfMonth();
        $trendEnd = now()->copy()->endOfMonth();
        while ($trendCursor->lte($trendEnd)) {
            $trendKey = $trendCursor->format('Y-m-d');
            $expenseTrend->put($trendKey, $expenseDaily->get($trendKey, ['production' => 0, 'personal' => 0]));
            $trendCursor->addDay();
        }
        $expenseMax = max(1, (float) $expenseTrend->flatMap(function ($row) { return [$row['production'], $row['personal']]; })->max());
        $thisMonthExpenses = $expensePurchases->filter(function ($purchase) { return $purchase->purchase_date && $purchase->purchase_date->isSameMonth(now()); });
        $lastMonthExpenses = $expensePurchases->filter(function ($purchase) { return $purchase->purchase_date && $purchase->purchase_date->isSameMonth(now()->copy()->subMonth()); });
        $expenseChange = function ($current, $previous) { return $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : ($current > 0 ? 100 : 0); };
        $totalChange = $expenseChange((float) $thisMonthExpenses->sum('total_amount'), (float) $lastMonthExpenses->sum('total_amount'));
        $productionChange = $expenseChange((float) $thisMonthExpenses->where('expense_category', '!=', 'Personal Expense')->sum('total_amount'), (float) $lastMonthExpenses->where('expense_category', '!=', 'Personal Expense')->sum('total_amount'));
        $personalChange = $expenseChange((float) $thisMonthExpenses->where('expense_category', 'Personal Expense')->sum('total_amount'), (float) $lastMonthExpenses->where('expense_category', 'Personal Expense')->sum('total_amount'));
        $pendingChange = $expenseChange((float) $thisMonthExpenses->sum('balance_amount'), (float) $lastMonthExpenses->sum('balance_amount'));
    @endphp
    <div class="vp-expense-dashboard">
        <div class="vp-expense-cards">
            @php $__vpBase = request()->except(['expense_type','payment_status','page']); @endphp
            <a href="{{ route('crm.vendor_purchases.index', $__vpBase) }}" class="vp-expense-card vp-expense-card-link {{ !request('expense_type') && !request('payment_status') ? 'is-active' : '' }}" title="Show all entries"><span class="vp-expense-icon" style="color:#f45a24;background:#fff0e9"><i class="fas fa-wallet"></i></span><div class="vp-expense-card-copy"><span class="vp-expense-label">Total Expenses</span><strong class="vp-expense-value">{{ number_format($expenseTotal, 2) }}</strong><small class="vp-expense-note">vs last month <span class="vp-expense-change {{ $totalChange < 0 ? 'down' : '' }}">{{ $totalChange >= 0 ? '↑' : '↓' }} {{ number_format(abs($totalChange), 1) }}%</span></small></div></a>
            <a href="{{ route('crm.vendor_purchases.index', array_merge($__vpBase, ['expense_type'=>'Production Expense'])) }}" class="vp-expense-card vp-expense-card-link {{ request('expense_type')==='Production Expense' ? 'is-active' : '' }}" title="Filter: Production Expenses"><span class="vp-expense-icon" style="color:#159447;background:#e6f7e9"><i class="fas fa-industry"></i></span><div class="vp-expense-card-copy"><span class="vp-expense-label">Production Expenses</span><strong class="vp-expense-value">{{ number_format($productionTotal, 2) }}</strong><small class="vp-expense-note">vs last month <span class="vp-expense-change {{ $productionChange < 0 ? 'down' : '' }}">{{ $productionChange >= 0 ? '↑' : '↓' }} {{ number_format(abs($productionChange), 1) }}%</span></small><div class="vp-expense-payment-split"><span class="paid"><i class="fas fa-check-circle"></i> Paid {{ number_format($productionPaid, 2) }}</span><span class="unpaid"><i class="fas fa-clock"></i> Unpaid {{ number_format($productionUnpaid, 2) }}</span></div></div></a>
            <a href="{{ route('crm.vendor_purchases.index', array_merge($__vpBase, ['expense_type'=>'Personal Expense'])) }}" class="vp-expense-card vp-expense-card-link {{ request('expense_type')==='Personal Expense' ? 'is-active' : '' }}" title="Filter: Personal Expenses"><span class="vp-expense-icon" style="color:#6457b8;background:#efedff"><i class="fas fa-user"></i></span><div class="vp-expense-card-copy"><span class="vp-expense-label">Personal Expenses</span><strong class="vp-expense-value">{{ number_format($personalTotal, 2) }}</strong><small class="vp-expense-note">vs last month <span class="vp-expense-change {{ $personalChange < 0 ? 'down' : '' }}">{{ $personalChange >= 0 ? '↑' : '↓' }} {{ number_format(abs($personalChange), 1) }}%</span></small><div class="vp-expense-payment-split"><span class="paid"><i class="fas fa-check-circle"></i> Paid {{ number_format($personalPaid, 2) }}</span><span class="unpaid"><i class="fas fa-clock"></i> Unpaid {{ number_format($personalUnpaid, 2) }}</span></div></div></a>
            <a href="{{ route('crm.vendor_purchases.index', array_merge($__vpBase, ['payment_status'=>'Unpaid'])) }}" class="vp-expense-card vp-expense-card-link {{ request('payment_status')==='Unpaid' ? 'is-active' : '' }}" title="Filter: Unpaid / pending"><span class="vp-expense-icon" style="color:#2563eb;background:#e8f1ff"><i class="fas fa-clock"></i></span><div class="vp-expense-card-copy"><span class="vp-expense-label">Pending Payments</span><strong class="vp-expense-value">{{ number_format($expensePending, 2) }}</strong><small class="vp-expense-note">vs last month <span class="vp-expense-change {{ $pendingChange < 0 ? 'down' : '' }}">{{ $pendingChange >= 0 ? '↑' : '↓' }} {{ number_format(abs($pendingChange), 1) }}%</span></small></div></a>
        </div>
        <div class="vp-expense-grid">
            <div class="vp-expense-panel"><h3>Expense Overview</h3><div class="vp-expense-split"><div class="vp-expense-donut-wrap"><canvas id="vpExpenseDonut"></canvas><div class="vp-expense-donut-center"><strong>{{ number_format($expenseTotal, 2) }}</strong><span>Total</span></div></div><div class="vp-expense-legend"><div><span><i class="vp-expense-legend-dot" style="background:#7acb8a"></i>Production Expenses</span><strong>{{ number_format($productionTotal, 2) }} ({{ number_format($expenseProductionPct, 1) }}%)</strong></div><div><span><i class="vp-expense-legend-dot" style="background:#ff965f"></i>Personal Expenses</span><strong>{{ number_format($personalTotal, 2) }} ({{ number_format($expensePersonalPct, 1) }}%)</strong></div></div></div></div>
            <div class="vp-expense-panel"><div class="vp-expense-panel-head"><h3>Expense Trend</h3><div class="vp-expense-panel-legend"><span><i class="vp-expense-legend-dot" style="background:#7acb8a"></i>Production</span><span><i class="vp-expense-legend-dot" style="background:#ff965f"></i>Personal</span></div></div><div class="vp-expense-chart-wrap"><canvas id="vpExpenseTrend"></canvas></div></div>
        </div>
    </div>
    <div class="vp-vendor-hero"><div><h2>Vendor Directory</h2><p class="vp-muted">Manage suppliers and open their complete purchase history.</p></div><strong style="color:var(--primary-purple)"><i class="fas fa-building"></i> {{ $vendors->count() }}</strong></div>
    @if(!empty($jobSummary))
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;padding:1.05rem 1.4rem;margin-bottom:1rem;background:linear-gradient(135deg,var(--primary-soft),#fff);border:1px solid var(--primary-shadow);border-radius:16px;">
        <div style="display:flex;align-items:center;gap:.85rem;">
            <span style="width:46px;height:46px;display:grid;place-items:center;border-radius:13px;background:var(--primary-purple);color:#fff;font-size:1.1rem;flex:none;"><i class="fas fa-briefcase"></i></span>
            <div>
                <div style="font-size:.64rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;font-weight:850;">Job Purchase Total</div>
                <div style="font-size:1.1rem;font-weight:850;color:#1e293b;">{{ $jobSummary['job_id'] }}</div>
                <div style="font-size:.72rem;color:#64748b;">{{ $jobSummary['count'] }} purchase(s) &middot; {{ $jobSummary['vendors'] }} vendor(s)</div>
            </div>
        </div>
        <div style="display:flex;gap:1.8rem;flex-wrap:wrap;">
            <div><div style="font-size:.6rem;text-transform:uppercase;color:#94a3b8;font-weight:850;">Total</div><div style="font-size:1.2rem;font-weight:850;color:#1e293b;">{{ number_format($jobSummary['total'],2) }} <span style="font-size:.7rem;color:#94a3b8;">{{ $jobSummary['currency'] }}</span></div></div>
            <div><div style="font-size:.6rem;text-transform:uppercase;color:#94a3b8;font-weight:850;">Paid</div><div style="font-size:1.2rem;font-weight:850;color:#159447;">{{ number_format($jobSummary['paid'],2) }}</div></div>
            <div><div style="font-size:.6rem;text-transform:uppercase;color:#94a3b8;font-weight:850;">Balance</div><div style="font-size:1.2rem;font-weight:850;color:#d58a0b;">{{ number_format($jobSummary['balance'],2) }}</div></div>
        </div>
    </div>
    @endif
    <div class="vp-directory-stats">
        <div class="vp-directory-stat"><span class="vp-directory-stat-icon" style="color:#f45a24;background:#fff0e9"><i class="fas fa-truck"></i></span><div class="vp-directory-stat-copy"><span>Total Vendors</span><strong>{{ number_format($directorySummary['vendors']) }}</strong><small>Active supplier directory</small></div></div>
        <div class="vp-directory-stat"><span class="vp-directory-stat-icon" style="color:#d97706;background:#fff3d6"><i class="fas fa-clock"></i></span><div class="vp-directory-stat-copy"><span>Pending</span><strong>{{ number_format($directorySummary['pending'], 2) }}</strong><small>Total Unpaid Balance</small></div></div>
        <div class="vp-directory-stat"><span class="vp-directory-stat-icon" style="color:#059669;background:#e1f8ef"><i class="fas fa-check-circle"></i></span><div class="vp-directory-stat-copy"><span>Paid</span><strong>{{ number_format($directorySummary['paid'], 2) }}</strong><small>Total amount paid</small></div></div>
    </div>
    <div class="vp-card vp-filter-card" style="margin-bottom:1rem"><div class="vp-toolbar-row"><form method="GET" class="vp-toolbar"><div class="vp-search"><i class="fas fa-search"></i><input class="vp-control" name="search" id="vendorLiveSearch" autocomplete="off" value="{{ request('search') }}" placeholder="Search…" oninput="vpVendorLiveSearch(this.value)"><button class="vp-search-btn" type="submit" title="Search"><i class="fas fa-arrow-right"></i></button></div><select class="vp-control" name="expense_type" onchange="this.form.submit()"><option value="">All</option>@foreach(['Production Expense','Personal Expense'] as $type)<option value="{{ $type }}" {{ request('expense_type')===$type?'selected':'' }}>{{ $type }}</option>@endforeach</select><select class="vp-control" name="payment_status" onchange="this.form.submit()"><option value="">All payments</option>@foreach(['Paid','Partial','Unpaid'] as $status)<option value="{{ $status }}" {{ request('payment_status')===$status?'selected':'' }}>{{ $status }}</option>@endforeach</select><input type="hidden" name="date_from" value="{{ request('date_from') }}"><input type="hidden" name="date_to" value="{{ request('date_to') }}"><input class="vp-control vp-date-range" type="text" value="{{ request('date_from') && request('date_to') ? request('date_from').' - '.request('date_to') : '' }}" placeholder="Date range" title="Date range"></form><form method="POST" action="{{ route('crm.vendor_purchases.export') }}" class="vp-export-bar" id="vpExportForm">{{ csrf_field() }}<input type="hidden" name="search"><input type="hidden" name="expense_type"><input type="hidden" name="payment_status"><input type="hidden" name="date_from"><input type="hidden" name="date_to"><div class="vp-export-menu"><button class="vp-filter-btn vp-export-btn {{ isset($activeCrmWorkspace) && $activeCrmWorkspace->slug === 'mybox-packaging-app' ? 'vp-al-massa' : '' }}" type="button" onclick="document.getElementById('vpExportOptions').classList.toggle('show')"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down"></i></button><div class="vp-export-options" id="vpExportOptions"><button name="format" value="excel" type="submit"><i class="fas fa-file-excel"></i> Excel</button><button name="format" value="pdf" type="submit"><i class="fas fa-file-pdf"></i> PDF</button></div></div></form></div></div>
    <div class="vp-vendor-grid">@forelse($vendors as $vendor)<a class="vp-vendor-card" href="{{ route('crm.vendor_purchases.index',['vendor_id'=>$vendor->id]) }}"><div class="vp-vendor-head"><div class="vp-vendor-avatar">{{ strtoupper(substr($vendor->name,0,1)) }}</div><div><h3>{{ $vendor->name }}</h3><div class="vp-muted">Vendor #{{ str_pad($vendor->id,4,'0',STR_PAD_LEFT) }}</div></div></div>@if($vendor->phone)<div class="vp-vendor-contact"><i class="fas fa-phone"></i>{{ $vendor->phone }}</div>@endif @if($vendor->email)<div class="vp-vendor-contact"><i class="fas fa-envelope"></i>{{ $vendor->email }}</div>@endif<div class="vp-vendor-metrics"><div class="vp-vendor-metric"><span>Purchases</span><strong>{{ $vendor->purchases_count }}</strong></div><div class="vp-vendor-metric"><span>Total</span><strong>{{ number_format($vendor->purchases->sum('total_amount'),2) }}</strong></div><div class="vp-vendor-metric"><span>Outstanding</span><strong>{{ number_format($vendor->purchases->sum('balance_amount'),2) }}</strong></div></div><div class="vp-vendor-open"><span>View Purchases</span><i class="fas fa-arrow-right"></i></div></a>@empty<div class="vp-card vp-empty" style="grid-column:1/-1"><i class="fas fa-truck" style="font-size:2rem;margin-bottom:.7rem"></i><div>No vendors yet. Click “Add Vendor” to create the first vendor.</div></div>@endforelse</div>
    <table class="vp-directory-table" id="vendorDirectoryTable"><thead><tr><th>Vendor / Payee</th><th>Expense Type</th><th>Contact</th><th>Purchases</th><th>Total Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($vendors as $vendor)@php($vendorBalance = (float) $vendor->purchases->sum('balance_amount'))
@php($vendorHasUnpaidPurchase = $vendor->purchases->contains(function ($purchase) { return in_array($purchase->payment_status, ['Unpaid', 'Partial'], true); }))
@php($vendorHasPurchases = $vendor->purchases->isNotEmpty())
@php($vendorIsUnpaid = $vendorBalance > 0 || $vendorHasUnpaidPurchase)
@php($vendorStatus = !$vendorHasPurchases ? 'No Purchases' : ($vendorIsUnpaid ? 'Unpaid' : 'Paid'))
@php($vendorStatusClass = !$vendorHasPurchases ? 'empty' : ($vendorIsUnpaid ? 'pending' : ''))
<tr class="vp-row-link" data-href="{{ route('crm.vendor_purchases.index',['vendor_id'=>$vendor->id]) }}" data-vendor-search="{{ strtolower($vendor->name.' '.$vendor->trn_number.' '.$vendor->phone.' '.$vendor->email) }}"><td>
<div class="vp-directory-vendor"><span class="vp-directory-avatar">{{ strtoupper(substr($vendor->name,0,1)) }}</span><div><strong>{{ $vendor->name }}</strong><div class="vp-directory-sub">Vendor #{{ str_pad($vendor->id,4,'0',STR_PAD_LEFT) }}</div></div></div></td><td>
<span class="vp-expense-type {{ $vendor->category === 'Personal Expense' ? 'personal' : '' }}">{{ $vendor->category ?: 'Production Expense' }}</span></td><td>
<span class="vp-directory-contact"><i class="fas fa-phone"></i>{{ $vendor->phone ?: '-' }}</span><span class="vp-directory-contact" style="margin-top:.28rem"><i class="fas fa-envelope"></i>{{ $vendor->email ?: '-' }}</span></td><td>
<span class="vp-directory-number">{{ $vendor->purchases_count }}</span></td><td>
<span class="vp-directory-number">{{ number_format($vendor->purchases->sum('total_amount'),2) }}</span>@if($vendorBalance > 0)<div class="vp-directory-sub">Balance {{ number_format($vendorBalance,2) }}</div>@endif</td><td>
<span class="vp-directory-status {{ $vendorStatusClass }}">{{ $vendorStatus }}</span></td><td>
<div class="vp-action-group"><a class="vp-back" href="{{ route('crm.vendor_purchases.index',['vendor_id'=>$vendor->id]) }}" title="View vendor purchases"><i class="fas fa-eye"></i></a>@if($canDeleteVendors)<form method="POST" action="{{ route('crm.vendors.destroy',$vendor->id) }}" data-delete-target="{{ $vendor->name }}" onsubmit="return openVendorDeleteDialog(this,'vendor',this.dataset.deleteTarget);">{{ csrf_field() }}{{ method_field('DELETE') }}<button class="vp-directory-delete" type="submit" title="Delete vendor"><i class="fas fa-trash"></i></button></form>@endif</div></td></tr>@empty<tr><td colspan="7" class="vp-empty">No vendors found. Click “Add Vendor” to create one.</td></tr>@endforelse</tbody></table>
    @if($vendors->hasPages())<div class="vp-pagination">{{ $vendors->links() }}</div>@endif
    @else
    <a class="vp-back" href="{{ route('crm.vendor_purchases.index') }}"><i class="fas fa-arrow-left"></i> All Vendors</a><h2 style="margin:0 0 1rem">{{ $selectedVendor->name }} Purchases</h2>
    <div class="vp-stats">
        <div class="vp-stat"><div class="vp-stat-top"><span class="vp-stat-label">Total Purchases</span><span class="vp-stat-icon" style="color:#2563eb;background:#e8f1ff"><i class="fas fa-shopping-cart"></i></span></div><div class="vp-stat-value">{{ number_format($summary['total'], 2) }}</div></div>
        <div class="vp-stat"><div class="vp-stat-top"><span class="vp-stat-label">Amount Paid</span><span class="vp-stat-icon" style="color:#059669;background:#e1f8ef"><i class="fas fa-check-circle"></i></span></div><div class="vp-stat-value">{{ number_format($summary['paid'], 2) }}</div></div>
        <div class="vp-stat"><div class="vp-stat-top"><span class="vp-stat-label">Outstanding</span><span class="vp-stat-icon" style="color:#e11d48;background:#ffe8ed"><i class="fas fa-wallet"></i></span></div><div class="vp-stat-value">{{ number_format($summary['balance'], 2) }}</div></div>
        <div class="vp-stat"><div class="vp-stat-top"><span class="vp-stat-label">Open Payments</span><span class="vp-stat-icon" style="color:#d97706;background:#fff3d6"><i class="fas fa-clock"></i></span></div><div class="vp-stat-value">{{ number_format($summary['unpaid_count']) }}</div></div>
    </div>

    <div class="vp-card">
        <div class="vp-toolbar-row"><form method="GET" class="vp-toolbar"><input type="hidden" name="vendor_id" value="{{ $selectedVendor->id }}">
            <div class="vp-search"><i class="fas fa-search"></i><input class="vp-control" name="search" id="vpPurchaseLiveSearch" autocomplete="off" value="{{ request('search') }}" placeholder="Search item, material, invoice, job…" oninput="vpPurchaseLiveSearch(this.value)"><button class="vp-search-btn" type="submit" title="Search"><i class="fas fa-arrow-right"></i></button></div>
            <select class="vp-control" name="category"><option value="">All categories</option>@foreach(['Paper & Board','Ink & Printing','Finishing Material','Adhesive','Shipping Material','Other'] as $category)<option value="{{ $category }}" {{ request('category')===$category?'selected':'' }}>{{ $category }}</option>@endforeach</select>
            <select class="vp-control" name="payment_status"><option value="">All payments</option>@foreach(['Paid','Partial','Unpaid'] as $status)<option value="{{ $status }}" {{ request('payment_status')===$status?'selected':'' }}>{{ $status }}</option>@endforeach</select>
            <input type="hidden" name="date_from" value="{{ request('date_from') }}"><input type="hidden" name="date_to" value="{{ request('date_to') }}"><input class="vp-control vp-date-range" type="text" value="{{ request('date_from') && request('date_to') ? request('date_from').' - '.request('date_to') : '' }}" placeholder="Date range" title="Date range">
        </form>
        <form method="POST" action="{{ route('crm.vendor_purchases.export') }}" class="vp-export-bar" id="vpExportForm">{{ csrf_field() }}<input type="hidden" name="vendor_id" value="{{ $selectedVendor->id }}"><input type="hidden" name="search"><input type="hidden" name="category"><input type="hidden" name="payment_status"><input type="hidden" name="date_from"><input type="hidden" name="date_to"><div class="vp-export-menu"><button class="vp-filter-btn vp-export-btn {{ isset($activeCrmWorkspace) && $activeCrmWorkspace->slug === 'mybox-packaging-app' ? 'vp-al-massa' : '' }}" type="button" onclick="document.getElementById('vpExportOptions').classList.toggle('show')"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down"></i></button><div class="vp-export-options" id="vpExportOptions"><button name="format" value="excel" type="submit"><i class="fas fa-file-excel"></i> Excel</button><button name="format" value="pdf" type="submit"><i class="fas fa-file-pdf"></i> PDF</button></div></div><span id="vpSelectedCount" class="vp-muted">0 selected</span></form></div>
        <div class="vp-table-wrap"><table class="vp-table"><thead><tr><th>Date</th><th>Invoice</th><th>Vendor</th><th>Packaging Item</th><th>Qty</th><th>Total</th><th>Paid / Balance</th><th>Status</th><th>Attachment</th><th>Actions</th><th>Update Payment</th></tr></thead><tbody>
            @forelse($purchases as $purchase)
            <tr class="vp-row-link" data-href="{{ route('crm.vendor_purchases.edit',$purchase->id) }}" data-purchase-search="{{ strtolower(trim($purchase->purchase_date->format('d M Y').' '.$purchase->invoice_number.' '.$purchase->job_id.' '.$purchase->vendor_name.' '.($purchase->items->pluck('item_name')->filter()->implode(' ') ?: $purchase->item_name).' '.$purchase->category.' '.$purchase->material.' '.$purchase->payment_status)) }}">
                <td>{{ $purchase->purchase_date->format('d M Y') }}</td><td><div class="vp-muted">{{ $purchase->invoice_number ?: 'No invoice #' }}</div>@if($purchase->job_id)<div class="vp-muted" style="margin-top:.15rem;color:var(--primary-purple);font-weight:700"><i class="fas fa-briefcase" style="font-size:.62rem"></i> {{ $purchase->job_id }}</div>@endif</td>
                <td><div class="vp-vendor">{{ $purchase->vendor_name }}</div><div class="vp-muted">{{ $purchase->vendor_phone ?: $purchase->vendor_email }}</div></td>
                <td><div class="vp-vendor">{{ $purchase->items->pluck('item_name')->filter()->implode(', ') ?: $purchase->item_name }}</div><div class="vp-muted">{{ $purchase->items->count() > 1 ? $purchase->items->count().' products' : collect([$purchase->category,$purchase->material,$purchase->gsm ? $purchase->gsm.' GSM' : null])->filter()->implode(' · ') }}</div></td>
                <td>@if($purchase->items->count() > 1)<span class="vp-money">{{ $purchase->items->count() }}</span> products @else<span class="vp-money">{{ number_format($purchase->quantity,2) }}</span> {{ $purchase->unit }}@endif</td>
                <td class="vp-money">{{ $purchase->currency }} {{ number_format($purchase->total_amount,2) }}</td>
                <td><div style="color:#059669;font-weight:750">{{ number_format($purchase->paid_amount,2) }}</div><div class="vp-muted">Balance {{ number_format($purchase->balance_amount,2) }}</div></td>
                <td><span class="vp-status vp-status-{{ strtolower($purchase->payment_status) }}"><i class="fas fa-circle" style="font-size:.38rem"></i>{{ $purchase->payment_status }}</span></td>
                <td>@if($purchase->attachment_path)<a class="vp-attachment" href="{{ asset(ltrim(preg_replace('#^public/#','',$purchase->attachment_path),'/')) }}" target="_blank" rel="noopener" title="{{ $purchase->attachment_name }}"><i class="fas fa-paperclip"></i> View File</a>@else<span class="vp-muted">—</span>@endif</td>
                <td><div class="vp-action-group"><a class="vp-edit-btn" href="{{ route('crm.vendor_purchases.edit',$purchase->id) }}" title="Edit purchase" aria-label="Edit purchase"><i class="fas fa-pen"></i></a>@if($canDeleteVendors)<form method="POST" action="{{ route('crm.vendor_purchases.destroy',$purchase->id) }}" data-delete-target="{{ $purchase->item_name }}" onsubmit="return openVendorDeleteDialog(this,'purchase',this.dataset.deleteTarget);">{{ csrf_field() }}{{ method_field('DELETE') }}<button class="vp-delete-btn" type="submit" title="Delete purchase"><i class="fas fa-trash"></i></button></form>@endif</div></td><td><form class="vp-pay-form" method="POST" action="{{ route('crm.vendor_purchases.update_payment',$purchase->id) }}">{{ csrf_field() }}{{ method_field('PATCH') }}<input class="vp-control" type="number" step="0.01" min="0" max="{{ $purchase->total_amount }}" name="paid_amount" value="{{ $purchase->paid_amount }}" aria-label="Paid amount"><button class="vp-pay-btn" title="Save payment"><i class="fas fa-save"></i></button></form></td>
            </tr>
            @empty
            <tr><td colspan="12"><div class="vp-empty"><i class="fas fa-box-open" style="font-size:2rem;margin-bottom:.7rem"></i><div>No vendor purchases recorded yet.</div></div></td></tr>
            @endforelse
            <tr id="vpPurchaseNoMatch" style="display:none"><td colspan="12"><div class="vp-empty"><i class="fas fa-search" style="font-size:1.6rem;margin-bottom:.6rem"></i><div>No purchases match your search.</div></div></td></tr>
        </tbody></table></div>
        @if($purchases->hasPages())<div class="vp-pagination">{{ $purchases->links() }}</div>@endif
    </div>
    @endif
</div>

@if(!$selectedVendor)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
// Whole-row click opens the vendor/purchase, but ignore clicks on interactive controls
// (links, buttons, inputs, delete/payment forms) and on text selections.
document.addEventListener('click', function (event) {
    var row = event.target.closest('.vp-row-link');
    if (!row || !row.dataset.href) return;
    if (event.target.closest('a, button, input, select, textarea, label, form')) return;
    if (window.getSelection && String(window.getSelection())) return;
    if (event.metaKey || event.ctrlKey) { window.open(row.dataset.href, '_blank'); return; }
    window.location = row.dataset.href;
});
function initVendorExpenseCharts() {
    if (typeof Chart === 'undefined') return;
    (window.vendorExpenseCharts || []).forEach(function (chart) { chart.destroy(); });
    var expenseCharts = [];
    window.vendorExpenseCharts = expenseCharts;

    var donut = document.getElementById('vpExpenseDonut');
    if (donut) {
        expenseCharts.push(new Chart(donut, {
            type: 'doughnut',
            data: {
                labels: ['Production Expenses', 'Personal Expenses'],
                datasets: [{
                    data: @json([$productionTotal, $personalTotal]),
                    backgroundColor: ['#7acb8a', '#ff965f'],
                    borderWidth: 0,
                    hoverOffset: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false }, tooltip: { displayColors: false }, datalabels: { display: false } }
            }
        }));
    }

    var trend = document.getElementById('vpExpenseTrend');
    if (trend) {
        var trendContext = trend.getContext('2d');
        var productionGradient = trendContext.createLinearGradient(0, 0, 0, 225);
        productionGradient.addColorStop(0, 'rgba(111,197,129,.30)');
        productionGradient.addColorStop(1, 'rgba(111,197,129,.025)');
        var personalGradient = trendContext.createLinearGradient(0, 0, 0, 225);
        personalGradient.addColorStop(0, 'rgba(255,125,66,.24)');
        personalGradient.addColorStop(1, 'rgba(255,125,66,.02)');
        expenseCharts.push(new Chart(trend, {
            type: 'line',
            data: {
                labels: @json($expenseTrend->keys()->map(function ($date) { return \Carbon\Carbon::parse($date)->format('d M'); })->values()),
                datasets: [
                    {
                        label: 'Production',
                        data: @json($expenseTrend->pluck('production')->values()),
                        borderColor: '#6fc581',
                        backgroundColor: productionGradient,
                        pointBackgroundColor: '#6fc581',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3.5,
                        pointHoverRadius: 5,
                        borderWidth: 2.4,
                        tension: .38,
                        fill: true
                    },
                    {
                        label: 'Personal',
                        data: @json($expenseTrend->pluck('personal')->values()),
                        borderColor: '#ff7d42',
                        backgroundColor: personalGradient,
                        pointBackgroundColor: '#ff7d42',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3.5,
                        pointHoverRadius: 5,
                        borderWidth: 2.4,
                        tension: .38,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, datalabels: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#8793a6', autoSkip: true, maxTicksLimit: 7, maxRotation: 0, font: { size: 10 } }, border: { display: false } },
                    y: { beginAtZero: true, grid: { color: '#edf1f5', drawTicks: false }, ticks: { color: '#8793a6', padding: 8, font: { size: 10 }, callback: function (value) { return value >= 1000 ? (value / 1000) + 'K' : value; } }, border: { display: false } }
                }
            }
        }));
    }

    if (!window.vendorExpenseChartLifecycleBound) {
        window.vendorExpenseChartLifecycleBound = true;
        window.restoreVendorExpenseCharts = function () {
            if (document.hidden) return;
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    (window.vendorExpenseCharts || []).forEach(function (chart) {
                        chart.resize();
                        chart.update('none');
                    });
                });
            });
        };
        document.addEventListener('visibilitychange', window.restoreVendorExpenseCharts);
        window.addEventListener('pageshow', window.restoreVendorExpenseCharts);
        window.addEventListener('focus', window.restoreVendorExpenseCharts);
    }
    window.restoreVendorExpenseCharts();
}

(function bootVendorExpenseCharts() {
    function start() {
        if (typeof Chart !== 'undefined') {
            initVendorExpenseCharts();
            return;
        }
        var loader = document.querySelector('script[data-vendor-chart-loader]');
        if (!loader) {
            loader = document.createElement('script');
            loader.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
            loader.dataset.vendorChartLoader = '1';
            document.head.appendChild(loader);
        }
        loader.addEventListener('load', initVendorExpenseCharts, { once: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();

// Live vendor filter — no Enter needed.
function vpVendorLiveSearch(term) {
    term = (term || '').trim().toLowerCase();
    var table = document.getElementById('vendorDirectoryTable');
    if (!table || !table.tBodies.length) return;
    Array.prototype.forEach.call(table.tBodies[0].rows, function (row) {
        var hay = row.getAttribute('data-vendor-search');
        if (hay === null) return; // skip the empty-state row
        row.style.display = (term === '' || hay.indexOf(term) !== -1) ? '' : 'none';
    });
}

(function initVendorDirectoryExportSelection() {
    var table = document.getElementById('vendorDirectoryTable');
    var exportForm = document.getElementById('vpExportForm');
    if (!table || !exportForm || !table.tHead || !table.tBodies.length) return;

    var vendorIds = @json($vendors->pluck('id')->values());
    if (!vendorIds.length) return;

    var heading = document.createElement('th');
    heading.innerHTML = '<input type="checkbox" id="vpVendorSelectAll" aria-label="Select all vendors">';
    table.tHead.rows[0].insertBefore(heading, table.tHead.rows[0].firstChild);

    Array.prototype.forEach.call(table.tBodies[0].rows, function (row, index) {
        if (!vendorIds[index]) return;
        var cell = document.createElement('td');
        cell.innerHTML = '<input type="checkbox" class="vp-vendor-row-check" name="vendor_ids[]" value="' + vendorIds[index] + '" form="vpExportForm" aria-label="Select vendor">';
        row.insertBefore(cell, row.firstChild);
    });

    var selectedCount = document.createElement('span');
    selectedCount.className = 'vp-muted';
    selectedCount.id = 'vpVendorSelectedCount';
    selectedCount.textContent = '0 selected';
    exportForm.appendChild(selectedCount);

    function updateCount() {
        selectedCount.textContent = document.querySelectorAll('.vp-vendor-row-check:checked').length + ' selected';
    }

    document.getElementById('vpVendorSelectAll').addEventListener('change', function () {
        document.querySelectorAll('.vp-vendor-row-check').forEach(function (checkbox) {
            checkbox.checked = this.checked;
        }.bind(this));
        updateCount();
    });
    document.addEventListener('change', function (event) {
        if (event.target.classList.contains('vp-vendor-row-check')) updateCount();
    });
})();
</script>
@endif

<div id="vendorMasterModal" class="vp-modal-backdrop" onclick="if(event.target===this) requestVendorClose('vendor')"><div class="vp-modal" style="max-width:560px"><div class="vp-modal-header"><div class="vp-modal-heading"><span class="vp-modal-heading-icon"><i class="fas fa-truck"></i></span><div><h3>Add Vendor</h3><p class="vp-modal-subtitle">Create vendor first, then add purchases against it.</p></div></div><button class="vp-close" type="button" onclick="requestVendorClose('vendor')"><i class="fas fa-times"></i></button></div><form class="vp-form" id="vendorMasterForm" method="POST" action="{{ route('crm.vendors.store') }}">{{ csrf_field() }}<div class="vp-grid"><div class="vp-field vp-field-6"><label>Vendor Name <span class="vp-required">*</span></label><input class="vp-control" name="name" required></div><div class="vp-field vp-field-6"><label>Expense Type <span class="vp-required">*</span></label><select class="vp-control" name="category" required><option value="Production Expense">Production Expense</option><option value="Personal Expense">Personal Expense</option></select></div><div class="vp-field vp-field-6"><label>TRN Number</label><input class="vp-control" name="trn_number" maxlength="100"></div><div class="vp-field vp-field-6"><label>Phone</label><input class="vp-control" name="phone"></div><div class="vp-field vp-field-6"><label>Email</label><input class="vp-control" type="email" name="email"></div><div class="vp-field vp-field-12"><label>Address</label><input class="vp-control" name="address"></div><div class="vp-field vp-field-12"><label>Notes</label><textarea class="vp-control" name="notes"></textarea></div></div><div class="vp-modal-actions"><button class="vp-filter-btn" type="button" onclick="requestVendorClose('vendor')">Cancel</button><button class="vp-primary-btn" type="submit"><i class="fas fa-check"></i> Save Vendor</button></div></form></div></div>

<div id="vendorPurchaseModal" class="vp-modal-backdrop" onclick="if(event.target===this) requestVendorClose('purchase')">
    <div class="vp-modal" role="dialog" aria-modal="true" aria-labelledby="vendorPurchaseTitle">
        <div class="vp-modal-header"><div class="vp-modal-heading"><span class="vp-modal-heading-icon"><i class="fas fa-truck-loading"></i></span><div><h3 id="vendorPurchaseTitle">Add Vendor Purchase</h3><p class="vp-modal-subtitle">Record packaging material purchased from a supplier.</p></div></div><button class="vp-close" type="button" onclick="requestVendorClose('purchase')"><i class="fas fa-times"></i></button></div>
        <form class="vp-form" id="vendorPurchaseForm" method="POST" action="{{ route('crm.vendor_purchases.store') }}" enctype="multipart/form-data">{{ csrf_field() }}<input type="hidden" name="_method" id="vpFormMethod" value="POST">
            <p class="vp-section"><i class="fas fa-building"></i> Vendor & invoice</p>
            <div class="vp-grid">
                <div class="vp-field vp-field-6"><label>Select Vendor <span class="vp-required">*</span></label><select class="vp-control" name="vendor_id" onchange="fillVendorDetails(this)" required><option value="">Choose saved vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" {{ isset($selectedVendor) && $selectedVendor && $selectedVendor->id === $vendor->id ? 'selected' : '' }} data-phone="{{ $vendor->phone }}" data-email="{{ $vendor->email }}">{{ $vendor->name }}</option>@endforeach</select><div class="vp-muted">Vendor missing? Use “Add Vendor” first.</div></div>
                <div class="vp-field vp-field-3"><label>Purchase Date <span class="vp-required">*</span></label><input class="vp-control" type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required></div>
                <div class="vp-field vp-field-3"><label>Invoice Number</label><input class="vp-control" name="invoice_number"></div>
                <div class="vp-field"><label>Vendor Phone</label><input class="vp-control" name="vendor_phone" autocomplete="tel"></div>
                <div class="vp-field"><label>Vendor Email</label><input class="vp-control" type="email" name="vendor_email" autocomplete="email"></div>
                <div class="vp-field"><label>Payment Due Date</label><input class="vp-control" type="date" name="due_date"></div>
            </div>
            <p class="vp-section"><i class="fas fa-box"></i> Packaging material</p>
            <div class="vp-grid">
                <div class="vp-field"><label>Category <span class="vp-required">*</span></label><div class="vp-combobox"><input class="vp-control" name="category" value="Paper & Board" autocomplete="off" oninput="filterVendorCategories(this.value)" onfocus="openVendorCategories()" required><button class="vp-combobox-toggle" type="button" onclick="toggleVendorCategories(event)" aria-label="Show all categories"><i class="fas fa-chevron-down"></i></button><div class="vp-combobox-menu" id="vendorCategoryMenu">@foreach(['Paper & Board','Ink & Printing','Finishing Material','Adhesive','Shipping Material','Other'] as $category)<button class="vp-combobox-option" type="button" data-value="{{ $category }}" onclick="selectVendorCategory(this.dataset.value)">{{ $category }}</button>@endforeach</div></div><small class="vp-muted">Select an option or type a custom category.</small></div>
                <div class="vp-field vp-field-6"><label>Item Name <span class="vp-required">*</span></label><input class="vp-control" name="item_name" placeholder="e.g. Kraft paper rolls" required></div>
                <div class="vp-field vp-field-3"><label>Material</label><input class="vp-control" name="material" placeholder="Kraft, SBS, corrugated..."></div>
                <div class="vp-field"><label>Specification / Grade</label><input class="vp-control" name="specification"></div>
                <div class="vp-field vp-field-3"><label>Size (L × W × H)</label><div class="vp-size-grid"><input class="vp-control" type="number" step="0.01" min="0" name="size_length" placeholder="L"><input class="vp-control" type="number" step="0.01" min="0" name="size_width" placeholder="W"><input class="vp-control" type="number" step="0.01" min="0" name="size_height" placeholder="H"></div></div>
                <div class="vp-field vp-field-3"><label>GSM / Thickness</label><input class="vp-control" name="gsm" placeholder="e.g. 350"></div>
                <div class="vp-field vp-field-3"><label>Color</label><input class="vp-control" name="color"></div>
            </div>
            <p class="vp-section"><i class="fas fa-calculator"></i> Quantity & payment</p>
            <div class="vp-grid">
                <div class="vp-field vp-field-3"><label>Quantity <span class="vp-required">*</span></label><input class="vp-control vp-calc" type="number" step="0.01" min="0.01" name="quantity" value="1" required></div>
                <div class="vp-field vp-field-3"><label>Unit <span class="vp-required">*</span></label><select class="vp-control" name="unit" required>@foreach(['Sheets','Kg','Rolls','Pieces','Boxes','Liters','Meters','Pallets'] as $unit)<option>{{ $unit }}</option>@endforeach</select></div>
                <div class="vp-field vp-field-3"><label>Unit Price <span class="vp-required">*</span></label><input class="vp-control vp-calc" type="number" step="0.01" min="0" name="unit_price" value="0" required></div>
                <div class="vp-field vp-field-3"><label>Currency</label><select class="vp-control" id="vendorCurrencySelect" name="currency">@foreach(['AED','USD','EUR','GBP','PKR','SAR','QAR','OMR','KWD','BHD','CAD','AUD','INR','CNY','JPY'] as $currency)<option value="{{ $currency }}">{{ $currency }}</option>@endforeach</select><small class="vp-help">Currencies load automatically from live API.</small></div>
                <div class="vp-field vp-field-3"><label>VAT %</label><input class="vp-control vp-calc" type="number" step="0.01" min="0" max="100" name="vat_percentage" value="5"></div>
                <div class="vp-field vp-field-3"><label>Shipping Cost</label><input class="vp-control vp-calc" type="number" step="0.01" min="0" name="shipping_cost" value="0"></div>
                <div class="vp-field vp-field-3"><label>Paid Amount</label><input class="vp-control vp-calc" type="number" step="0.01" min="0" name="paid_amount" value="0"></div>
                <div class="vp-field vp-field-3"><label>Payment Status <span class="vp-required">*</span></label><select class="vp-control vp-calc" name="payment_status" required><option value="Unpaid">Unpaid</option><option value="Partial">Partial</option><option value="Paid">Paid</option></select></div>
                <div class="vp-field vp-field-3"><label>Payment Method</label><select class="vp-control" name="payment_method"><option value="">Select method</option>@foreach(['Cash','Bank Transfer','Card','Cheque','Credit'] as $method)<option>{{ $method }}</option>@endforeach</select></div>
                <div class="vp-field vp-field-12"><div class="vp-total-panel"><div><div class="vp-total-label">Subtotal</div><div class="vp-total-value" id="vpSubtotal">0.00</div></div><div><div class="vp-total-label">Total</div><div class="vp-total-value" id="vpTotal">0.00</div></div><div><div class="vp-total-label">Balance</div><div class="vp-total-value" id="vpBalance">0.00</div></div><div><div class="vp-total-label">Status</div><div class="vp-total-value" id="vpStatus">Unpaid</div></div></div></div>
                <div class="vp-field vp-field-12"><label>Notes</label><textarea class="vp-control" name="notes" placeholder="Quality, delivery, batch or other notes..."></textarea></div>
                <div class="vp-field vp-field-12"><label>Invoice / Purchase Attachment</label><input class="vp-control" type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv"><small class="vp-muted">PDF, image, Word, Excel or CSV — maximum 20 MB. On edit, leave blank to keep the existing file.</small></div>
            </div>
            <div class="vp-modal-actions"><button class="vp-filter-btn" type="button" onclick="requestVendorClose('purchase')">Cancel</button><button class="vp-primary-btn" type="submit"><i class="fas fa-check-circle"></i> Save Purchase</button></div>
        </form>
    </div>
</div>

<div id="vendorDeleteDialog" class="vp-modal-backdrop" onclick="if(event.target===this) closeVendorDeleteDialog()">
    <div class="vp-modal vp-guard" role="alertdialog" aria-modal="true" aria-labelledby="vendorDeleteTitle">
        <div class="vp-modal-header vp-delete-heading">
            <div class="vp-modal-heading"><span class="vp-modal-heading-icon"><i class="fas fa-trash-alt"></i></span><div><h3 id="vendorDeleteTitle">Delete record?</h3><p class="vp-modal-subtitle">This action requires confirmation.</p></div></div>
            <button class="vp-close" type="button" onclick="closeVendorDeleteDialog()"><i class="fas fa-times"></i></button>
        </div>
        <div class="vp-guard-body">
            Are you sure you want to permanently delete <span class="vp-delete-target" id="vendorDeleteTarget"></span>?
            <div class="vp-delete-warning"><i class="fas fa-exclamation-triangle"></i><span id="vendorDeleteWarning">Deleted records cannot be restored.</span></div>
        </div>
        <div class="vp-guard-actions"><button class="vp-filter-btn" type="button" onclick="closeVendorDeleteDialog()">Cancel</button><button class="vp-danger-btn vp-danger-btn-solid" type="button" onclick="confirmVendorDelete()"><i class="fas fa-trash"></i> Confirm Delete</button></div>
    </div>
</div>

<div id="vendorUnsavedGuard" class="vp-modal-backdrop">
    <div class="vp-modal vp-guard" role="alertdialog" aria-modal="true" aria-labelledby="vendorGuardTitle">
        <div class="vp-modal-header"><div class="vp-modal-heading"><span class="vp-modal-heading-icon"><i class="fas fa-save"></i></span><div><h3 id="vendorGuardTitle">Unsaved Changes</h3><p class="vp-modal-subtitle">Your entered vendor information has not been saved yet.</p></div></div></div>
        <div class="vp-guard-body">Leaving now may lose the details you entered. Save the form, discard your changes, or stay here and continue editing.</div>
        <div class="vp-guard-actions"><button class="vp-filter-btn" type="button" onclick="stayInVendorForm()">Stay Here</button><button class="vp-danger-btn" type="button" onclick="discardVendorChanges()">Discard</button><button class="vp-primary-btn" type="button" onclick="saveAndExitVendorForm()"><i class="fas fa-save"></i> Save &amp; Exit</button></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    (function loadCurrencies(){var select=document.getElementById('vendorCurrencySelect');if(!select)return;var fallback={AED:'UAE Dirham',USD:'US Dollar',EUR:'Euro',GBP:'British Pound',PKR:'Pakistani Rupee',SAR:'Saudi Riyal',QAR:'Qatari Riyal',OMR:'Omani Rial',KWD:'Kuwaiti Dinar',BHD:'Bahraini Dinar',CAD:'Canadian Dollar',AUD:'Australian Dollar',INR:'Indian Rupee',CNY:'Chinese Yuan',JPY:'Japanese Yen'};function render(currencies){var current=select.value||'AED';select.innerHTML='';Object.keys(currencies).sort().forEach(function(code){var option=document.createElement('option');option.value=code;option.textContent=code+' — '+currencies[code];select.appendChild(option)});select.value=currencies[current]?current:'AED'}fetch('https://restcountries.com/v3.1/all?fields=currencies').then(function(response){if(!response.ok)throw new Error('Currency API unavailable');return response.json()}).then(function(countries){var currencies=Object.assign({},fallback);countries.forEach(function(country){Object.keys(country.currencies||{}).forEach(function(code){currencies[code]=(country.currencies[code]&&country.currencies[code].name)||code})});render(currencies)}).catch(function(){render(fallback)})})();
    function setVendorView(view){var cards=document.querySelector('.vp-vendor-grid'),table=document.getElementById('vendorDirectoryTable');if(!cards||!table)return;cards.style.display=view==='cards'?'grid':'none';table.style.display=view==='table'?'table':'none'}
    function filterVendorDirectory(value){var q=(value||'').trim().toLowerCase();document.querySelectorAll('#vendorDirectoryTable tbody tr[data-vendor-search]').forEach(function(row){var hay=(row.getAttribute('data-vendor-search')||row.textContent||'').toLowerCase();row.style.display=!q||hay.indexOf(q)!==-1?'table-row':'none'});}
    (function(){var directoryTable=document.getElementById('vendorDirectoryTable');if(!directoryTable)return;var search=document.querySelector('.vp-toolbar [name="search"]');if(search)search.addEventListener('input',function(){filterVendorDirectory(this.value)});})();
    // Live search for a selected vendor's purchases list.
    function vpPurchaseLiveSearch(value){var q=(value||'').trim().toLowerCase();var rows=document.querySelectorAll('.vp-table tbody tr[data-purchase-search]');var shown=0;rows.forEach(function(row){var hay=row.getAttribute('data-purchase-search')||'';var match=!q||hay.indexOf(q)!==-1;row.style.display=match?'':'none';if(match)shown++;});var empty=document.getElementById('vpPurchaseNoMatch');if(empty)empty.style.display=(shown===0&&rows.length>0)?'':'none';}
    setTimeout(function(){
    (function(){document.querySelectorAll('.vp-date-filter').forEach(function(filter){var preset=filter.querySelector('select'),inputs=filter.querySelectorAll('input'),from=inputs[0],to=inputs[1],separator=filter.querySelector('.vp-date-separator');if(!preset||!from||!to)return;var trigger=document.createElement('button');trigger.type='button';trigger.className='vp-date-trigger';trigger.innerHTML='<span></span><i class="fas fa-calendar-alt"></i>';var popover=document.createElement('div');popover.className='vp-date-popover';popover.innerHTML='<span class="vp-date-popover-label">Choose a custom date range</span><div class="vp-date-popover-row"></div><div class="vp-date-popover-actions"><button class="vp-date-clear" type="button">Clear</button><button class="vp-date-apply" type="button">Apply</button></div>';filter.appendChild(trigger);filter.appendChild(popover);var row=popover.querySelector('.vp-date-popover-row');row.appendChild(from);row.appendChild(separator);row.appendChild(to);function title(){var fmt=function(value){if(!value)return '';var parts=value.split('-');return parts.length===3?parts[2]+'/'+parts[1]+'/'+parts[0]:value};trigger.querySelector('span').textContent=from.value&&to.value?fmt(from.value)+' — '+fmt(to.value):'Date range'}function close(){popover.classList.remove('show')}title();trigger.addEventListener('click',function(){preset.value='';popover.classList.toggle('show')});preset.addEventListener('change',function(){if(this.value===''){popover.classList.add('show')}else{close();setTimeout(title,0)}});popover.querySelector('.vp-date-apply').addEventListener('click',function(){if(from.value&&to.value){close();title();filter.closest('form').submit()}});popover.querySelector('.vp-date-clear').addEventListener('click',function(){from.value='';to.value='';close();title();filter.closest('form').submit()});document.addEventListener('click',function(event){if(!filter.contains(event.target))close()})})})();
    },0);
    setTimeout(function(){document.querySelectorAll('.vp-date-filter').forEach(function(filter){var preset=filter.querySelector('select'),inputs=filter.querySelectorAll('input'),from=inputs[0],to=inputs[1],form=filter.closest('form'),popover=filter.querySelector('.vp-date-popover');if(!preset||!from||!to||!popover)return;function preventAutoSubmit(event){if(preset.value==='')event.stopImmediatePropagation()}from.addEventListener('change',preventAutoSubmit,true);to.addEventListener('change',preventAutoSubmit,true);popover.querySelector('.vp-date-apply').addEventListener('click',function(){form.elements.date_from.value=from.value;form.elements.date_to.value=to.value});popover.querySelector('.vp-date-clear').addEventListener('click',function(){form.elements.date_from.value='';form.elements.date_to.value=''})})},0);
    function openVendorCategories(){document.getElementById('vendorCategoryMenu').classList.add('show');filterVendorCategories(document.querySelector('#vendorPurchaseForm [name="category"]').value)}
    function toggleVendorCategories(event){event.stopPropagation();var menu=document.getElementById('vendorCategoryMenu');var opening=!menu.classList.contains('show');menu.classList.toggle('show',opening);if(opening){document.querySelectorAll('#vendorCategoryMenu .vp-combobox-option').forEach(function(option){option.style.display='block'})}}
    function filterVendorCategories(value){var query=(value||'').trim().toLowerCase();document.querySelectorAll('#vendorCategoryMenu .vp-combobox-option').forEach(function(option){option.style.display=!query||option.dataset.value.toLowerCase().indexOf(query)!==-1?'block':'none'})}
    function selectVendorCategory(value){var input=document.querySelector('#vendorPurchaseForm [name="category"]');input.value=value;document.getElementById('vendorCategoryMenu').classList.remove('show');input.dispatchEvent(new Event('change',{bubbles:true}))}
    document.addEventListener('click',function(event){var combo=event.target.closest('.vp-combobox');if(!combo)document.getElementById('vendorCategoryMenu').classList.remove('show')});
    (function(){var form=document.querySelector('.vp-toolbar');if(!form)return;function submitFilters(){form.submit()}['category','payment_status'].forEach(function(name){if(form.elements[name])form.elements[name].addEventListener('change',submitFilters)})})();
    function fillVendorDetails(select){var option=select.options[select.selectedIndex],form=document.getElementById('vendorPurchaseForm');form.elements.vendor_phone.value=option?option.getAttribute('data-phone')||'':'';form.elements.vendor_email.value=option?option.getAttribute('data-email')||'':''}
    var vendorFormSnapshots={}, pendingVendorClose=null;
    function vendorFormState(form){var values=[];Array.prototype.forEach.call(form.elements,function(field){if(!field.name||field.name==='_token'||field.name==='_method')return;if(field.type==='file'){values.push(field.name+':'+(field.files&&field.files[0]?field.files[0].name:''));return}if((field.type==='checkbox'||field.type==='radio')&&!field.checked)return;values.push(field.name+':'+field.value)});return values.join('|')}
    function rememberVendorForm(type){var form=document.getElementById(type==='vendor'?'vendorMasterForm':'vendorPurchaseForm');vendorFormSnapshots[type]=vendorFormState(form)}
    function vendorFormChanged(type){var form=document.getElementById(type==='vendor'?'vendorMasterForm':'vendorPurchaseForm');return vendorFormState(form)!==(vendorFormSnapshots[type]||'')}
    function openVendorModal(){var form=document.getElementById('vendorMasterForm');form.reset();document.getElementById('vendorMasterModal').style.display='flex';document.body.style.overflow='hidden';rememberVendorForm('vendor')}
    function closeVendorModal(){document.getElementById('vendorMasterModal').style.display='none';document.body.style.overflow=''}
    function requestVendorClose(type){if(vendorFormChanged(type)){pendingVendorClose=type;document.getElementById('vendorUnsavedGuard').style.display='flex';return}forceCloseVendorForm(type)}
    function forceCloseVendorForm(type){if(type==='vendor')closeVendorModal();else closePurchaseModal();pendingVendorClose=null}
    function stayInVendorForm(){document.getElementById('vendorUnsavedGuard').style.display='none';pendingVendorClose=null}
    function discardVendorChanges(){var type=pendingVendorClose;document.getElementById('vendorUnsavedGuard').style.display='none';forceCloseVendorForm(type)}
    function saveAndExitVendorForm(){var type=pendingVendorClose,form=document.getElementById(type==='vendor'?'vendorMasterForm':'vendorPurchaseForm');if(form.reportValidity()){vendorFormSnapshots[type]=vendorFormState(form);form.requestSubmit?form.requestSubmit():form.submit()}}
    var pendingVendorDeleteForm=null;
    function openVendorDeleteDialog(form,type,target){pendingVendorDeleteForm=form;document.getElementById('vendorDeleteTitle').textContent=type==='vendor'?'Delete vendor?':'Delete purchase?';document.getElementById('vendorDeleteTarget').textContent=target||'this record';document.getElementById('vendorDeleteWarning').textContent=type==='vendor'?'A vendor with purchase history must have its purchases deleted first.':'This purchase and its attachment will be permanently removed.';document.getElementById('vendorDeleteDialog').style.display='flex';document.body.style.overflow='hidden';return false}
    function closeVendorDeleteDialog(){document.getElementById('vendorDeleteDialog').style.display='none';document.body.style.overflow='';pendingVendorDeleteForm=null}
    function confirmVendorDelete(){if(!pendingVendorDeleteForm)return;var form=pendingVendorDeleteForm;pendingVendorDeleteForm=null;form.submit()}
    var vendorPurchases = @json($purchases->getCollection()->keyBy('id'));
    document.getElementById('vpExportForm').addEventListener('submit',function(){var filters=document.querySelector('.vp-toolbar');['search','expense_type','category','payment_status','date_from','date_to'].forEach(function(name){if(this.elements[name]&&filters.elements[name])this.elements[name].value=filters.elements[name].value}.bind(this));});
    (function(){function iso(date){return date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0')+'-'+String(date.getDate()).padStart(2,'0')}function presetFor(from,to){var today=new Date(),todayValue=iso(today);if(from===todayValue&&to===todayValue)return 'today';var weekStart=new Date(today);weekStart.setDate(today.getDate()-(today.getDay()+6)%7);var weekEnd=new Date(weekStart);weekEnd.setDate(weekStart.getDate()+6);if(from===iso(weekStart)&&to===iso(weekEnd))return 'week';var monthStart=new Date(today.getFullYear(),today.getMonth(),1),monthEnd=new Date(today.getFullYear(),today.getMonth()+1,0);if(from===iso(monthStart)&&to===iso(monthEnd))return 'month';return ''}document.querySelectorAll('.vp-date-range').forEach(function(legacy){var form=legacy.closest('form'),hiddenFrom=form.elements.date_from,hiddenTo=form.elements.date_to;if(!hiddenFrom||!hiddenTo)return;legacy.style.display='none';var wrap=document.createElement('div');wrap.className='vp-date-filter';wrap.innerHTML='<select class="vp-control" aria-label="Quick date range"><option value="">Custom range</option><option value="today">Today</option><option value="week">This week</option><option value="month">This month</option></select><input class="vp-control" type="date" aria-label="Start date"><span class="vp-date-separator">—</span><input class="vp-control" type="date" aria-label="End date">';legacy.parentNode.insertBefore(wrap,legacy);var preset=wrap.querySelector('select'),from=wrap.querySelectorAll('input')[0],to=wrap.querySelectorAll('input')[1];from.value=hiddenFrom.value||'';to.value=hiddenTo.value||'';preset.value=presetFor(from.value,to.value);function sync(submit){hiddenFrom.value=from.value;hiddenTo.value=to.value;preset.value=presetFor(from.value,to.value);if(submit&&from.value&&to.value)form.submit()}from.addEventListener('change',function(){sync(true)});to.addEventListener('change',function(){sync(true)});preset.addEventListener('change',function(){if(!this.value)return;var today=new Date(),start,end;if(this.value==='today'){start=end=today}else if(this.value==='week'){start=new Date(today);start.setDate(today.getDate()-(today.getDay()+6)%7);end=new Date(start);end.setDate(start.getDate()+6)}else{start=new Date(today.getFullYear(),today.getMonth(),1);end=new Date(today.getFullYear(),today.getMonth()+1,0)}from.value=iso(start);to.value=iso(end);sync(true)})})})();
    (function(){var table=document.querySelector('.vp-table');if(!table)return;var head=table.tHead.rows[0], h=document.createElement('th');h.innerHTML='<input type="checkbox" id="vpSelectAll">';head.insertBefore(h,head.firstChild);Array.prototype.forEach.call(table.tBodies[0].rows,function(row){if(row.cells.length<2)return;var c=document.createElement('td');c.innerHTML='<input type="checkbox" class="vp-row-check" name="ids[]" value="'+row.dataset.id+'" form="vpExportForm">';row.insertBefore(c,row.firstChild)});var rows=@json($purchases->getCollection()->pluck('id')->values());Array.prototype.forEach.call(table.tBodies[0].rows,function(row,i){if(rows[i])row.dataset.id=rows[i];var cb=row.querySelector('.vp-row-check');if(cb&&rows[i])cb.value=rows[i]});function count(){var n=document.querySelectorAll('.vp-row-check:checked').length;document.getElementById('vpSelectedCount').textContent=n+' selected'}document.getElementById('vpSelectAll').addEventListener('change',function(){document.querySelectorAll('.vp-row-check').forEach(function(c){c.checked=this.checked}.bind(this));count()});document.addEventListener('change',function(e){if(e.target.classList.contains('vp-row-check'))count()})})();
    function openPurchaseModal(){ var form=document.getElementById('vendorPurchaseForm');form.reset();form.action='{{ route('crm.vendor_purchases.store') }}';document.getElementById('vpFormMethod').value='POST';document.getElementById('vendorPurchaseTitle').textContent='Add Vendor Purchase';form.querySelector('[name="purchase_date"]').value='{{ date('Y-m-d') }}';fillVendorDetails(form.elements.vendor_id);document.getElementById('vendorPurchaseModal').style.display='flex';document.body.style.overflow='hidden';calculateVendorPurchase();rememberVendorForm('purchase'); }
    function openEditPurchase(id){var p=vendorPurchases[id];if(!p)return;var form=document.getElementById('vendorPurchaseForm');form.reset();form.action='{{ url('/crm/vendor-purchases') }}/'+id;document.getElementById('vpFormMethod').value='PUT';document.getElementById('vendorPurchaseTitle').textContent='Edit Vendor Purchase';Object.keys(p).forEach(function(name){var field=form.elements[name];if(!field)return;var value=p[name];if((name==='purchase_date'||name==='due_date')&&value)value=String(value).substring(0,10);field.value=value===null?'':value});var sizeParts=String(p.size||'').split(/\s*(?:x|×|\*)\s*/i);form.elements.size_length.value=sizeParts[0]||'';form.elements.size_width.value=sizeParts[1]||'';form.elements.size_height.value=sizeParts[2]||'';document.getElementById('vendorPurchaseModal').style.display='flex';document.body.style.overflow='hidden';calculateVendorPurchase();rememberVendorForm('purchase')}
    function closePurchaseModal(){ document.getElementById('vendorPurchaseModal').style.display='none'; document.body.style.overflow=''; }
    function purchaseNumber(name){ var field=document.querySelector('#vendorPurchaseModal [name="'+name+'"]'); return parseFloat(field && field.value)||0; }
    function calculateVendorPurchase(){
        var subtotal=purchaseNumber('quantity')*purchaseNumber('unit_price');
        var total=subtotal+(subtotal*purchaseNumber('vat_percentage')/100)+purchaseNumber('shipping_cost');
        var form=document.getElementById('vendorPurchaseForm');
        var status=form.elements.payment_status.value;
        var paidField=form.elements.paid_amount;
        if(status==='Unpaid')paidField.value='0';
        if(status==='Paid')paidField.value=total.toFixed(2);
        paidField.readOnly=status!=='Partial';
        var paid=Math.min(purchaseNumber('paid_amount'),total);
        var balance=Math.max(total-paid,0);
        document.getElementById('vpSubtotal').textContent=subtotal.toFixed(2);
        document.getElementById('vpTotal').textContent=total.toFixed(2);
        document.getElementById('vpBalance').textContent=balance.toFixed(2);
        document.getElementById('vpStatus').textContent=status;
    }
    document.querySelectorAll('.vp-calc').forEach(function(field){ field.addEventListener('input',calculateVendorPurchase); });
    document.addEventListener('keydown',function(event){if(event.key!=='Escape')return;if(document.getElementById('vendorUnsavedGuard').style.display==='flex'){stayInVendorForm();return}if(document.getElementById('vendorPurchaseModal').style.display==='flex')requestVendorClose('purchase');else if(document.getElementById('vendorMasterModal').style.display==='flex')requestVendorClose('vendor')});
    @if($errors->any()) openPurchaseModal(); @endif
</script>
@endsection
