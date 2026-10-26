{{-- Shared look for list actions + add/edit dialogs (Make Payment, Fixed Assets, Employees, Purchase Invoices...). --}}
<style>
    .ex-add { display: inline-flex; align-items: center; gap: .5rem; border: none; background: var(--brand); color: #fff; border-radius: 10px;
        padding: .7rem 1.2rem; font-size: .86rem; font-weight: 800; font-family: inherit; cursor: pointer; box-shadow: 0 6px 14px var(--primary-shadow); }
    .ex-add:hover { opacity: .92; }
    .ex-name { font-weight: 700; color: var(--ink); line-height: 1.25; }
    .ex-sub { display: block; font-size: .68rem; color: var(--faint); margin-top: .15rem; }
    .ex-proof { font-size: .72rem; font-weight: 700; color: var(--brand); text-decoration: none; white-space: nowrap; }
    .ex-actions { display: flex; gap: .35rem; justify-content: flex-end; }
    .ex-icon { width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--line); background: #fff; color: var(--muted);
        display: inline-grid; place-items: center; cursor: pointer; font-size: .75rem; }
    .ex-icon:hover { border-color: var(--brand); color: var(--brand); }
    .ex-icon.danger:hover { border-color: var(--red); color: var(--red); }

    .ex-modal { position: fixed; inset: 0; background: rgba(15,23,42,.45); display: none; align-items: flex-start; justify-content: center;
        z-index: 2000; padding: 4vh 16px; overflow-y: auto; }
    .ex-modal.open { display: flex; }
    .ex-dialog { background: #fff; border-radius: 18px; width: 100%; max-width: 640px; box-shadow: 0 30px 60px -20px rgba(15,23,42,.45); }
    .ex-dhead { display: flex; align-items: center; gap: .8rem; padding: 1.1rem 1.3rem; border-bottom: 1px solid var(--line); }
    .ex-dhead h3 { margin: 0; font-size: 1.05rem; color: var(--ink); font-weight: 800; }
    .ex-dhead p { margin: .1rem 0 0; font-size: .74rem; color: var(--muted); }
    .ex-close { margin-left: auto; border: 1px solid var(--line); background: #fff; border-radius: 9px; width: 34px; height: 34px; cursor: pointer; color: var(--muted); }
    .ex-dbody { padding: 1.1rem 1.3rem; display: grid; grid-template-columns: 1fr 1fr; gap: .85rem 1rem; }
    .ex-dbody .full { grid-column: 1 / -1; }
    .ex-f label { display: block; font-size: .7rem; font-weight: 800; color: var(--body); margin-bottom: .3rem; }
    .ex-f label .req { color: var(--red); }
    .ex-f label .hint { font-weight: 600; color: var(--faint); }
    .ex-f input, .ex-f select, .ex-f textarea { width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: .58rem .7rem;
        font-size: .86rem; font-family: inherit; color: var(--ink); background: #fff; outline: none; }
    .ex-f input:focus, .ex-f select:focus, .ex-f textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 12%, transparent); }
    .ex-amount { display: grid; grid-template-columns: 1fr 90px; gap: .5rem; }
    .ex-seg { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; }
    .ex-seg label { margin: 0; }
    .ex-seg input { display: none; }
    .ex-seg span { display: flex; align-items: center; justify-content: center; gap: .4rem; padding: .58rem .4rem; border: 1px solid var(--line);
        border-radius: 9px; font-size: .8rem; font-weight: 700; color: var(--muted); cursor: pointer; }
    .ex-seg input:checked + span { border-color: var(--brand); background: var(--brand-soft); color: var(--brand); }
    .ex-errors { grid-column: 1 / -1; background: var(--red-soft); border: 1px solid #fecaca; color: #7f1d1d; border-radius: 10px; padding: .7rem .9rem; font-size: .8rem; }
    .ex-errors ul { margin: .3rem 0 0; padding-left: 1.1rem; }
    .ex-dfoot { display: flex; justify-content: flex-end; gap: .6rem; padding: 1rem 1.3rem; border-top: 1px solid var(--line); background: var(--well); border-radius: 0 0 18px 18px; }
    .ex-cancel { border: 1px solid var(--line); background: #fff; border-radius: 9px; padding: .6rem 1.1rem; font-weight: 700; font-family: inherit; cursor: pointer; color: var(--body); }
    .ex-current-proof { font-size: .72rem; color: var(--muted); margin-top: .3rem; }
    @media (max-width: 640px) { .ex-dbody { grid-template-columns: 1fr; } }
</style>
