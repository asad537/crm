@extends('crm.layout')

@section('title', 'Leads Managements')

@section('header_actions')
<button type="submit" name="export" value="true" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;" form="leadsFilterForm">
    <i class="fas fa-file-excel" style="margin-right: 8px;"></i> Export
</button>
@endsection

@section('content')
<style>
    /* Consistent Theme Styles */
    .leads-card {
        background: var(--card-bg);
        border-radius: var(--border-radius-base);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: none;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    .leads-filter {
        background: var(--card-bg);
        border-radius: var(--border-radius-base);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: none;
    }
    
    
    .table-responsive { overflow-x: auto; }
    .table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.9rem; }
    .table th {
        background: #f8fafc;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #64748b;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }
    .table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    .table tr:hover td { background: #f8fafc; }
    .table tr:last-child td { border-bottom: none; }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .status-new { background: #eff6ff; color: #3b82f6; } /* Blue */
    .status-viewed { background: #f1f5f9; color: #475569; } /* Gray */
    .status-responded { background: #f3e8ff; color: #9333ea; } /* Purple match */
    .status-order { background: #dcfce7; color: #16a34a; } /* Green match */
    .status-closed { background: #fee2e2; color: #ef4444; } /* Red match */

    .btn {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .btn-primary { background: var(--primary-purple); color: white; border-color: var(--primary-purple); }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-light { background: white; border: 1px solid #cbd5e1; color: #475569; }
    .btn-light:hover { background: #f1f5f9; }
    
    input, select {
        font-size: 0.9rem !important; 
        padding: 0.6rem !important; 
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        outline: none;
    }
    input:focus, select:focus { border-color: var(--primary-purple) !important; ring: 2px solid var(--primary-purple); }
</style>

<!-- Filters -->
<!-- Filters -->
<div class="leads-filter" style="position: relative;">
    <form id="leadsFilterForm" action="{{ route('crm.leads.index') }}" method="GET" style="display: flex; gap: 1rem; align-items: flex-end; width: 100%;">
        
        <div style="flex: 1.5; min-width: 0;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Search</label>
           <div style="position: relative;">
                <i class="fas fa-search"
                   style="
                    position: absolute;
                    left: 1rem;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #94a3b8;
                    pointer-events: none;
                   ">
                </i>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by Name, Email or Phone"
                    oninput="debounceSearch(this)"
                    style="
                    width: 100%;
                     padding: 0.75rem 2.2rem !important;
                    border: 1px solid #cbd5e1;
                    border-radius: 10px;
                    font-size: 0.9rem;
                    outline: none;
                    transition: all 0.2s;
                    "
                >
            </div>
        </div>

        <div style="flex: 1; min-width: 0;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Product</label>
            <div style="position: relative;">
                <select name="product" onchange="updateLeads()" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 0.9rem; outline: none; appearance: none; background: white;">
                    <option value="">All Products</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod }}" {{ request('product') == $prod ? 'selected' : '' }}>{{ Str::limit($prod, 25) }}</option>
                    @endforeach
                </select>
                <i class="fas fa-chevron-down" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 0.8rem;"></i>
            </div>
        </div>



        <div style="flex: 1.2; min-width: 0;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Date Range</label>
            <div style="position: relative;">
                <input type="text" id="daterange" placeholder="Select Date Range"
                       value="{{ request('start_date') && request('end_date') ? request('start_date') . ' - ' . request('end_date') : '' }}"
                       style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 0.9rem; outline: none; background: white; cursor: pointer;">
                <i class="far fa-calendar-alt" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;"></i>
            </div>
            <input type="hidden" name="start_date" id="start_date" value="{{ request('start_date') }}">
            <input type="hidden" name="end_date" id="end_date" value="{{ request('end_date') }}">
        </div>

        <a href="{{ route('crm.leads.index') }}" class="btn btn-light" style="padding: 0.75rem 1rem; font-size: 0.9rem; white-space: nowrap; height: 42px;">
            <i class="fas fa-undo" style="margin-right: 5px;"></i> Reset
        </a>

    </form>

</div>

<!-- Table -->
<div class="leads-card">
    @include('crm.leads.table')
</div>
@endsection

@section('scripts')
<script>
    let timeout = null;

    function updateLeads() {
        const form = document.getElementById('leadsFilterForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        for(const pair of formData.entries()) {
            if (pair[1]) params.append(pair[0], pair[1]);
        }
        
        const card = document.querySelector('.leads-card');
        if (card) {
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';
        }

        const url = form.getAttribute('action') || window.location.pathname;
        const queryString = params.toString();
        const fetchUrl = queryString ? `${url}?${queryString}` : url;

        fetch(fetchUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            if (card) {
                card.innerHTML = html;
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
            window.history.pushState({}, '', fetchUrl);
        })
        .catch(err => {
            console.error('Filter failed:', err);
            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
        });
    }

    function debounceSearch(input) {
        clearTimeout(timeout);
        const delay = input.value === '' ? 50 : 300;
        timeout = setTimeout(updateLeads, delay);
    }

    $(document).ready(function() {
        $('#daterange').daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD',
                applyLabel: 'Filter'
            }
        });

        $('#daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
            $('input[name="start_date"]').val(picker.startDate.format('YYYY-MM-DD'));
            $('input[name="end_date"]').val(picker.endDate.format('YYYY-MM-DD'));
            updateLeads();
        });

        $('#daterange').on('cancel.daterangepicker', function() {
            $(this).val('');
            $('input[name="start_date"]').val('');
            $('input[name="end_date"]').val('');
            updateLeads();
        });
    });
</script>
@endsection
