{{-- Shared ledger look (General Ledger, Receipts). --}}
<style>
    .gl-page { --ink: #0f172a; --body: #334155; --muted: #64748b; --faint: #94a3b8;
        --line: #e2e8f0; --line-soft: #f1f5f9; --well: #f8fafc;
        --brand: var(--primary-purple, var(--primary-purple)); --brand-deep: var(--primary-hover, #5848d8);
        --brand-soft: var(--primary-soft, var(--primary-soft));
        --green: #15803d; --green-soft: #ecfdf3; --red: #b91c1c; --red-soft: #fef2f2; --amber: #b45309; --amber-soft: #fffbeb;
        width: 100%; color: var(--body);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    .gl-page *, .gl-page *::before, .gl-page *::after { box-sizing: border-box; }

    .gl-head { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.2rem; }
    .gl-sub { font-size: .85rem; color: var(--muted); font-weight: 500; }
    .gl-sub strong { color: var(--ink); font-weight: 700; }
    .gl-tabs { display: inline-flex; background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 4px; gap: 4px;
        box-shadow: 0 1px 2px rgba(15,23,42,.05); }
    .gl-tab { display: inline-flex; align-items: center; gap: .5rem; padding: .58rem 1.15rem; border-radius: 9px;
        color: var(--muted); text-decoration: none; font-weight: 700; font-size: .84rem; transition: all .15s; }
    .gl-tab:hover { color: var(--ink); background: var(--well); }
    .gl-tab.active { background: var(--brand); color: #fff; box-shadow: 0 4px 12px -4px var(--primary-shadow, rgba(0,0,0,.25)); }

    .gl-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; margin-bottom: 1.2rem; }
    .gl-stat { position: relative; background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1.15rem 1.25rem 1.2rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 10px 28px -22px rgba(15,23,42,.25); overflow: hidden; }
    .gl-stat-top { display: flex; align-items: center; gap: .7rem; }
    .gl-ico { width: 38px; height: 38px; border-radius: 11px; display: grid; place-items: center; font-size: .95rem; flex: none; }
    .gl-stat .gl-k { font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--faint); }
    .gl-stat .gl-v { font-size: 1.55rem; font-weight: 800; letter-spacing: -.02em; color: var(--ink); margin-top: .55rem;
        font-variant-numeric: tabular-nums; line-height: 1; }
    .gl-stat .gl-v small { font-size: .64rem; color: var(--muted); font-weight: 700; letter-spacing: .05em; }
    .gl-stat .gl-note { font-size: .7rem; color: var(--muted); margin-top: .45rem; }
    .gl-ico.brand { background: var(--brand-soft); color: var(--brand); }
    .gl-ico.green { background: var(--green-soft); color: var(--green); }
    .gl-ico.red { background: var(--red-soft); color: var(--red); }
    .gl-stat.green .gl-v { color: var(--green); }
    .gl-stat.red .gl-v { color: var(--red); }
    /* Clickable stat cards → jump to that tab's records */
    .gl-stat-link, .gl-stat-link *, .gl-stat-link:hover, .gl-stat-link:hover * { text-decoration: none !important; }
    .gl-stat-link { color: inherit; cursor: pointer; transition: transform .12s, box-shadow .12s, border-color .12s; }
    .gl-stat-link:hover { transform: translateY(-2px); box-shadow: 0 10px 24px -8px rgba(15,23,42,.18); border-color: var(--brand); }
    .gl-stat-go { margin-left: auto; color: var(--faint); font-size: .8rem; transition: transform .12s, color .12s; }
    .gl-stat-link:hover .gl-stat-go { color: var(--brand); transform: translateX(3px); }
    .gl-bar { height: 5px; border-radius: 99px; background: var(--line-soft); margin-top: .8rem; overflow: hidden; }
    .gl-bar span { display: block; height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--brand), var(--brand-deep)); }

    /* ── Filter / export bar ─────────────────────────── */
    .gl-filterbar { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap;
        background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: .9rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .gl-filters { display: flex; gap: .7rem; flex-wrap: wrap; align-items: flex-end; }
    .gl-field label { display: block; font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
        color: var(--faint); margin-bottom: .3rem; }
    .gl-field input, .gl-field select { border: 1px solid var(--line); border-radius: 9px; padding: .5rem .65rem;
        font-size: .82rem; font-family: inherit; outline: none; background: #fff; color: var(--ink); transition: all .12s; }
    .gl-field input:focus, .gl-field select:focus { border-color: var(--brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 10%, transparent); }
    .gl-field input[type="date"] { width: 140px; }
    .gl-field input[name="search"] { width: 190px; }
    .gl-apply { border: none; background: var(--brand); color: #fff; border-radius: 9px; padding: .55rem 1.1rem;
        font-size: .8rem; font-weight: 700; font-family: inherit; cursor: pointer; transition: opacity .14s; box-shadow: 0 6px 14px var(--primary-shadow); }
    .gl-apply:hover { opacity: .9; }
    .gl-reset { font-size: .76rem; color: var(--muted); text-decoration: none; font-weight: 600; padding: .55rem .4rem; }
    .gl-reset:hover { color: var(--ink); }
    .gl-exports { display: flex; gap: .55rem; }
    .gl-export { display: inline-flex; align-items: center; gap: .45rem; border: 1px solid var(--line); background: #fff;
        color: var(--body); border-radius: 9px; padding: .55rem 1rem; font-size: .8rem; font-weight: 700;
        text-decoration: none; transition: all .14s; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .gl-export:hover { border-color: var(--brand); color: var(--brand); }
    .gl-export i { color: var(--brand); }

    .gl-count { font-size: .78rem; color: var(--muted); font-weight: 600; margin-bottom: .7rem; }
    .gl-count b { color: var(--ink); }
    .gl-card { background: #fff; border: 1px solid var(--line); border-radius: 16px; overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 12px 32px -24px rgba(15,23,42,.3); }
    .gl-tablewrap { overflow-x: auto; }
    .gl-table { width: 100%; border-collapse: collapse; min-width: 920px; }
    .gl-table thead th { font-size: .63rem; text-transform: uppercase; letter-spacing: .09em; font-weight: 800;
        color: var(--muted); text-align: left; padding: .8rem 1.15rem; background: var(--well); border-bottom: 1px solid var(--line); }
    .gl-table thead th.gl-r, .gl-table td.gl-r { text-align: right; }
    .gl-table tbody td { padding: .75rem 1.15rem; border-bottom: 1px solid var(--line-soft); font-size: .87rem; vertical-align: middle; }
    .gl-table tbody tr:last-child td { border-bottom: none; }
    .gl-table tbody tr { transition: background .12s; }
    .gl-table tbody tr:hover { background: color-mix(in srgb, var(--brand) 3%, white); }
    .gl-table td.gl-date { white-space: nowrap; color: var(--muted); font-size: .8rem; }
    .gl-party { display: flex; align-items: center; gap: .6rem; min-width: 0; }
    .gl-avatar { display: inline-grid; place-items: center; width: 32px; height: 32px; border-radius: 9px; flex: none;
        background: var(--brand-soft); color: var(--brand); font-size: .74rem; font-weight: 800; }
    .gl-name { font-weight: 700; color: var(--ink); line-height: 1.25; }
    .gl-num { font-variant-numeric: tabular-nums; font-weight: 700; color: var(--ink); white-space: nowrap; }
    .gl-num.gl-green { color: var(--green); }
    .gl-num.gl-red { color: var(--red); }
    .gl-num small { font-size: .64rem; color: var(--faint); font-weight: 600; }
    .gl-badge { display: inline-flex; align-items: center; gap: .38rem; font-size: .66rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: .05em; padding: .34rem .7rem; border-radius: 999px; white-space: nowrap; }
    .gl-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .gl-badge.ok { color: var(--green); background: var(--green-soft); }
    .gl-badge.due { color: var(--amber); background: var(--amber-soft); }
    .gl-badge.part { color: #1e40af; background: #eff6ff; }
    .gl-badge.t-pay { color: #7c2d12; background: #fff7ed; }
    .gl-badge.t-rec { color: #14532d; background: #f0fdf4; }
    .gl-mutedtext { color: var(--muted); font-size: .76rem; }
    .gl-empty { text-align: center; padding: 3.5rem 1rem; color: var(--muted); }
    .gl-empty i { font-size: 1.9rem; color: var(--faint); display: block; margin-bottom: .7rem; }
    .gl-pagination { padding: 1rem; display: flex; justify-content: center; border-top: 1px solid var(--line-soft); }
    .gl-pagination nav { display: flex; }
    .gl-pagination ul, .gl-pagination .pagination { display: flex; list-style: none; gap: .3rem; padding: 0; margin: 0; align-items: center; flex-wrap: wrap; }
    .gl-pagination li > *, .gl-pagination a, .gl-pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 .5rem; border: 1px solid var(--line); border-radius: 8px; text-decoration: none; color: var(--body); font-size: .82rem; font-weight: 600; background: #fff; }
    .gl-pagination a:hover { border-color: var(--brand); color: var(--brand); }
    .gl-pagination .active > *, .gl-pagination [aria-current] > * { background: var(--brand); border-color: var(--brand); color: #fff; }
    .gl-pagination .disabled > * { opacity: .45; }

    /* ── Tablet ──────────────────────────────────────── */
    @media (max-width: 900px) {
        .gl-head { flex-direction: column; align-items: stretch; }
        .gl-tabs { width: 100%; justify-content: space-between; }
        .gl-tab { flex: 1; justify-content: center; padding: .55rem .5rem; font-size: .78rem; }
        .gl-filterbar { flex-direction: column; align-items: stretch; }
        .gl-filters { width: 100%; }
        .gl-exports { justify-content: flex-end; }
    }

    /* ── Mobile: table becomes stacked cards ─────────── */
    @media (max-width: 640px) {
        .gl-stats { grid-template-columns: 1fr; }
        .gl-tab span, .gl-tab { font-size: 0; gap: 0; }
        .gl-tab i { font-size: .95rem; }
        .gl-tab.active { font-size: .78rem; }
        .gl-tab.active i { margin-right: .4rem; }
        .gl-field input[name="search"], .gl-field input[type="date"] { width: 100%; }
        .gl-field { flex: 1 1 45%; }
        .gl-field:last-of-type { flex-basis: 100%; }

        .gl-tablewrap { overflow-x: visible; }
        .gl-table, .gl-table tbody, .gl-table tr, .gl-table td { display: block; width: 100%; min-width: 0; }
        .gl-table thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
        .gl-table tr { border: 1px solid var(--line); border-radius: 12px; margin: .7rem; padding: .35rem .2rem;
            box-shadow: 0 1px 3px rgba(15,23,42,.05); }
        .gl-table tr:hover { background: #fff; }
        .gl-table td { display: flex; justify-content: space-between; align-items: center; gap: 1rem;
            padding: .55rem .9rem; border-bottom: 1px solid var(--line-soft); text-align: right; }
        .gl-table tr td:last-child { border-bottom: none; }
        .gl-table td::before { content: attr(data-label); font-size: .64rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: .07em; color: var(--faint); text-align: left; flex: none; }
        .gl-table td.gl-r { text-align: right; }
        .gl-party { justify-content: flex-end; }
        .gl-empty { display: block !important; }
    }
</style>
