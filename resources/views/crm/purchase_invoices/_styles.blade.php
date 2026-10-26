{{-- Purchase invoice pages: status pills, small row buttons, form and invoice layout. --}}
<style>
    .pi-btn { display: inline-flex; align-items: center; justify-content: center; padding: .38rem .8rem; border: 1px solid var(--line);
        border-radius: 8px; background: #fff; color: var(--ink); font-size: .78rem; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer; font-family: inherit; }
    .pi-btn:hover { border-color: var(--brand); color: var(--brand); }
    .pi-btn.danger:hover { border-color: var(--red); color: var(--red); }
    .pi-status { display: inline-block; padding: .3rem .7rem; border-radius: 6px; font-size: .72rem; font-weight: 800; white-space: nowrap; color: #fff; }
    .pi-status.overdue { background: #dc2626; }
    .pi-status.paid { background: #16a34a; }
    .pi-status.partial { background: #2563eb; }
    .pi-status.unpaid { background: #d97706; }
    .pi-status.overpaid { background: #94a3b8; }

    .pi-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 1px 2px rgba(15,23,42,.04); margin-bottom: 1rem; }
    .pi-card-head { padding: .95rem 1.2rem; border-bottom: 1px solid var(--line); font-weight: 800; color: var(--ink); font-size: .9rem; display: flex; align-items: center; gap: .5rem; }
    .pi-card-body { padding: 1.1rem 1.2rem; }
    .pi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .85rem 1rem; }
    .pi-grid .full { grid-column: 1 / -1; }
    .pi-items { width: 100%; border-collapse: collapse; }
    .pi-items th { font-size: .63rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: var(--muted); text-align: left;
        padding: .6rem .5rem; background: var(--well); border-bottom: 1px solid var(--line); }
    .pi-items td { padding: .45rem .5rem; border-bottom: 1px solid var(--line-soft); vertical-align: middle; font-size: .86rem; }
    .pi-items .r { text-align: right; }
    .pi-items input { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: .5rem .6rem; font-size: .86rem; font-family: inherit; outline: none; }
    .pi-items input:focus { border-color: var(--brand); }
    .pi-items input.r { text-align: right; }
    .pi-line-total { font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .pi-add-row { margin-top: .7rem; border: 1px dashed var(--brand); color: var(--brand); background: var(--brand-soft); border-radius: 9px;
        padding: .5rem 1rem; font-weight: 800; font-size: .8rem; cursor: pointer; font-family: inherit; }
    .pi-add-bar { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; margin-top: .7rem; }
    .pi-add-bar .pi-add-row { margin-top: 0; }
    .pi-asset-pick { display: inline-flex; align-items: center; gap: .45rem; border: 1px dashed #7c3aed; background: #f5f3ff; color: #6d28d9;
        border-radius: 9px; padding: 0 .3rem 0 .8rem; font-size: .8rem; font-weight: 800; }
    .pi-asset-pick select { border: 0; background: transparent; color: #6d28d9; font: inherit; font-weight: 800; padding: .5rem .3rem; outline: none; cursor: pointer; max-width: 460px; }
    .pi-asset-row td { background: #faf8ff; }
    .pi-asset-name { display: flex; align-items: center; gap: .45rem; }
    .pi-asset-chip { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .5rem; border-radius: 6px; background: #ede9fe;
        color: #6d28d9; font-size: .68rem; font-weight: 800; white-space: nowrap; }
    .pi-stock-pick { border-color: #0d9488; background: #f0fdfa; color: #0f766e; }
    .pi-stock-pick select { color: #0f766e; }
    .pi-stock-row td { background: #f3fbf9; }
    .pi-stock-chip { background: #ccfbf1; color: #0f766e; }
    .pi-stock-note { margin-top: .25rem; font-size: .68rem; font-weight: 700; color: #0f766e; }
    .pi-stock-help { display: flex; gap: .5rem; align-items: flex-start; margin-top: .7rem; padding: .55rem .75rem; border-radius: 9px; background: #f0fdfa;
        border: 1px solid #ccfbf1; color: #115e59; font-size: .74rem; line-height: 1.45; }
    .pi-stock-help i { margin-top: .15rem; }
    .pi-stock-help a { color: inherit; font-weight: 800; }
    .pi-items input[readonly] { background: var(--well); color: var(--body); cursor: default; }
    .pi-total-row { display: flex; justify-content: flex-end; gap: 1.5rem; margin-top: 1rem; font-size: 1rem; font-weight: 800; color: var(--ink); }
    .pi-total-row span:last-child { min-width: 140px; text-align: right; font-variant-numeric: tabular-nums; }
    .pi-actions { display: flex; justify-content: flex-end; gap: .6rem; }
    .pi-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .9rem 1.2rem; }
    .pi-meta .k { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--faint); }
    .pi-meta .v { font-size: .92rem; font-weight: 700; color: var(--ink); margin-top: .2rem; }
    .pi-sum { margin-left: auto; max-width: 320px; }
    .pi-sum div { display: flex; justify-content: space-between; padding: .4rem 0; font-size: .9rem; }
    .pi-sum .big { font-size: 1.05rem; font-weight: 800; color: var(--ink); border-top: 2px solid var(--line); margin-top: .3rem; padding-top: .6rem; }
    .pi-sum .pi-advance-note { margin: .5rem 0 0; font-size: .78rem; line-height: 1.45; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: .5rem .65rem; }
    .pi-attach-img { max-width: 100%; max-height: 420px; border: 1px solid var(--line); border-radius: 10px; display: block; margin-top: .6rem; }
    @media (max-width: 760px) { .pi-grid { grid-template-columns: 1fr; } }
    @media print {
        .sidebar, .top-header, .pi-noprint, header, nav { display: none !important; }
        .pi-card { box-shadow: none; }
    }
</style>
