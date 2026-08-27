@extends('crm.layout')

@section('title', 'Team Performance')

@section('content')
<style>
    .tp-page{color:#1f2a3d}.tp-intro{margin-bottom:1rem;color:#74839a;font-size:.82rem}.tp-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}.tp-summary-card,.tp-person{background:#fff;border:1px solid #e6ecf3;border-radius:17px;box-shadow:0 7px 24px rgba(15,23,42,.05)}.tp-summary-card{display:flex;align-items:center;gap:1rem;min-height:98px;padding:1rem 1.2rem}.tp-summary-icon,.tp-metric-icon{display:flex;align-items:center;justify-content:center;border-radius:14px}.tp-summary-icon{width:50px;height:50px;flex:0 0 50px}.tp-summary-copy span,.tp-metric-label{display:block;color:#8b9ab0;font-size:.67rem;font-weight:850;letter-spacing:.055em;text-transform:uppercase}.tp-summary-copy strong{display:block;margin-top:.18rem;font-size:1.45rem;color:#111827}.tp-summary-copy small{display:block;margin-top:.25rem;color:#10a875;font-size:.67rem;font-weight:800}
    .tp-export{position:relative}.tp-export-button{height:42px;padding:.65rem 1rem;border:0;border-radius:11px;background:var(--primary-purple);color:#fff;font-weight:800;cursor:pointer;box-shadow:0 8px 20px var(--primary-shadow)}.tp-export-menu{display:none;position:absolute;right:0;top:calc(100% + 7px);z-index:100;min-width:145px;padding:.35rem;background:#fff;border:1px solid #e2e8f0;border-radius:11px;box-shadow:0 14px 30px rgba(15,23,42,.16)}.tp-export-menu.show{display:block}.tp-export-menu a{display:flex;align-items:center;gap:.55rem;padding:.65rem .75rem;border-radius:8px;color:#334155;text-decoration:none;font-size:.76rem;font-weight:750}.tp-export-menu a:hover{background:var(--primary-soft);color:var(--primary-purple)}
    .tp-filters{display:grid;grid-template-columns:minmax(220px,1fr) minmax(220px,320px) 190px;gap:.75rem;margin-bottom:1.1rem}.tp-control-wrap{position:relative}.tp-control-wrap>i{position:absolute;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}.tp-control-wrap>i:first-child{left:.9rem}.tp-date>i{left:auto!important;right:.9rem}.tp-control{width:100%;height:46px;padding:.7rem .9rem;border:1px solid #dde5ee;border-radius:12px;background:#fff;color:#334155;outline:0;box-sizing:border-box;font-size:.82rem}.tp-control-wrap .tp-control{padding-left:2.5rem}.tp-control-wrap.tp-date .tp-control{padding-left:.9rem;padding-right:2.8rem}.tp-control:focus{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow)}
    .tp-selection{display:flex;align-items:center;gap:.7rem;margin-bottom:.85rem;padding:.7rem .85rem;background:#fff;border:1px solid #e5ebf2;border-radius:12px}.tp-select-all{display:flex;align-items:center;gap:.45rem;color:#64748b;font-size:.72rem;font-weight:800}.tp-checkbox{width:17px;height:17px;accent-color:var(--primary-purple);cursor:pointer}.tp-selected-count{color:var(--primary-purple);font-size:.72rem;font-weight:850}.tp-selected-actions{position:relative;margin-left:auto}.tp-selected-button{height:36px;padding:.48rem .8rem;border:0;border-radius:9px;background:var(--primary-purple);color:#fff;font-size:.7rem;font-weight:800;cursor:pointer}.tp-selected-button[disabled]{opacity:.42;cursor:not-allowed}.tp-selected-menu{display:none;position:absolute;right:0;top:calc(100% + 6px);z-index:90;min-width:145px;padding:.35rem;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 13px 28px rgba(15,23,42,.16)}.tp-selected-menu.show{display:block}.tp-selected-option{display:flex;width:100%;align-items:center;gap:.5rem;padding:.62rem .7rem;border:0;border-radius:7px;background:#fff;color:#334155;text-align:left;font-size:.72rem;font-weight:750;cursor:pointer}.tp-selected-option:hover{background:var(--primary-soft);color:var(--primary-purple)}
    .tp-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.tp-person{padding:1rem}.tp-person.selected{border-color:var(--primary-purple);box-shadow:0 0 0 3px var(--primary-shadow),0 7px 24px rgba(15,23,42,.05)}.tp-person-head{display:flex;align-items:center;gap:.75rem;padding-bottom:.85rem;border-bottom:1px solid #edf1f5}.tp-avatar{width:46px;height:46px;display:flex;align-items:center;justify-content:center;border-radius:14px;background:var(--primary-soft);color:var(--primary-purple);font-weight:900}.tp-person-name{font-size:.92rem;font-weight:850;color:#1e293b}.tp-role{display:inline-flex;margin-top:.22rem;padding:.22rem .5rem;border-radius:999px;background:#f1f5f9;color:#64748b;font-size:.62rem;font-weight:800}.tp-score{margin-left:auto;text-align:right}.tp-score strong{display:block;color:var(--primary-purple);font-size:1.15rem}.tp-score span{color:#94a3b8;font-size:.6rem;text-transform:uppercase;font-weight:800}.tp-person-export{position:relative}.tp-person-export-button{width:34px;height:34px;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:var(--primary-purple);cursor:pointer}.tp-person-export-menu{display:none;position:absolute;right:0;top:calc(100% + 6px);z-index:80;min-width:130px;padding:.3rem;background:#fff;border:1px solid #e2e8f0;border-radius:9px;box-shadow:0 12px 26px rgba(15,23,42,.15)}.tp-person-export-menu.show{display:block}.tp-person-export-menu a{display:flex;align-items:center;gap:.45rem;padding:.55rem .65rem;border-radius:7px;color:#334155;text-decoration:none;font-size:.7rem;font-weight:750}.tp-person-export-menu a:hover{background:var(--primary-soft);color:var(--primary-purple)}
    .tp-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem;margin-top:.85rem}.tp-metric{display:flex;align-items:center;gap:.55rem;padding:.7rem;border-radius:12px;background:#f8fafc;border:1px solid #edf1f5;min-width:0}.tp-metric-icon{width:34px;height:34px;flex:0 0 34px;font-size:.75rem}.tp-metric-value{font-size:1rem;font-weight:900;color:#172033}.tp-metric-label{font-size:.56rem;line-height:1.25}.tp-blue{color:#0284c7;background:#e0f2fe}.tp-purple{color:#9333ea;background:#f3e8ff}.tp-green{color:#16a34a;background:#dcfce7}.tp-empty{grid-column:1/-1;padding:3rem;text-align:center;background:#fff;border:1px solid #e6ecf3;border-radius:17px;color:#94a3b8}
    @media(max-width:1050px){.tp-grid{grid-template-columns:1fr}.tp-filters{grid-template-columns:1fr 1fr}.tp-filters>div:last-child{grid-column:1/-1}.tp-summary{grid-template-columns:1fr}}
    @media(max-width:650px){.tp-filters{grid-template-columns:1fr}.tp-filters>div:last-child{grid-column:auto}.tp-metrics{grid-template-columns:1fr}.tp-summary-card{min-height:84px}.tp-selection{flex-wrap:wrap}.tp-selected-actions{width:100%;margin-left:0}.tp-selected-button{flex:1}}
</style>

<div class="tp-page">
    <div class="tp-intro">Role-based performance overview for the selected project and date range.</div>

    <div class="tp-summary">
        <div class="tp-summary-card"><span class="tp-summary-icon tp-blue"><i class="fas fa-users"></i></span><div class="tp-summary-copy"><span>Team Members</span><strong>{{ number_format($teamSummary['members']) }}</strong><small>Users in current project</small></div></div>
        <div class="tp-summary-card"><span class="tp-summary-icon tp-purple"><i class="fas fa-chart-line"></i></span><div class="tp-summary-copy"><span>Total Activity</span><strong>{{ number_format($teamSummary['activity']) }}</strong><small>Role-based actions in range</small></div></div>
        <div class="tp-summary-card"><span class="tp-summary-icon tp-green"><i class="fas fa-user-check"></i></span><div class="tp-summary-copy"><span>Active Members</span><strong>{{ number_format($teamSummary['active_members']) }}</strong><small>Members with recorded activity</small></div></div>
    </div>

    <form class="tp-filters" method="GET" action="{{ route('crm.team_performance') }}">
        <div class="tp-control-wrap"><i class="fas fa-search"></i><input class="tp-control" type="search" name="search" value="{{ $search }}" placeholder="Search team member..." oninput="clearTimeout(window.tpSearchTimer);window.tpSearchTimer=setTimeout(()=>this.form.submit(),500)"></div>
        <div class="tp-control-wrap tp-date"><input class="tp-control" type="text" id="daterange" placeholder="Select date range" value="{{ request('start_date') && request('end_date') ? request('start_date').' - '.request('end_date') : '' }}"><i class="far fa-calendar-alt"></i><input type="hidden" name="start_date" id="start_date" value="{{ request('start_date') }}"><input type="hidden" name="end_date" id="end_date" value="{{ request('end_date') }}"></div>
        <div><select class="tp-control" name="range" onchange="this.form.submit()"><option value="today" {{ $range==='today'?'selected':'' }}>Today</option><option value="this_week" {{ $range==='this_week'?'selected':'' }}>This Week</option><option value="this_month" {{ $range==='this_month'?'selected':'' }}>This Month</option><option value="this_year" {{ $range==='this_year'?'selected':'' }}>This Year</option></select></div>
    </form>

    <form class="tp-selection" id="tpSelectedExportForm" method="GET" action="{{ route('crm.team_performance.export') }}">
        @if($search)<input type="hidden" name="search" value="{{ $search }}">@endif
        @if($start_date)<input type="hidden" name="start_date" value="{{ $start_date }}">@endif
        @if($end_date)<input type="hidden" name="end_date" value="{{ $end_date }}">@endif
        <input type="hidden" name="range" value="{{ $range }}">
        <span id="tpSelectedIds"></span>
        <label class="tp-select-all"><input class="tp-checkbox" id="tpSelectAll" type="checkbox"> Select All</label>
        <span class="tp-selected-count" id="tpSelectedCount">0 selected</span>
        <div class="tp-selected-actions">
            <button class="tp-selected-button" id="tpSelectedExportButton" type="button" disabled onclick="document.getElementById('tpSelectedMenu').classList.toggle('show')"><i class="fas fa-download"></i> Export <i class="fas fa-chevron-down"></i></button>
            <div class="tp-selected-menu" id="tpSelectedMenu">
                <button class="tp-selected-option" type="submit" name="format" value="excel"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="tp-selected-option" type="submit" name="format" value="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
            </div>
        </div>
    </form>

    <div class="tp-grid">
        @forelse($performanceData as $data)
            <article class="tp-person">
                <div class="tp-person-head">
                    <input class="tp-checkbox tp-user-check" type="checkbox" value="{{ $data['id'] }}" aria-label="Select {{ $data['name'] }}">
                    <span class="tp-avatar">{{ strtoupper(substr($data['name'],0,2)) }}</span>
                    <div><div class="tp-person-name">{{ $data['name'] }}</div><span class="tp-role">{{ $data['role_label'] }}</span></div>
                    <div class="tp-score"><strong>{{ number_format($data['score']) }}</strong><span>Total activity</span></div>
                    <div class="tp-person-export">
                        <button class="tp-person-export-button" type="button" title="Export {{ $data['name'] }}" onclick="event.stopPropagation();document.querySelectorAll('.tp-person-export-menu.show').forEach(function(menu){if(menu!==this.nextElementSibling)menu.classList.remove('show')},this);this.nextElementSibling.classList.toggle('show')"><i class="fas fa-download"></i></button>
                        <div class="tp-person-export-menu">
                            <a href="{{ route('crm.team_performance.export', array_merge(request()->except(['format','user_id']), ['format'=>'excel','user_id'=>$data['id']])) }}"><i class="fas fa-file-excel"></i> Excel</a>
                            <a href="{{ route('crm.team_performance.export', array_merge(request()->except(['format','user_id']), ['format'=>'pdf','user_id'=>$data['id']])) }}"><i class="fas fa-file-pdf"></i> PDF</a>
                        </div>
                    </div>
                </div>
                <div class="tp-metrics">
                    @foreach($data['metrics'] as $metric)
                        <div class="tp-metric"><span class="tp-metric-icon tp-{{ $metric['tone'] }}"><i class="fas fa-{{ $metric['icon'] }}"></i></span><div><div class="tp-metric-value">{{ number_format($metric['value']) }}</div><span class="tp-metric-label">{{ $metric['label'] }}</span></div></div>
                    @endforeach
                </div>
            </article>
        @empty
            <div class="tp-empty"><i class="fas fa-users" style="font-size:1.8rem;margin-bottom:.7rem"></i><div>No team members match the selected filters.</div></div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function(){
    $('#daterange').daterangepicker({autoUpdateInput:false,opens:'left',locale:{cancelLabel:'Clear',format:'YYYY-MM-DD',applyLabel:'Filter'}});
    $('#daterange').on('apply.daterangepicker',function(ev,picker){$(this).val(picker.startDate.format('YYYY-MM-DD')+' - '+picker.endDate.format('YYYY-MM-DD'));$('#start_date').val(picker.startDate.format('YYYY-MM-DD'));$('#end_date').val(picker.endDate.format('YYYY-MM-DD'));this.form.submit()});
    $('#daterange').on('cancel.daterangepicker',function(){$(this).val('');$('#start_date,#end_date').val('');this.form.submit()});
});
function updateTeamSelection(){
    var checks=Array.prototype.slice.call(document.querySelectorAll('.tp-user-check')),selected=checks.filter(function(check){return check.checked}),holder=document.getElementById('tpSelectedIds');
    holder.innerHTML='';
    selected.forEach(function(check){var input=document.createElement('input');input.type='hidden';input.name='ids[]';input.value=check.value;holder.appendChild(input);check.closest('.tp-person').classList.add('selected')});
    checks.filter(function(check){return !check.checked}).forEach(function(check){check.closest('.tp-person').classList.remove('selected')});
    document.getElementById('tpSelectedCount').textContent=selected.length+' selected';
    document.getElementById('tpSelectedExportButton').disabled=selected.length===0;
    if(selected.length===0)document.getElementById('tpSelectedMenu').classList.remove('show');
    var all=document.getElementById('tpSelectAll');all.checked=checks.length>0&&selected.length===checks.length;all.indeterminate=selected.length>0&&selected.length<checks.length;
}
document.querySelectorAll('.tp-user-check').forEach(function(check){check.addEventListener('change',updateTeamSelection)});
document.getElementById('tpSelectAll').addEventListener('change',function(){var checked=this.checked;document.querySelectorAll('.tp-user-check').forEach(function(check){check.checked=checked});updateTeamSelection()});
document.addEventListener('click',function(event){var selectedBox=document.querySelector('.tp-selected-actions');if(selectedBox&&!selectedBox.contains(event.target))document.getElementById('tpSelectedMenu').classList.remove('show');document.querySelectorAll('.tp-person-export-menu.show').forEach(function(menu){if(!menu.parentElement.contains(event.target))menu.classList.remove('show')})});
</script>
@endsection
