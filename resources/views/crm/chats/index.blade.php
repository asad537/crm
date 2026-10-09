@extends('crm.layout')
@section('title', 'Mail')

@section('styles')
    /* ===== Outlook-style 3-pane mail client (converted Live Chat page) ===== */
    .main-area { padding: 0 !important; overflow: hidden !important; background: #fff; display: flex; flex-direction: column; }
    .top-bar { display: none !important; }

    .mail-app { display: grid; grid-template-columns: 250px 380px minmax(0, 1fr); height: 100vh; width: 100%; background: #fff; }

    /* ---- Left: accounts + folders ---- */
    .mail-nav { display: flex; flex-direction: column; min-width: 0; min-height: 0; overflow: hidden; border-right: 1px solid #eef1f6; background: #fafbfd; }
    .mail-list, .chat-main { min-height: 0; }
    #mailAccountsList { max-height: 38vh; overflow-y: auto; padding-right: 2px; }
    .mail-nav-head { display: flex; align-items: center; gap: 10px; padding: 1.1rem 1rem .8rem; }
    .mail-nav-head h2 { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }
    .mail-nav-head h2 i { color: var(--primary-purple); }
    .mail-nav-scroll { flex: 1; min-height: 0; overflow-y: auto; padding: 0 .6rem 1rem; }
    .mail-section-title { display: flex; align-items: center; justify-content: space-between; padding: .9rem .5rem .35rem; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #8a94a6; }
    .mail-section-title button { border: 0; background: transparent; color: var(--primary-purple); font: inherit; font-size: .7rem; font-weight: 800; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
    .mail-section-title button:hover { background: var(--primary-soft); }
    .mail-nav-item { display: flex; align-items: center; gap: 10px; padding: .55rem .6rem; border-radius: 9px; cursor: pointer; color: #334155; font-size: .84rem; font-weight: 600; user-select: none; position: relative; }
    .mail-nav-item:hover { background: #eef1f7; }
    .mail-nav-item.active { background: var(--primary-soft); color: var(--primary-purple); }
    .mail-nav-item i.fa-fw { width: 18px; text-align: center; color: #94a3b8; font-size: .85rem; }
    .mail-nav-item.active i.fa-fw { color: var(--primary-purple); }
    .mail-nav-item .label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mail-nav-item .count { font-size: .68rem; font-weight: 800; color: #64748b; background: #e9edf4; border-radius: 99px; padding: 1px 7px; }
    .mail-nav-item.active .count { background: #fff; color: var(--primary-purple); }
    .mail-nav-item.disabled { opacity: .45; cursor: not-allowed; }
    .mail-acc-dot { width: 10px; height: 10px; border-radius: 50%; flex: 0 0 10px; }
    .mail-acc-sub { display: block; font-size: .68rem; color: #94a3b8; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mail-acc-menu { display: none; position: absolute; right: 6px; top: 50%; transform: translateY(-50%); gap: 2px; }
    .mail-nav-item:hover .mail-acc-menu { display: flex; }
    .mail-acc-menu button { width: 24px; height: 24px; border: 0; border-radius: 6px; background: #fff; color: #64748b; cursor: pointer; font-size: .7rem; box-shadow: 0 1px 2px rgba(15,23,42,.1); }
    .mail-acc-menu button:hover { color: var(--primary-purple); }
    .mail-acc-menu button.danger:hover { color: #dc2626; }
    .mail-acc-err { position: absolute; right: 8px; top: 8px; color: #dc2626; font-size: .7rem; }
    .mail-add-btn { width: 100%; margin-top: .35rem; display: flex; align-items: center; justify-content: center; gap: 8px; padding: .6rem; border: 1.5px dashed #cbd5e1; border-radius: 10px; background: #fff; color: #475569; font: inherit; font-size: .8rem; font-weight: 700; cursor: pointer; }
    .mail-add-btn:hover { border-color: var(--primary-purple); color: var(--primary-purple); }
    .mail-nav-foot { padding: .6rem .8rem; border-top: 1px solid #eef1f6; display: flex; align-items: center; justify-content: space-between; font-size: .72rem; color: #94a3b8; }
    .mail-nav-foot button { border: 0; background: transparent; color: var(--primary-purple); font: inherit; font-size: .74rem; font-weight: 800; cursor: pointer; }

    /* ---- Middle: conversation list ---- */
    .mail-list { display: flex; flex-direction: column; min-width: 0; border-right: 1px solid #eef1f6; background: #fff; }
    .mail-list-head { padding: 1rem 1rem .7rem; border-bottom: 1px solid #f1f5f9; }
    .mail-list-title { display: flex; align-items: center; justify-content: space-between; margin-bottom: .7rem; }
    .mail-list-title h3 { margin: 0; font-size: 1rem; font-weight: 800; color: var(--text-dark); }
    .mail-list-title small { display: block; font-size: .7rem; color: #94a3b8; font-weight: 500; }
    .mail-list-title .icon-btn { width: 32px; height: 32px; border: 1px solid #e5e9f0; border-radius: 8px; background: #fff; color: #64748b; cursor: pointer; }
    .mail-list-title .icon-btn:hover { color: var(--primary-purple); border-color: var(--primary-purple); }
    .search-chat { background: #f1f5f9; border-radius: 10px; padding: .55rem .9rem; display: flex; align-items: center; gap: .5rem; }
    .search-chat input { border: none; background: none; outline: none; width: 100%; font-size: .86rem; }
    .chat-list-items { flex: 1; overflow-y: auto; }
    .chat-item { padding: .85rem 1rem; border-bottom: 1px solid #f3f5f9; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; transition: background .15s; position: relative; }
    .chat-item:hover { background: #f8fafc; }
    .chat-item.active { background: var(--primary-soft); box-shadow: inset 3px 0 0 var(--primary-purple); }
    .chat-item.unread .chat-name, .chat-item.unread .chat-subject { font-weight: 800; color: #0f172a; }
    .chat-item.unread::before { content: ""; position: absolute; left: 6px; top: 1.25rem; width: 7px; height: 7px; border-radius: 50%; background: var(--primary-purple); }
    .chat-avatar { width: 40px; height: 40px; border-radius: 11px; background: #ecf0ff; color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0; font-size: .9rem; }
    .chat-info { flex: 1; min-width: 0; }
    .chat-row1 { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
    .chat-name { font-weight: 600; color: #1e293b; font-size: .88rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .chat-time { font-size: .68rem; color: #94a3b8; white-space: nowrap; }
    .chat-subject { font-size: .78rem; color: #334155; margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .chat-snippet { font-size: .74rem; color: #8a94a6; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: flex; align-items: center; gap: 6px; }
    .chat-acc-chip { display: inline-flex; align-items: center; gap: 4px; max-width: 140px; font-size: .62rem; font-weight: 700; color: #64748b; background: #f1f5f9; border-radius: 99px; padding: 1px 7px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .chat-badge { background: var(--primary-purple); color: #fff; border-radius: 99px; font-size: .64rem; padding: 1px 7px; font-weight: 800; margin-left: 6px; }
    .chat-list-empty { padding: 2.5rem 1.5rem; text-align: center; color: #94a3b8; font-size: .85rem; }
    .chat-list-empty i { display: block; font-size: 2rem; opacity: .35; margin-bottom: .6rem; }

    /* ---- Right: reading pane ---- */
    .chat-main { display: flex; flex-direction: column; min-width: 0; background: #f8fafc; position: relative; }
    .chat-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8; padding: 2rem; text-align: center; }
    .chat-empty i { font-size: 4rem; opacity: .25; margin-bottom: 1.2rem; }
    .chat-empty h3 { margin: 0 0 .4rem; color: #64748b; }
    .chat-header { padding: .8rem 1.4rem; background: #fff; border-bottom: 1px solid #eef1f6; display: flex; align-items: center; justify-content: space-between; gap: 1rem; z-index: 10; }
    .chat-header .who { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .chat-header .who .chat-avatar { width: 42px; height: 42px; }
    .chat-header .meta { min-width: 0; }
    .chat-header .meta .chat-name { font-size: .95rem; font-weight: 800; }
    .chat-header .meta .sub { font-size: .74rem; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .reader-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .reader-btn { display: inline-flex; align-items: center; gap: 6px; padding: .5rem .8rem; border: 1px solid #e5e9f0; border-radius: 9px; background: #fff; color: #475569; font: inherit; font-size: .78rem; font-weight: 700; cursor: pointer; text-decoration: none; white-space: nowrap; }
    .reader-btn:hover { border-color: var(--primary-purple); color: var(--primary-purple); }
    .reader-btn.primary { background: var(--primary-purple); border-color: var(--primary-purple); color: #fff; }
    .reader-btn.primary:hover { background: var(--primary-hover); color: #fff; }
    #mobileBackBtn { display: none; background: none; border: none; color: #64748b; font-size: 1.1rem; cursor: pointer; padding: 0 6px 0 0; }
    .chat-messages-container { flex: 1; padding: 1.5rem 1.8rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1.1rem; scroll-behavior: smooth; }
    .msg-row { display: flex; flex-direction: column; width: 100%; }
    .msg-bubble { max-width: 78%; padding: .85rem 1.1rem; border-radius: 14px; font-size: .92rem; line-height: 1.55; position: relative; overflow-wrap: anywhere; }
    .msg-bubble > *:first-child { margin-top: 0; } .msg-bubble > *:last-child { margin-bottom: 0; } .msg-bubble p { margin: 0; }
    .msg-bubble img { max-width: 100%; height: auto; }
    .msg-admin { align-self: flex-end; background: linear-gradient(135deg, color-mix(in srgb, var(--primary-purple) 16%, #fff), color-mix(in srgb, var(--primary-purple) 30%, #fff)); color: #273449; border: 1px solid color-mix(in srgb, var(--primary-purple) 22%, #fff); border-bottom-right-radius: 4px; }
    .msg-client { align-self: flex-start; background: #fff; color: #1e293b; border: 1px solid #eef1f6; border-bottom-left-radius: 4px; box-shadow: 0 2px 6px rgba(15,23,42,.04); }
    .msg-time { font-size: .68rem; margin-top: 4px; color: #94a3b8; }

    /* composer */
    .chat-input-area { padding: .9rem 1.4rem 1rem; background: #fff; border-top: 1px solid #eef1f6; position: relative; }
    .composer-box { border: 1.5px solid #e2e8f0; border-radius: 14px; background: #fff; overflow: hidden; }
    .composer-box:focus-within { border-color: var(--primary-purple); box-shadow: 0 0 0 3px var(--primary-shadow); }
    .composer-box textarea { width: 100%; min-height: 110px; border: 0; outline: 0; resize: vertical; padding: .9rem 1rem; font: inherit; font-size: .92rem; color: #1e293b; box-sizing: border-box; }
    .composer-box .tox-tinymce { border: 0 !important; border-radius: 0 !important; }
    .composer-bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: .55rem .75rem; border-top: 1px solid #f1f5f9; background: #fbfcfe; }
    .composer-bar .left { display: flex; align-items: center; gap: 8px; min-width: 0; }
    .chat-attach-button { display: inline-flex; align-items: center; gap: 6px; padding: .45rem .7rem; border-radius: 8px; color: #475569; font-size: .78rem; font-weight: 700; cursor: pointer; }
    .chat-attach-button:hover { background: #eef1f7; color: var(--primary-purple); }
    .composer-to { font-size: .72rem; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .composer-to strong { color: #475569; }
    .send-btn { display: inline-flex; align-items: center; gap: 8px; padding: .55rem 1.1rem; border: 0; border-radius: 9px; background: var(--primary-purple); color: #fff; font: inherit; font-size: .82rem; font-weight: 800; cursor: pointer; box-shadow: 0 6px 14px var(--primary-shadow); }
    .send-btn:hover { background: var(--primary-hover); }
    .send-btn:disabled { opacity: .6; cursor: default; }
    #attachment-tray { display: none; gap: 8px; flex-wrap: wrap; padding: .6rem .75rem 0; }
    .attachment-chip { display: flex; align-items: center; gap: 9px; max-width: 260px; padding: 7px 8px 7px 10px; background: #fff; border: 1px solid #dbe3ef; border-radius: 10px; box-shadow: 0 2px 5px rgba(15,23,42,.06); }
    .attachment-chip-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .78rem; font-weight: 700; color: #334155; }
    .attachment-chip-open { display: flex; min-width: 0; flex: 1; align-items: center; gap: 9px; color: inherit; text-decoration: none; cursor: pointer; }
    .attachment-chip-open:hover .attachment-chip-name { color: var(--primary-purple); text-decoration: underline; }
    .attachment-chip-preview { width: 32px; height: 32px; flex: 0 0 32px; object-fit: cover; border-radius: 7px; border: 1px solid #e2e8f0; }
    .attachment-chip-remove { flex: 0 0 24px; width: 24px; height: 24px; padding: 0; border: none; border-radius: 7px; background: #fef2f2; color: #dc2626; cursor: pointer; }
    .reply-drop-overlay { position: absolute; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px; border: 2px dashed var(--primary-purple); border-radius: 12px; background: rgba(238,242,255,.96); color: var(--primary-purple); font-weight: 800; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity .15s ease; }
    .reply-drop-overlay.active { opacity: 1; visibility: visible; pointer-events: auto; }
    .admin-readonly { text-align: center; color: #94a3b8; padding: .8rem; font-size: .82rem; }

    /* ---- Modals (email meta + account) ---- */
    .mm-backdrop { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.55); z-index: 99999; align-items: center; justify-content: center; padding: 20px; }
    .mm-backdrop.open { display: flex; }
    .mm-dialog { width: 100%; max-width: 560px; background: #fff; border-radius: 18px; box-shadow: 0 24px 60px rgba(15,23,42,.25); overflow: hidden; max-height: 92vh; display: flex; flex-direction: column; }
    .mm-dialog.wide { max-width: 640px; }
    .mm-head { padding: 16px 22px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
    .mm-head h4 { margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; }
    .mm-head button { border: none; background: none; font-size: 1.1rem; color: #94a3b8; cursor: pointer; }
    .mm-body { padding: 18px 22px; overflow-y: auto; }
    .mm-foot { padding: 14px 22px 18px; display: flex; gap: 10px; justify-content: flex-end; align-items: center; border-top: 1px solid #e2e8f0; background: #f8fafc; }
    .mm-grid { display: grid; gap: 12px; }
    .mm-grid.two { grid-template-columns: 1fr 1fr; }
    .mm-field label { display: block; font-size: .78rem; font-weight: 700; color: #475569; margin-bottom: 5px; }
    .mm-field input, .mm-field select, .mm-field textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 9px 12px; font: inherit; font-size: .88rem; outline: none; box-sizing: border-box; background: #fff; }
    .mm-field input:focus, .mm-field select:focus { border-color: var(--primary-purple); box-shadow: 0 0 0 3px var(--primary-shadow); }
    .mm-hint { font-size: .72rem; color: #94a3b8; margin-top: 4px; }
    .mm-btn { border: 1px solid #cbd5e1; background: #fff; color: #475569; border-radius: 10px; padding: 9px 15px; font: inherit; font-weight: 700; cursor: pointer; }
    .mm-btn.primary { border-color: var(--primary-purple); background: var(--primary-purple); color: #fff; font-weight: 800; }
    .mm-btn:disabled { opacity: .6; cursor: default; }
    .mm-status { flex: 1; font-size: .76rem; font-weight: 700; }
    .mm-status.ok { color: #047857; } .mm-status.err { color: #b91c1c; }
    .mm-toggle { display: flex; align-items: center; gap: 8px; font-size: .8rem; color: #475569; font-weight: 600; cursor: pointer; }
    .mm-adv { font-size: .76rem; color: var(--primary-purple); font-weight: 800; cursor: pointer; border: 0; background: none; padding: 0; }
    .mm-test { font-size: .75rem; margin-top: 4px; }
    .mm-test .ok { color: #047857; } .mm-test .err { color: #b91c1c; }


    /* ---- mail message rows / reader ---- */
    .chat-item .row-icons { display: inline-flex; align-items: center; gap: 6px; margin-left: 6px; color: #94a3b8; font-size: .7rem; }
    .chat-item .star-btn { border: 0; background: none; padding: 0 2px; cursor: pointer; color: #cbd5e1; font-size: .78rem; line-height: 1; }
    .chat-item .star-btn.on, .mail-reader-head .star-btn.on { color: #f59e0b; }
    .mail-reader { flex: 1; overflow-y: auto; padding: 1.2rem 1.6rem 1.4rem; display: none; flex-direction: column; gap: .9rem; }
    .mail-reader-head { background: #fff; border: 1px solid #eef1f6; border-radius: 14px; padding: 1rem 1.2rem; }
    .mail-reader-head h2 { margin: 0 0 .6rem; font-size: 1.05rem; font-weight: 800; color: var(--text-dark); display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .mail-reader-head .star-btn { border: 0; background: none; cursor: pointer; color: #cbd5e1; font-size: 1rem; }
    .mail-meta-row { display: flex; align-items: flex-start; gap: 10px; font-size: .8rem; color: #475569; }
    .mail-meta-row .chat-avatar { width: 38px; height: 38px; font-size: .8rem; }
    .mail-meta-row .lines { flex: 1; min-width: 0; }
    .mail-meta-row .lines b { color: #0f172a; }
    .mail-meta-row .lines .addr { color: #64748b; }
    .mail-meta-row .lines small { display: block; color: #8a94a6; font-size: .72rem; margin-top: 2px; overflow-wrap: anywhere; }
    .mail-meta-row .when { font-size: .72rem; color: #94a3b8; white-space: nowrap; }
    .mail-body-card { background: #fff; border: 1px solid #eef1f6; border-radius: 14px; overflow: hidden; }
    .mail-body-card iframe { width: 100%; border: 0; display: block; min-height: 160px; background: #fff; }
    .mail-body-card pre { margin: 0; padding: 1rem 1.2rem; white-space: pre-wrap; font: inherit; font-size: .9rem; color: #1e293b; }
    .mail-atts { display: flex; flex-wrap: wrap; gap: 8px; padding: .8rem 1.2rem; border-top: 1px solid #f1f5f9; background: #fbfcfe; }
    .mail-att { display: inline-flex; align-items: center; gap: 8px; padding: .45rem .7rem; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #334155; text-decoration: none; font-size: .78rem; font-weight: 700; max-width: 260px; }
    .mail-att i { color: var(--primary-purple); }
    .mail-att span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mail-att small { color: #94a3b8; font-weight: 500; }
    .thread-strip { background: #fff; border: 1px solid #eef1f6; border-radius: 14px; padding: .6rem .8rem; }
    .thread-strip h5 { margin: 0 0 .4rem .3rem; font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; color: #8a94a6; }
    .thread-item { display: flex; align-items: center; gap: 10px; padding: .5rem .6rem; border-radius: 9px; cursor: pointer; font-size: .78rem; color: #334155; }
    .thread-item:hover { background: #f5f7fb; }
    .thread-item .who { font-weight: 700; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 180px; }
    .thread-item .snip { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #8a94a6; }
    .thread-item .when { font-size: .68rem; color: #94a3b8; white-space: nowrap; }
    .mail-reply-note { background: #fff; border: 1px dashed #d8deea; border-radius: 12px; padding: .7rem 1rem; font-size: .8rem; color: #64748b; display: flex; align-items: center; gap: 8px; }
    .mail-reply-note a { color: var(--primary-purple); font-weight: 700; text-decoration: none; }
    .list-loadmore { display: block; width: calc(100% - 2rem); margin: .6rem 1rem 1rem; padding: .55rem; border: 1px solid #e5e9f0; border-radius: 9px; background: #fff; color: var(--primary-purple); font: inherit; font-size: .78rem; font-weight: 800; cursor: pointer; }

    /* ---- compose fields (mail reply / new message) ---- */
    .compose-fields { display: none; border-bottom: 1px solid #f1f5f9; background: #fbfcfe; padding: .35rem .75rem; }
    .compose-fields .cf-row { display: flex; align-items: center; gap: 8px; padding: .3rem 0; border-bottom: 1px dashed #eef1f6; }
    .compose-fields .cf-row:last-child { border-bottom: 0; }
    .compose-fields label { flex: 0 0 56px; font-size: .72rem; font-weight: 800; color: #8a94a6; text-transform: uppercase; letter-spacing: .04em; }
    .compose-fields input, .compose-fields select { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font: inherit; font-size: .86rem; color: #1e293b; padding: .15rem 0; }
    .compose-fields .cf-link { border: 0; background: none; color: var(--primary-purple); font: inherit; font-size: .72rem; font-weight: 800; cursor: pointer; padding: 0 4px; }
    .compose-fields .cf-mode { font-size: .72rem; color: #64748b; font-weight: 700; }
    .nav-compose-btn { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; padding: .45rem .7rem; border: 0; border-radius: 9px; background: var(--primary-purple); color: #fff; font: inherit; font-size: .76rem; font-weight: 800; cursor: pointer; box-shadow: 0 6px 14px var(--primary-shadow); }
    .nav-compose-btn:hover { background: var(--primary-hover); }

    /* ---- Responsive ---- */
    @media (max-width: 1200px) { .mail-app { grid-template-columns: 220px 330px minmax(0,1fr); } }
    @media (max-width: 1024px) {
        .mail-app { grid-template-columns: 320px minmax(0,1fr); }
        .mail-nav { position: fixed; top: 0; left: 0; bottom: 0; width: 270px; z-index: 1200; transform: translateX(-100%); transition: transform .2s; box-shadow: 0 0 40px rgba(15,23,42,.2); }
        .mail-app.nav-open .mail-nav { transform: none; }
        .mail-nav-backdrop { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.35); z-index: 1199; }
        .mail-app.nav-open .mail-nav-backdrop { display: block; }
        .nav-toggle-btn { display: inline-flex !important; }
    }
    @media (max-width: 768px) {
        .mail-app { grid-template-columns: 1fr; height: 100vh; }
        .chat-main { position: fixed; inset: 0; z-index: 1000; display: none; }
        .mail-app.chat-active .mail-list { display: none; }
        .mail-app.chat-active .chat-main { display: flex; }
        #mobileBackBtn { display: inline-flex !important; }
        .chat-header { padding: .7rem .9rem; }
        .chat-messages-container { padding: 1rem; gap: .8rem; }
        .chat-input-area { padding: .7rem .9rem .8rem; }
        .mm-grid.two { grid-template-columns: 1fr; }
        .reader-btn .txt { display: none; }
    }
    .mail-nav-backdrop { display: none; } /* only a grid child on desktop; shown as an overlay by the <=1024px rules */
    .nav-toggle-btn { display: none; width: 32px; height: 32px; border: 1px solid #e5e9f0; border-radius: 8px; background: #fff; color: #64748b; cursor: pointer; align-items: center; justify-content: center; }
@endsection

@section('content')
@php $mailUser = Auth::guard('crm')->user(); $mailIsAdminOnly = $mailUser->isAdmin(); @endphp
<div class="mail-app" id="app">
    <!-- ============ LEFT: accounts + folders ============ -->
    <aside class="mail-nav" id="mailNav">
        <div class="mail-nav-head">
            <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()" style="margin:0; cursor:pointer; color:#64748b;"></i>
            <h2><i class="fas fa-envelope"></i> Mail</h2>
            <button type="button" class="nav-compose-btn" id="newMailBtn" style="display:none" onclick="startCompose()" title="New message"><i class="fas fa-pen"></i> New</button>
        </div>
        <div class="mail-nav-scroll">
            <div class="mail-section-title"><span>Mailboxes</span><button type="button" onclick="openAccountModal()" title="Connect a mailbox"><i class="fas fa-plus"></i> Add</button></div>
            <div id="mailAccountsList">
                <div class="mail-nav-item active" data-account="" onclick="selectAccount(null)"><i class="fas fa-fw fa-layer-group"></i><span class="label">All Inboxes</span></div>
                <div style="padding:.5rem .6rem; font-size:.74rem; color:#94a3b8;"><i class="fas fa-spinner fa-spin"></i> Loading mailboxes…</div>
            </div>
            <button type="button" class="mail-add-btn" onclick="openAccountModal()"><i class="fas fa-plus-circle"></i> Add mailbox</button>

            <div class="mail-section-title"><span>Folders</span></div>
            <div id="mailFoldersList">
                <div class="mail-nav-item active" data-folder="inbox" onclick="selectFolder('inbox')"><i class="fas fa-fw fa-inbox"></i><span class="label">Inbox</span></div>
            </div>
            <div class="mail-section-title"><span>CRM</span></div>
            <div class="mail-nav-item" data-folder="leads" onclick="selectFolder('leads')" title="Lead conversations (website/form inquiries and their replies)"><i class="fas fa-fw fa-user-tag"></i><span class="label">Lead conversations</span><span class="count" id="folderLeadsCount" style="display:none">0</span></div>
        </div>
        <div class="mail-nav-foot"><span id="mailSyncStatus">Lead conversations</span><button type="button" onclick="manualSync()" title="Check mailbox for new replies"><i class="fas fa-sync-alt"></i> Sync</button></div>
    </aside>
    <div class="mail-nav-backdrop" onclick="toggleMailNav(false)"></div>

    <!-- ============ MIDDLE: conversation list ============ -->
    <section class="mail-list">
        <div class="mail-list-head">
            <div class="mail-list-title">
                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                    <button type="button" class="nav-toggle-btn" onclick="toggleMailNav(true)" title="Mailboxes & folders"><i class="fas fa-bars"></i></button>
                    <div style="min-width:0;"><h3 id="listTitle">Inbox</h3><small id="listSubtitle">All inboxes</small></div>
                </div>
                <button type="button" class="icon-btn" onclick="chatListRetries=0;loadChatList()" title="Refresh"><i class="fas fa-redo-alt"></i></button>
            </div>
            <div class="search-chat">
                <i class="fas fa-search" style="color:#94a3b8"></i>
                <input type="text" id="chatSearch" placeholder="Search conversations…" onkeyup="filterChats()">
            </div>
        </div>
        <div class="chat-list-items" id="chatListContainer">
            <div class="chat-list-empty"><i class="fas fa-spinner fa-spin"></i> Loading conversations…</div>
        </div>
    </section>

    <!-- ============ RIGHT: reading pane ============ -->
    <section class="chat-main" id="chatWindow">
        <div class="chat-empty" id="emptyState">
            <i class="fas fa-envelope-open-text"></i>
            <h3>Select a conversation</h3>
            <p>Pick a conversation from the list to read and reply.</p>
        </div>

        <div class="chat-header" id="chatHeader" style="display:none">
            <div class="who">
                <button id="mobileBackBtn" type="button" onclick="toggleMobileView(false)"><i class="fas fa-arrow-left"></i></button>
                <div class="chat-avatar" id="activeAvatar">U</div>
                <div class="meta">
                    <div class="chat-name" id="activeName">User Name</div>
                    <div class="sub"><span id="activeEmailHeader"></span> <span id="activeSubject" style="color:#94a3b8"></span></div>
                </div>
            </div>
            <div class="reader-actions">
                <button type="button" class="reader-btn" id="readerStarBtn" onclick="toggleStarActive()" title="Star" style="display:none"><i class="far fa-star"></i></button>
                <button type="button" class="reader-btn" id="readerUnreadBtn" onclick="markActiveUnread()" title="Mark as unread" style="display:none"><i class="fas fa-envelope"></i></button>
                <button type="button" class="reader-btn" id="readerArchiveBtn" onclick="mailAction('archive')" title="Archive" style="display:none"><i class="fas fa-archive"></i></button>
                <button type="button" class="reader-btn" id="readerJunkBtn" onclick="mailAction('junk')" title="Mark as junk" style="display:none"><i class="fas fa-exclamation-circle"></i></button>
                <button type="button" class="reader-btn" id="readerTrashBtn" onclick="mailAction('trash')" title="Move to Trash" style="display:none"><i class="fas fa-trash-alt"></i></button>
                <button type="button" class="reader-btn" id="readerRestoreBtn" onclick="mailAction('inbox')" title="Move to Inbox" style="display:none"><i class="fas fa-inbox"></i><span class="txt">Inbox</span></button>
                <button type="button" class="reader-btn" id="readerDeleteBtn" onclick="mailAction('delete')" title="Delete permanently" style="display:none;color:#dc2626"><i class="fas fa-times-circle"></i><span class="txt">Delete</span></button>
                <select id="readerMoveSel" class="reader-btn" onchange="if(this.value){mailAction(this.value);this.value='';}" title="Move to folder" style="display:none;padding:.45rem .5rem"><option value="">Move to…</option></select>
                <button type="button" class="reader-btn" id="readerReplyBtn" onclick="replyActive('reply')" title="Reply"><i class="fas fa-reply"></i><span class="txt">Reply</span></button>
                <button type="button" class="reader-btn" id="readerReplyAllBtn" onclick="replyActive('reply_all')" title="Reply all" style="display:none"><i class="fas fa-reply-all"></i></button>
                <button type="button" class="reader-btn" id="readerForwardBtn" onclick="replyActive('forward')" title="Forward" style="display:none"><i class="fas fa-share"></i></button>
                <a href="#" id="viewLeadBtn" class="reader-btn" title="Open lead / case"><i class="fas fa-external-link-alt"></i><span class="txt">View Case</span></a>
            </div>
        </div>

        <div class="chat-messages-container" id="messagesContainer" style="display:none"></div>
        <div class="mail-reader" id="mailReader"></div>

        <div class="chat-input-area" id="inputArea" style="display:none">
            @if($mailIsAdminOnly)
                <div class="admin-readonly"><i class="fas fa-eye"></i> Admin view is read-only — replies are sent by the mailbox owner.</div>
            @else
                <form id="chatForm" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <input type="hidden" name="email_subject" id="chatEmailSubject">
                    <input type="hidden" name="cc" id="chatCcField">
                    <input type="hidden" name="bcc" id="chatBccField">
                    <div class="composer-box" id="replyDropZone">
                        <div id="replyDropOverlay" class="reply-drop-overlay"><i class="fas fa-cloud-upload-alt" style="font-size:1.6rem"></i>Drop files to attach</div>
                        <div id="composeFields" class="compose-fields">
                            <div class="cf-row"><label>From</label><select id="cfFrom"></select><span class="cf-mode" id="cfModeLabel"></span></div>
                            <div class="cf-row"><label>To</label><input type="text" id="cfTo" placeholder="name@company.com, another@company.com"><button type="button" class="cf-link" onclick="toggleCcBcc()">Cc / Bcc</button></div>
                            <div class="cf-row" id="cfCcRow" style="display:none"><label>Cc</label><input type="text" id="cfCc" placeholder="cc@company.com"></div>
                            <div class="cf-row" id="cfBccRow" style="display:none"><label>Bcc</label><input type="text" id="cfBcc" placeholder="bcc@company.com"></div>
                            <div class="cf-row"><label>Subject</label><input type="text" id="cfSubject" placeholder="Subject"></div>
                        </div>
                        <div id="attachment-tray"></div>
                        <textarea id="messageInput" name="message_body" placeholder="Write your reply…"></textarea>
                        <div class="composer-bar">
                            <div class="left">
                                <label for="fileInput" class="chat-attach-button"><i class="fas fa-paperclip"></i> Attach
                                    <input type="file" id="fileInput" name="attachments[]" multiple style="display:none" onchange="handleFileSelect(this)">
                                </label>
                                <span class="composer-to">To <strong id="activeEmail">client@example.com</strong></span>
                            </div>
                            <button type="submit" class="send-btn" id="sendBtn"><span id="sendBtnText">Send</span> <i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </section>
</div>

<!-- Subject / CC / BCC before sending -->
<div id="emailMetaModal" class="mm-backdrop" onclick="if(event.target===this)closeEmailMetaModal()">
    <div class="mm-dialog">
        <div class="mm-head"><h4>Email Details</h4><button type="button" onclick="closeEmailMetaModal()"><i class="fas fa-times"></i></button></div>
        <div class="mm-body">
            <div class="mm-grid">
                <div class="mm-field"><label>Subject</label><input type="text" id="modalSubject"></div>
                <div class="mm-grid two">
                    <div class="mm-field"><label>CC</label><input type="text" id="modalCc" placeholder="cc@example.com, cc2@example.com"></div>
                    <div class="mm-field"><label>BCC</label><input type="text" id="modalBcc" placeholder="bcc@example.com"></div>
                </div>
            </div>
        </div>
        <div class="mm-foot">
            <button type="button" class="mm-btn" onclick="closeEmailMetaModal()">Cancel</button>
            <button type="button" class="mm-btn primary" onclick="submitEmailMeta()"><i class="fas fa-paper-plane"></i> Send Reply</button>
        </div>
    </div>
</div>

<!-- Add / edit mailbox -->
<div id="accountModal" class="mm-backdrop" onclick="if(event.target===this)closeAccountModal()">
    <div class="mm-dialog wide">
        <div class="mm-head"><h4 id="accountModalTitle">Connect a mailbox</h4><button type="button" onclick="closeAccountModal()"><i class="fas fa-times"></i></button></div>
        <div class="mm-body">
            <input type="hidden" id="accId">
            <div class="mm-grid">
                <div class="mm-grid two">
                    <div class="mm-field"><label>Provider</label><select id="accProvider" onchange="applyPreset()"></select><div class="mm-hint" id="accProviderHint"></div></div>
                    <div class="mm-field"><label>Display name</label><input type="text" id="accDisplayName" placeholder="e.g. Jack · Sales"></div>
                </div>
                <div class="mm-grid two">
                    <div class="mm-field"><label>Email address</label><input type="email" id="accEmail" placeholder="you@company.com" oninput="syncAccUser()"></div>
                    <div class="mm-field"><label>Password / App password</label><input type="password" id="accPass" autocomplete="new-password" placeholder="••••••••"><div class="mm-hint" id="accPassHint"></div></div>
                </div>
                <div><button type="button" class="mm-adv" onclick="toggleAdvanced()"><i class="fas fa-sliders-h"></i> <span id="advLabel">Show server settings</span></button></div>
                <div id="accAdvanced" style="display:none" class="mm-grid">
                    <div class="mm-field"><label>Login username <span style="font-weight:500;color:#94a3b8">(usually the email)</span></label><input type="text" id="accUser"></div>
                    <div class="mm-grid two">
                        <div class="mm-field"><label>IMAP host</label><input type="text" id="accImapHost"></div>
                        <div class="mm-grid two">
                            <div class="mm-field"><label>Port</label><input type="number" id="accImapPort"></div>
                            <div class="mm-field"><label>Encryption</label><select id="accImapEnc"><option value="ssl">SSL</option><option value="tls">STARTTLS</option><option value="none">None</option></select></div>
                        </div>
                    </div>
                    <div class="mm-grid two">
                        <div class="mm-field"><label>SMTP host</label><input type="text" id="accSmtpHost"></div>
                        <div class="mm-grid two">
                            <div class="mm-field"><label>Port</label><input type="number" id="accSmtpPort"></div>
                            <div class="mm-field"><label>Encryption</label><select id="accSmtpEnc"><option value="tls">STARTTLS</option><option value="ssl">SSL</option><option value="none">None</option></select></div>
                        </div>
                    </div>
                    <div class="mm-field"><label>Signature (HTML allowed)</label><textarea id="accSignature" rows="3" placeholder="Regards, …"></textarea></div>
                </div>
                <label class="mm-toggle"><input type="checkbox" id="accShare" checked> Allow admins read-only access to this mailbox</label>
                <div class="mm-test" id="accTestResult"></div>
            </div>
        </div>
        <div class="mm-foot">
            <span class="mm-status" id="accStatus"></span>
            <button type="button" class="mm-btn" id="accTestBtn" onclick="testAccount()"><i class="fas fa-plug"></i> Test connection</button>
            <button type="button" class="mm-btn primary" id="accSaveBtn" onclick="saveAccount()"><i class="fas fa-check"></i> Save</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ---------------------------------------------------------------------------
// AJAX-nav safe: top-level `var`/function only, init runs immediately, timers and
// listeners are stored on window and self-stop when #app is gone.
// ---------------------------------------------------------------------------
var MAIL_ROUTES = {
    list:      '{{ route("crm.chats.list") }}',
    sync:      '{{ route("crm.chats.sync") }}',
    accounts:  '{{ route("crm.mail.accounts.index") }}',
    presets:   '{{ route("crm.mail.accounts.presets") }}',
    accTest:   '{{ route("crm.mail.accounts.test") }}',
    folders:   '{{ route("crm.mail.folders") }}',
    messages:  '{{ route("crm.mail.messages") }}',
    mailSync:  '{{ route("crm.mail.sync") }}'
};
var MAIL_CSRF = '{{ csrf_token() }}';
var MAIL_IS_ADMIN = {{ $mailIsAdminOnly ? 'true' : 'false' }};
var MAIL_TINYMCE_SRC = "{{ URL::asset('tinymce/tinymce.min.js') }}";

var activeChatId = null, activeAccountId = null, activeFolder = 'inbox';
var chatsData = [], accountsData = [], presetsData = {};
var folderCounts = {}, customFolders = [], mailMessages = [], mailPage = 1, mailHasMore = false, activeMailId = null, activeMail = null, mailListLoading = false, mailListController = null;
var FOLDER_META = { inbox:['fa-inbox','Inbox'], starred:['fa-star','Starred'], sent:['fa-paper-plane','Sent'], drafts:['fa-file-alt','Drafts'], archive:['fa-archive','Archive'], junk:['fa-exclamation-circle','Junk'], trash:['fa-trash-alt','Trash'] };
function isMailMode(){ return activeFolder !== 'leads'; }
var lastMsgId = 0, lastDisplayedDateStr = null, pendingChatForm = null;
var chatListLoading = false, chatListController = null, chatListRetries = 0, inboxSyncRunning = false;
var ACC_COLORS = ['#6c5ce7','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#14b8a6','#f97316'];

function esc(s){ return (window.crmEsc ? crmEsc(String(s==null?'':s)) : String(s==null?'':s).replace(/[&<>"']/g, function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];})); }
function initialsOf(name){ return name ? name.split(' ').filter(function(n){return n;}).map(function(n){return n[0];}).join('').substring(0,2).toUpperCase() : '?'; }
function accColor(id){ return ACC_COLORS[(parseInt(id,10)||0) % ACC_COLORS.length]; }
function jsonHeaders(){ return {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':MAIL_CSRF,'Accept':'application/json','Content-Type':'application/json'}; }
function toast(msg, type){ if (window.showToast) showToast(msg, type||'success'); else alert(msg); }

// ============================== accounts ===================================
function loadAccounts(){
    return fetch(MAIL_ROUTES.accounts, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(function(r){ if(!r.ok) throw new Error('accounts '+r.status); return r.json(); })
        .then(function(d){ accountsData = d.accounts || []; renderAccounts(); })
        .catch(function(e){ console.error(e); var el=document.getElementById('mailAccountsList'); if(el) el.innerHTML = '<div class="mail-nav-item active" data-account="" onclick="selectAccount(null)"><i class="fas fa-fw fa-layer-group"></i><span class="label">All Inboxes</span></div><div style="padding:.4rem .6rem;font-size:.72rem;color:#b91c1c">Could not load mailboxes.</div>'; });
}
function renderAccounts(){
    var nb = document.getElementById('newMailBtn'); if (nb) nb.style.display = (ownSendableAccounts().length && !MAIL_IS_ADMIN) ? '' : 'none';
    var el = document.getElementById('mailAccountsList'); if(!el) return;
    var html = '<div class="mail-nav-item '+(activeAccountId===null?'active':'')+'" data-account="" onclick="selectAccount(null)"><i class="fas fa-fw fa-layer-group"></i><span class="label">All Inboxes</span></div>';
    if(!accountsData.length){
        html += '<div style="padding:.45rem .6rem; font-size:.74rem; color:#94a3b8;">No mailbox connected yet.</div>';
    }
    accountsData.forEach(function(a){
        var own = a.is_own, warn = a.last_sync_error ? '<i class="fas fa-exclamation-triangle mail-acc-err" title="'+esc(a.last_sync_error)+'"></i>' : '';
        var menu = '';
        if(own){
            menu = '<span class="mail-acc-menu">'
                + (a.is_default ? '' : '<button type="button" title="Make default" onclick="event.stopPropagation();accountAction('+a.id+',\'default\')"><i class="fas fa-star"></i></button>')
                + '<button type="button" title="Edit" onclick="event.stopPropagation();openAccountModal('+a.id+')"><i class="fas fa-pen"></i></button>'
                + '<button type="button" title="'+(a.sync_enabled?'Pause sync':'Resume sync')+'" onclick="event.stopPropagation();accountAction('+a.id+',\'toggle\')"><i class="fas '+(a.sync_enabled?'fa-pause':'fa-play')+'"></i></button>'
                + '<button type="button" class="danger" title="Remove" onclick="event.stopPropagation();removeAccount('+a.id+')"><i class="fas fa-trash-alt"></i></button>'
                + '</span>';
        }
        html += '<div class="mail-nav-item '+(activeAccountId===a.id?'active':'')+'" data-account="'+a.id+'" onclick="selectAccount('+a.id+')" title="'+esc(a.email_address)+(own?'':' · '+esc(a.owner_name||'')+' (read-only)')+'">'
            + '<span class="mail-acc-dot" style="background:'+accColor(a.id)+';opacity:'+(a.is_active?1:.35)+'"></span>'
            + '<span class="label">'+esc(a.display_name || a.email_address)+(a.is_default?' <i class="fas fa-star" style="font-size:.6rem;color:#f59e0b"></i>':'')
            + '<span class="mail-acc-sub">'+esc(a.email_address)+(own?'':' · '+esc(a.owner_name||''))+'</span></span>'
            + warn + menu + '</div>';
    });
    el.innerHTML = html;
}
function selectAccount(id){
    activeAccountId = id;
    renderAccounts();
    var a = accountsData.find(function(x){return x.id===id;});
    var sub = document.getElementById('listSubtitle'); if(sub) sub.textContent = a ? a.email_address : 'All inboxes';
    toggleMailNav(false);
    loadFolders();
    if (isMailMode()) { loadMailList(true); } else { chatListRetries = 0; loadChatList(true); }
}
function selectFolder(folder){
    activeFolder = folder;
    document.querySelectorAll('.mail-nav-item[data-folder]').forEach(function(el){ el.classList.toggle('active', el.dataset.folder===folder); });
    var t = document.getElementById('listTitle');
    if (t) {
        if (folder === 'leads') t.textContent = 'Lead conversations';
        else if (folder.indexOf('custom:')===0) { var cf = customFolders.find(function(f){ return 'custom:'+f.id===folder; }); t.textContent = cf ? cf.name : 'Folder'; }
        else t.textContent = (FOLDER_META[folder]||['',folder])[1];
    }
    var search = document.getElementById('chatSearch'); if (search) { search.value=''; search.placeholder = isMailMode() ? 'Search mail…' : 'Search conversations…'; }
    toggleMailNav(false);
    closeReader();
    if (isMailMode()) loadMailList(true); else { chatListRetries = 0; loadChatList(true); }
}
function closeReader(){
    activeMailId = null; activeMail = null;
    if (window.__chatMsgPoll){ clearInterval(window.__chatMsgPoll); window.__chatMsgPoll=null; }
    activeChatId = null;
    var app=document.getElementById('app'); if(app) app.classList.remove('chat-active');
    ['chatHeader','messagesContainer','inputArea','mailReader'].forEach(function(id){ var el=document.getElementById(id); if(el) el.style.display='none'; });
    var es=document.getElementById('emptyState'); if(es) es.style.display='flex';
}

// ============================== folders ====================================
function loadFolders(){
    var url = MAIL_ROUTES.folders + (activeAccountId ? '?account='+activeAccountId : '');
    return fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(function(r){ if(!r.ok) throw new Error('folders '+r.status); return r.json(); })
        .then(function(d){ folderCounts = d.counts || {}; customFolders = d.custom || []; renderFolders(); })
        .catch(function(e){ console.error(e); });
}
function renderFolders(){
    var el = document.getElementById('mailFoldersList'); if(!el) return;
    var html = '';
    Object.keys(FOLDER_META).forEach(function(key){
        var c = folderCounts[key] || {total:0, unread:0};
        var badge = key==='inbox' || key==='starred' || key==='drafts' ? (key==='drafts' ? c.total : c.unread) : 0;
        html += '<div class="mail-nav-item '+(activeFolder===key?'active':'')+'" data-folder="'+key+'" onclick="selectFolder(\''+key+'\')"><i class="fas fa-fw '+FOLDER_META[key][0]+'"></i><span class="label">'+FOLDER_META[key][1]+'</span>'+(badge?'<span class="count">'+badge+'</span>':'')+'</div>';
    });
    customFolders.forEach(function(f){
        var key = 'custom:'+f.id;
        html += '<div class="mail-nav-item '+(activeFolder===key?'active':'')+'" data-folder="'+key+'" onclick="selectFolder(\''+key+'\')" title="'+esc(f.path)+'"><i class="fas fa-fw fa-folder"></i><span class="label">'+esc(f.name)+'</span>'+(f.unread_count?'<span class="count">'+f.unread_count+'</span>':'')+'</div>';
    });
    el.innerHTML = html;
    var leads = document.querySelector('.mail-nav-item[data-folder="leads"]'); if (leads) leads.classList.toggle('active', activeFolder==='leads');
}

// ============================ mail list ====================================
function loadMailList(reset){
    var container = document.getElementById('chatListContainer'); if(!container) return Promise.resolve();
    if (reset) { mailPage = 1; mailMessages = []; container.innerHTML = '<div class="chat-list-empty"><i class="fas fa-spinner fa-spin"></i> Loading mail…</div>'; }
    if (mailListController) { try{ mailListController.abort(); }catch(e){} }
    mailListController = new AbortController(); var controller = mailListController;
    var params = new URLSearchParams();
    if (activeAccountId) params.set('account', activeAccountId);
    if (activeFolder.indexOf('custom:')===0) params.set('folder', activeFolder.split(':')[1]); else params.set('type', activeFolder);
    var q = document.getElementById('chatSearch') ? document.getElementById('chatSearch').value.trim() : '';
    if (q) params.set('q', q);
    params.set('page', mailPage);
    return fetch(MAIL_ROUTES.messages+'?'+params.toString(), {signal:controller.signal, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(function(r){ if(!r.ok) throw new Error('messages '+r.status); return r.json(); })
        .then(function(d){
            if (controller !== mailListController) return;
            var incoming = d.messages || [];
            if (mailPage === 1) mailMessages = incoming; else incoming.forEach(function(m){ if(!mailMessages.some(function(x){return x.id===m.id;})) mailMessages.push(m); });
            mailHasMore = !!d.has_more;
            renderMailList();
        })
        .catch(function(e){ if (e.name==='AbortError') return; console.error(e); if (!mailMessages.length) container.innerHTML = '<div class="chat-list-empty"><i class="fas fa-plug"></i>Mail could not be loaded.<br><button type="button" class="reader-btn" style="margin-top:.8rem" onclick="loadMailList(true)">Retry</button></div>'; });
}
function loadMoreMail(){ if (!mailHasMore) return; mailPage++; loadMailList(false); }
function renderMailList(){
    var container = document.getElementById('chatListContainer'); if(!container) return;
    if (!mailMessages.length) {
        var acc = accountsData.length;
        container.innerHTML = '<div class="chat-list-empty"><i class="fas fa-inbox"></i>'+(acc ? 'No mail here yet.' : 'Connect a mailbox to see your email here.')+(acc ? '' : '<br><button type="button" class="reader-btn primary" style="margin-top:.8rem" onclick="openAccountModal()"><i class="fas fa-plus"></i> Add mailbox</button>')+'</div>';
        return;
    }
    var html = '';
    mailMessages.forEach(function(m){
        var who = m.is_outgoing ? ('To: ' + ((m.to && m.to[0]) ? (m.to[0].name || m.to[0].email) : '')) : (m.from_name || m.from_email || 'Unknown');
        var time = m.received_at ? moment(m.received_at).calendar(null, {sameDay:'h:mm A', lastDay:'[Yesterday]', lastWeek:'ddd', sameElse:'MMM D'}) : '';
        var acc = accountsData.find(function(a){return a.id===m.account_id;});
        var accChip = acc && activeAccountId===null && accountsData.length>1 ? '<span class="chat-acc-chip"><span class="mail-acc-dot" style="width:7px;height:7px;flex-basis:7px;background:'+accColor(acc.id)+'"></span>'+esc(acc.display_name||acc.email_address)+'</span>' : '';
        html += '<div class="chat-item'+(activeMailId===m.id?' active':'')+(!m.is_read && !m.is_outgoing?' unread':'')+'" data-mail="'+m.id+'" onclick="openMail('+m.id+')">'
            + '<div class="chat-avatar" style="'+(m.is_outgoing?'background:#f1f5f9;color:#64748b':'')+'">'+esc(initialsOf(m.is_outgoing ? ((m.to&&m.to[0])?(m.to[0].name||m.to[0].email):'') : (m.from_name||m.from_email)))+'</div>'
            + '<div class="chat-info">'
            +   '<div class="chat-row1"><span class="chat-name">'+esc(who)+'</span><span class="chat-time">'+esc(time)+'</span></div>'
            +   '<div class="chat-subject">'+esc(m.subject || '(no subject)')+'<span class="row-icons">'+(m.has_attachments?'<i class="fas fa-paperclip"></i>':'')+(acc && acc.is_own && !MAIL_IS_ADMIN ? '<button type="button" class="star-btn '+(m.is_starred?'on':'')+'" onclick="event.stopPropagation();toggleStar('+m.id+')" title="Star"><i class="'+(m.is_starred?'fas':'far')+' fa-star"></i></button>' : (m.is_starred?'<i class="fas fa-star" style="color:#f59e0b"></i>':''))+'</span></div>'
            +   '<div class="chat-snippet">'+accChip+'<span style="min-width:0;overflow:hidden;text-overflow:ellipsis">'+esc(m.snippet||'')+'</span></div>'
            + '</div></div>';
    });
    if (mailHasMore) html += '<button type="button" class="list-loadmore" onclick="loadMoreMail()">Load more</button>';
    container.innerHTML = html;
}
function toggleStar(id){
    fetch(MAIL_ROUTES.messages+'/'+id+'/star', {method:'POST', headers:jsonHeaders(), body:'{}'})
        .then(function(r){ return r.json(); })
        .then(function(d){ var m = mailMessages.find(function(x){return x.id===id;}); if (m) m.is_starred = d.is_starred; if (activeMail && activeMail.id===id) { activeMail.is_starred = d.is_starred; paintStar(); } renderMailList(); loadFolders(); })
        .catch(function(){ toast('Could not update star','error'); });
}
function toggleStarActive(){ if (activeMail) toggleStar(activeMail.id); }
function paintStar(){ var b=document.getElementById('readerStarBtn'); if(!b) return; b.innerHTML = '<i class="'+(activeMail && activeMail.is_starred?'fas':'far')+' fa-star"></i>'; b.classList.toggle('on', !!(activeMail && activeMail.is_starred)); }
function mailAction(action){
    if (!activeMail) return;
    var id = activeMail.id, m = activeMail;
    var go = function(){
        var req = action === 'delete'
            ? fetch(MAIL_ROUTES.messages+'/'+id, {method:'DELETE', headers:jsonHeaders()})
            : fetch(MAIL_ROUTES.messages+'/'+id+'/move', {method:'POST', headers:jsonHeaders(), body:JSON.stringify({to:action})});
        req.then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); })
           .then(function(x){
               if (!x.ok || x.j.success===false) throw new Error(x.j.message || 'Action failed');
               mailMessages = mailMessages.filter(function(x){ return x.id!==id; });
               closeReader(); renderMailList(); loadFolders();
               toast({archive:'Archived', trash:'Moved to Trash', junk:'Marked as junk', inbox:'Moved to Inbox', delete:'Deleted permanently'}[action] || 'Moved');
           })
           .catch(function(e){ toast(e.message, 'error'); });
    };
    if (action === 'delete') { if (window.customConfirm) customConfirm('Delete permanently?', 'This removes the email from the mailbox as well. This cannot be undone.', go, 'Yes, Delete', 'btn-confirm'); else if (confirm('Delete permanently?')) go(); }
    else go();
}
function paintReaderActions(m){
    var acc = accountsData.find(function(a){ return a.id===m.account_id; });
    var canAct = !!(m.can_act || (acc && acc.is_own && acc.is_active)) && !MAIL_IS_ADMIN;
    var type = m.folder_type || activeFolder;
    var show = function(id, on){ var b=document.getElementById(id); if(b) b.style.display = on ? '' : 'none'; };
    show('readerArchiveBtn', canAct && type !== 'archive' && type !== 'trash');
    show('readerJunkBtn',    canAct && type !== 'junk' && type !== 'trash');
    show('readerTrashBtn',   canAct && type !== 'trash');
    show('readerRestoreBtn', canAct && (type === 'trash' || type === 'junk' || type === 'archive'));
    show('readerDeleteBtn',  canAct && type === 'trash');
    var sel = document.getElementById('readerMoveSel');
    if (sel) {
        sel.style.display = canAct && customFolders.length ? '' : 'none';
        sel.innerHTML = '<option value="">Move to…</option>' + customFolders.filter(function(f){ return f.account_id===m.account_id; }).map(function(f){ return '<option value="folder:'+f.id+'">'+esc(f.name)+'</option>'; }).join('');
    }
}
function markActiveUnread(){
    if (!activeMail) return;
    fetch(MAIL_ROUTES.messages+'/'+activeMail.id+'/read', {method:'POST', headers:jsonHeaders(), body:JSON.stringify({read:false})})
        .then(function(){ var m = mailMessages.find(function(x){return x.id===activeMail.id;}); if (m) m.is_read=false; closeReader(); renderMailList(); loadFolders(); toast('Marked as unread'); })
        .catch(function(){ toast('Could not update','error'); });
}
function fmtBytes(n){ if(!n) return ''; var u=['B','KB','MB','GB'], i=0; while(n>=1024 && i<u.length-1){ n/=1024; i++; } return (i?n.toFixed(1):n)+' '+u[i]; }
function openMail(id){
    activeMailId = id; activeChatId = null;
    if (window.__chatMsgPoll){ clearInterval(window.__chatMsgPoll); window.__chatMsgPoll=null; }
    toggleMobileView(true);
    document.getElementById('emptyState').style.display='none';
    document.getElementById('chatHeader').style.display='flex';
    document.getElementById('messagesContainer').style.display='none';
    document.getElementById('inputArea').style.display='none';
    var reader = document.getElementById('mailReader'); reader.style.display='flex'; reader.innerHTML = '<div class="chat-list-empty"><i class="fas fa-spinner fa-spin"></i></div>';
    renderMailList();
    fetch(MAIL_ROUTES.messages+'/'+id, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(function(r){ if(!r.ok) throw new Error('message '+r.status); return r.json(); })
        .then(function(d){
            if (activeMailId !== id) return;
            activeMail = d.message; activeMail.attachments = d.attachments || []; activeMail.thread = d.thread || [];
            var row = mailMessages.find(function(x){return x.id===id;}); if (row) row.is_read = true;
            renderMailList(); loadFolders(); renderMailReader();
        })
        .catch(function(e){ console.error(e); reader.innerHTML = '<div class="chat-list-empty"><i class="fas fa-exclamation-triangle"></i>Could not load this message.</div>'; });
}
function renderMailReader(){
    var m = activeMail; if (!m) return;
    var who = m.is_outgoing ? ((m.to&&m.to[0]) ? (m.to[0].name || m.to[0].email) : 'Recipient') : (m.from_name || m.from_email || 'Unknown');
    document.getElementById('activeName').innerText = who;
    document.getElementById('activeEmailHeader').innerText = m.is_outgoing ? ('via ' + (m.account_email||'')) : (m.from_email || '');
    document.getElementById('activeSubject').innerText = '';
    document.getElementById('activeAvatar').innerText = initialsOf(who);
    var lead = document.getElementById('viewLeadBtn'); lead.style.display = m.crm_email_id ? '' : 'none'; if (m.crm_email_id) lead.href = '/crm/email/'+m.crm_email_id;
    document.getElementById('readerStarBtn').style.display=''; paintStar();
    document.getElementById('readerUnreadBtn').style.display = m.can_act ? '' : 'none';
    document.getElementById('readerStarBtn').style.display = m.can_act ? '' : 'none';
    paintReaderActions(m);
    var acc = accountsData.find(function(a){ return a.id===m.account_id; });
    var canSend = !!(acc && acc.is_own && acc.is_active && !MAIL_IS_ADMIN && document.getElementById('composeFields'));
    document.getElementById('readerReplyBtn').style.display = canSend ? '' : 'none';
    document.getElementById('readerReplyAllBtn').style.display = canSend ? '' : 'none';
    document.getElementById('readerForwardBtn').style.display = canSend ? '' : 'none';
    if (canSend) { setComposerMode('mail-reply', { accountId: acc.id, to: m.is_outgoing ? (m.to||[]).map(function(a){return a.email;}).join(', ') : (m.reply_to || m.from_email || ''), subject: 'Re: ' + stripRe(m.subject) }); }
    else { var ia=document.getElementById('inputArea'); if (ia) ia.style.display='none'; }

    var toList = (m.to||[]).map(function(a){ return esc(a.name ? a.name+' <'+a.email+'>' : a.email); }).join(', ');
    var ccList = (m.cc||[]).map(function(a){ return esc(a.name ? a.name+' <'+a.email+'>' : a.email); }).join(', ');
    var when = m.received_at ? moment(m.received_at).format('ddd, MMM D, YYYY · h:mm A') : '';
    var html = '<div class="mail-reader-head">'
        + '<h2><span>'+esc(m.subject||'(no subject)')+'</span></h2>'
        + '<div class="mail-meta-row"><div class="chat-avatar">'+esc(initialsOf(m.from_name||m.from_email))+'</div><div class="lines"><div><b>'+esc(m.from_name||m.from_email||'')+'</b> <span class="addr">'+(m.from_name?'&lt;'+esc(m.from_email||'')+'&gt;':'')+'</span></div>'
        + (toList?'<small><b>To:</b> '+toList+'</small>':'') + (ccList?'<small><b>Cc:</b> '+ccList+'</small>':'') + '</div><div class="when">'+esc(when)+'</div></div>'
        + '</div>';
    html += '<div class="mail-body-card">';
    if (m.html) {
        var doc = '<!doctype html><html><head><meta charset="utf-8"><base target="_blank"><style>body{margin:0;padding:16px 20px;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.55;color:#1e293b;word-wrap:break-word;overflow-wrap:anywhere}img{max-width:100%;height:auto}blockquote{border-left:3px solid #e2e8f0;margin:8px 0;padding-left:12px;color:#64748b}table{max-width:100%}pre{white-space:pre-wrap}</style></head><body>'+m.html+'</body></html>';
        html += '<iframe class="mail-html" sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" srcdoc="'+doc.replace(/&/g,'&amp;').replace(/"/g,'&quot;')+'" onload="fitMailFrame(this)"></iframe>';
    } else {
        html += '<pre>'+esc(m.text||'(empty message)')+'</pre>';
    }
    if (m.attachments && m.attachments.length) {
        html += '<div class="mail-atts">' + m.attachments.map(function(a){ return '<a class="mail-att" href="'+a.url+'" target="_blank" rel="noopener" data-no-ajax-nav title="'+esc(a.name)+'"><i class="fas '+(/^image\//.test(a.mime||'')?'fa-image':(a.mime==='application/pdf'?'fa-file-pdf':'fa-paperclip'))+'"></i><span>'+esc(a.name)+'</span><small>'+fmtBytes(a.size)+'</small></a>'; }).join('') + '</div>';
    }
    html += '</div>';
    if (m.thread && m.thread.length) {
        html += '<div class="thread-strip"><h5>'+m.thread.length+' more in this conversation</h5>' + m.thread.map(function(t){ return '<div class="thread-item" onclick="openMail('+t.id+')"><span class="who">'+(t.is_outgoing?'You':esc(t.from_name||t.from_email||''))+'</span><span class="snip">'+esc(t.snippet||t.subject||'')+'</span>'+(t.has_attachments?'<i class="fas fa-paperclip" style="color:#94a3b8;font-size:.7rem"></i>':'')+'<span class="when">'+(t.received_at?moment(t.received_at).format('MMM D, h:mm A'):'')+'</span></div>'; }).join('') + '</div>';
    }
    if (m.crm_email_id) {
        html += '<div class="mail-reply-note"><i class="fas fa-link"></i> Linked to lead <a href="/crm/email/'+m.crm_email_id+'">#'+m.crm_email_id+'</a>' + (canSend ? ' — replies from here are also recorded on the lead.' : '') + '</div>';
    } else if (!canSend && !m.is_outgoing) {
        html += '<div class="mail-reply-note"><i class="fas fa-eye"></i> Read-only view — only the mailbox owner can reply from this mailbox.</div>';
    }
    document.getElementById('mailReader').innerHTML = html;
    document.getElementById('mailReader').scrollTop = 0;
}
function fitMailFrame(f){ try { var h = f.contentDocument.documentElement.scrollHeight || f.contentDocument.body.scrollHeight; f.style.height = Math.min(Math.max(h + 24, 160), 4000) + 'px'; } catch(e) { f.style.height = '600px'; } }
function accountAction(id, action){
    fetch(MAIL_ROUTES.accounts+'/'+id+'/'+action, {method:'POST', headers:jsonHeaders(), body: action==='toggle' ? JSON.stringify({field:'sync_enabled'}) : '{}'})
        .then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); })
        .then(function(x){ if(!x.ok) throw new Error(x.j.message||'Failed'); toast(action==='default'?'Default mailbox updated':'Mailbox updated'); loadAccounts(); })
        .catch(function(e){ toast(e.message, 'error'); });
}
function removeAccount(id){
    var a = accountsData.find(function(x){return x.id===id;}); if(!a) return;
    var go = function(){
        fetch(MAIL_ROUTES.accounts+'/'+id, {method:'DELETE', headers:jsonHeaders()})
            .then(function(r){ if(!r.ok) throw new Error('Could not remove mailbox'); toast('Mailbox removed'); if(activeAccountId===id) activeAccountId=null; loadAccounts().then(function(){ loadChatList(true); }); })
            .catch(function(e){ toast(e.message,'error'); });
    };
    if (window.customConfirm) customConfirm('Remove mailbox?', a.email_address+' will be disconnected. Already-synced mail is kept.', go, 'Yes, Remove', 'btn-confirm'); else if(confirm('Remove '+a.email_address+'?')) go();
}

// ------------------------------ account modal -------------------------------
function loadPresets(){
    if (Object.keys(presetsData).length) return Promise.resolve(presetsData);
    return fetch(MAIL_ROUTES.presets, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){ presetsData = d.presets||{}; return presetsData; });
}
function openAccountModal(id){
    var m = document.getElementById('accountModal'); if(!m) return;
    loadPresets().then(function(){
        var sel = document.getElementById('accProvider'); sel.innerHTML = '';
        Object.keys(presetsData).forEach(function(k){ var o=document.createElement('option'); o.value=k; o.textContent=presetsData[k].label; sel.appendChild(o); });
        var a = id ? accountsData.find(function(x){return x.id===id;}) : null;
        document.getElementById('accountModalTitle').textContent = a ? 'Edit mailbox' : 'Connect a mailbox';
        document.getElementById('accId').value = a ? a.id : '';
        sel.value = a ? a.provider : 'hostinger';
        document.getElementById('accDisplayName').value = a ? (a.display_name||'') : '';
        document.getElementById('accEmail').value = a ? a.email_address : '';
        document.getElementById('accPass').value = '';
        document.getElementById('accPassHint').textContent = a ? 'Leave blank to keep the current password.' : '';
        document.getElementById('accUser').value = a ? (a.email_user||'') : '';
        document.getElementById('accImapHost').value = a ? (a.imap_host||'') : '';
        document.getElementById('accImapPort').value = a ? (a.imap_port||993) : 993;
        document.getElementById('accImapEnc').value = a ? (a.imap_encryption||'ssl') : 'ssl';
        document.getElementById('accSmtpHost').value = a ? (a.smtp_host||'') : '';
        document.getElementById('accSmtpPort').value = a ? (a.smtp_port||587) : 587;
        document.getElementById('accSmtpEnc').value = a ? (a.smtp_encryption||'tls') : 'tls';
        document.getElementById('accSignature').value = a ? (a.signature||'') : '';
        document.getElementById('accShare').checked = a ? !!a.share_with_admin : true;
        document.getElementById('accTestResult').innerHTML = '';
        setAccStatus('', '');
        if(!a) applyPreset(); else document.getElementById('accProviderHint').textContent = (presetsData[a.provider]||{}).hint||'';
        var adv = document.getElementById('accAdvanced'); adv.style.display = (a && a.provider==='custom') || (!a && sel.value==='custom') ? 'grid' : 'none';
        document.getElementById('advLabel').textContent = adv.style.display==='none' ? 'Show server settings' : 'Hide server settings';
        m.classList.add('open');
        setTimeout(function(){ document.getElementById(a?'accDisplayName':'accEmail').focus(); }, 50);
    });
}
function closeAccountModal(){ var m=document.getElementById('accountModal'); if(m) m.classList.remove('open'); }
function applyPreset(){
    var p = presetsData[document.getElementById('accProvider').value]; if(!p) return;
    document.getElementById('accProviderHint').textContent = p.hint||'';
    document.getElementById('accImapHost').value = p.imap_host||''; document.getElementById('accImapPort').value = p.imap_port||993; document.getElementById('accImapEnc').value = p.imap_encryption||'ssl';
    document.getElementById('accSmtpHost').value = p.smtp_host||''; document.getElementById('accSmtpPort').value = p.smtp_port||587; document.getElementById('accSmtpEnc').value = p.smtp_encryption||'tls';
    if (document.getElementById('accProvider').value==='custom') { document.getElementById('accAdvanced').style.display='grid'; document.getElementById('advLabel').textContent='Hide server settings'; }
}
function syncAccUser(){ var u=document.getElementById('accUser'); if(!u.dataset.touched) u.value = document.getElementById('accEmail').value; }
function toggleAdvanced(){ var adv=document.getElementById('accAdvanced'); var show = adv.style.display==='none'; adv.style.display = show?'grid':'none'; document.getElementById('advLabel').textContent = show?'Hide server settings':'Show server settings'; }
function setAccStatus(text, cls){ var s=document.getElementById('accStatus'); s.textContent=text; s.className='mm-status '+(cls||''); }
function accPayload(){
    var email = document.getElementById('accEmail').value.trim();
    return {
        email_address: email,
        display_name: document.getElementById('accDisplayName').value.trim() || null,
        provider: document.getElementById('accProvider').value,
        email_user: document.getElementById('accUser').value.trim() || email,
        email_pass: document.getElementById('accPass').value,
        imap_host: document.getElementById('accImapHost').value.trim(), imap_port: parseInt(document.getElementById('accImapPort').value,10)||null, imap_encryption: document.getElementById('accImapEnc').value,
        smtp_host: document.getElementById('accSmtpHost').value.trim(), smtp_port: parseInt(document.getElementById('accSmtpPort').value,10)||null, smtp_encryption: document.getElementById('accSmtpEnc').value,
        signature: document.getElementById('accSignature').value,
        share_with_admin: document.getElementById('accShare').checked
    };
}
function renderTest(t){
    var el = document.getElementById('accTestResult'); if(!t){ el.innerHTML=''; return; }
    el.innerHTML = '<div class="'+(t.imap&&t.imap.ok?'ok':'err')+'"><i class="fas '+(t.imap&&t.imap.ok?'fa-check-circle':'fa-times-circle')+'"></i> '+esc(t.imap?t.imap.message:'')+'</div>'
                 + '<div class="'+(t.smtp&&t.smtp.ok?'ok':'err')+'"><i class="fas '+(t.smtp&&t.smtp.ok?'fa-check-circle':'fa-times-circle')+'"></i> '+esc(t.smtp?t.smtp.message:'')+'</div>';
}
function firstError(j){ if(j.errors){ var k=Object.keys(j.errors)[0]; if(k) return j.errors[k][0]; } return j.message||'Request failed'; }
function testAccount(){
    var id = document.getElementById('accId').value, p = accPayload();
    if(!p.email_address){ setAccStatus('Enter the email address first.','err'); return; }
    if(!id && !p.email_pass){ setAccStatus('Enter the password to test.','err'); return; }
    var btn=document.getElementById('accTestBtn'); btn.disabled=true; setAccStatus('Testing IMAP and SMTP…',''); renderTest(null);
    fetch(id ? MAIL_ROUTES.accounts+'/'+id+'/test' : MAIL_ROUTES.accTest, {method:'POST', headers:jsonHeaders(), body:JSON.stringify(p)})
        .then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); })
        .then(function(x){ if(!x.ok && !x.j.imap){ throw new Error(firstError(x.j)); } renderTest(x.j); setAccStatus(x.j.success?'Connection OK':'Connection failed', x.j.success?'ok':'err'); })
        .catch(function(e){ setAccStatus(e.message,'err'); })
        .then(function(){ btn.disabled=false; });
}
function saveAccount(){
    var id = document.getElementById('accId').value, p = accPayload();
    if(!p.email_address){ setAccStatus('Email address is required.','err'); return; }
    if(!id && !p.email_pass){ setAccStatus('Password is required.','err'); return; }
    var btn=document.getElementById('accSaveBtn'); btn.disabled=true; setAccStatus('Verifying and saving…',''); renderTest(null);
    fetch(id ? MAIL_ROUTES.accounts+'/'+id : MAIL_ROUTES.accounts, {method: id?'PUT':'POST', headers:jsonHeaders(), body:JSON.stringify(p)})
        .then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); })
        .then(function(x){
            if(!x.ok){ if(x.j.test) renderTest(x.j.test); throw new Error(firstError(x.j)); }
            toast(id?'Mailbox updated':'Mailbox connected'); closeAccountModal(); loadAccounts();
        })
        .catch(function(e){ setAccStatus(e.message,'err'); })
        .then(function(){ btn.disabled=false; });
}

// ============================ conversations ================================
function manualSync(){
    if (inboxSyncRunning) return; inboxSyncRunning = true;
    var st=document.getElementById('mailSyncStatus'); if(st) st.textContent='Syncing…';
    var own = accountsData.filter(function(a){ return a.is_active && a.sync_enabled && (activeAccountId===null || a.id===activeAccountId); }); // every visible mailbox (read-only op)
    var jobs = own.map(function(a){ return fetch(MAIL_ROUTES.mailSync, {method:'POST', headers:jsonHeaders(), body:JSON.stringify({account:a.id})}).then(function(r){ return r.json(); }).catch(function(){ return null; }); });
    // legacy lead-reply import (uses the user's single legacy mailbox) stays available
    jobs.push(fetch(MAIL_ROUTES.sync, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){ return r.json(); }).catch(function(){ return null; }));
    Promise.all(jobs).then(function(results){
        var imported = results.reduce(function(n,r){ return n + ((r && r.imported) ? r.imported : 0); }, 0);
        loadFolders(); loadAccounts();
        if (isMailMode()) loadMailList(true); else loadChatList(true);
        if (activeChatId) fetchMessages();
        if (st) st.textContent = 'Synced ' + moment().format('h:mm A') + (imported ? ' · +' + imported + ' new' : '');
    }).catch(function(){ if(st) st.textContent='Sync failed'; }).then(function(){ inboxSyncRunning=false; });
}
function filterChats(){ if (isMailMode()) { clearTimeout(window.__mailSearchTimer); window.__mailSearchTimer = setTimeout(function(){ loadMailList(true); }, 350); } else { renderChatList(document.getElementById('chatSearch').value.toLowerCase()); } }
function loadChatList(force){
    if (!document.getElementById('chatListContainer')) { if (window.__chatsListPoll){ clearInterval(window.__chatsListPoll); window.__chatsListPoll=null; } return Promise.resolve(); }
    if (chatListLoading && !force) return Promise.resolve();
    if (chatListController) { try{ chatListController.abort(); }catch(e){} }
    chatListLoading = true; chatListController = new AbortController(); var controller = chatListController;
    var timeout = setTimeout(function(){ controller.abort(); }, 12000);
    var url = MAIL_ROUTES.list + (activeAccountId ? '?account='+activeAccountId : '');
    return fetch(url, {signal:controller.signal, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(function(res){ if(!res.ok) throw new Error('Chat list request failed ('+res.status+')'); return res.json(); })
        .then(function(data){
            chatsData = Array.isArray(data)?data:[]; chatListRetries=0;
            // Only rebuild the DOM when the data actually changed — avoids flicker and keeps
            // hover/selection stable while the 10s poll runs (Outlook-style).
            var sig = JSON.stringify(chatsData) + '|' + activeAccountId;
            var container = document.getElementById('chatListContainer');
            if (force || sig !== window.__chatsListSig || !container || !container.querySelector('.chat-item')) {
                window.__chatsListSig = sig;
                renderChatList(document.getElementById('chatSearch') ? document.getElementById('chatSearch').value.toLowerCase() : '');
            }
        })
        .catch(function(error){
            if (error.name==='AbortError') return;
            console.error('Chat list error:', error);
            if (chatListRetries < 3) { chatListRetries++; setTimeout(function(){ loadChatList(true); }, 800*chatListRetries); return; }
            if (!chatsData.length) { var c=document.getElementById('chatListContainer'); if(c) c.innerHTML = '<div class="chat-list-empty"><i class="fas fa-plug"></i>Conversations could not be loaded.<br><button type="button" class="reader-btn" style="margin-top:.8rem" onclick="chatListRetries=0;loadChatList(true)">Retry</button></div>'; }
        })
        .then(function(){ clearTimeout(timeout); if (chatListController===controller){ chatListController=null; chatListLoading=false; } });
}
function resumeChatList(){ if (document.hidden) return; loadChatList(true); if (activeChatId) fetchMessages(); }
function renderChatList(filter){
    filter = filter || '';
    var container = document.getElementById('chatListContainer'); if(!container) return;
    var filtered = chatsData.filter(function(chat){
        var hay = ((chat.client_name||'')+' '+(chat.client_email||'')+' '+(chat.subject||'')+' '+(chat.product_name||'')).toLowerCase();
        return hay.indexOf(filter) !== -1;
    });
    var unreadTotal = chatsData.reduce(function(n,c){ return n + (c.unread_count>0?1:0); }, 0);
    var cnt = document.getElementById('folderLeadsCount'); if(cnt){ cnt.textContent = unreadTotal; cnt.style.display = unreadTotal?'':'none'; }
    if (isMailMode()) return; // list pane is showing mail right now
    if (!filtered.length) { container.innerHTML = '<div class="chat-list-empty"><i class="fas fa-inbox"></i>'+(chatsData.length?'No conversations match your search.':'No conversations in this mailbox yet.')+'</div>'; return; }
    container.innerHTML = '';
    filtered.forEach(function(chat){
        var lm = chat.latest_message || null;
        var time = lm ? moment.utc(lm.created_at).local().calendar(null, {sameDay:'h:mm A', lastDay:'[Yesterday]', lastWeek:'ddd', sameElse:'MMM D'}) : '';
        var snippet = lm && lm.message_body ? lm.message_body.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim() : '';
        if (lm && lm.sender_type && lm.sender_type!=='client') snippet = 'You: ' + snippet;
        var acc = chat.mail_account_id ? accountsData.find(function(a){return a.id===chat.mail_account_id;}) : null;
        var accChip = acc && activeAccountId===null ? '<span class="chat-acc-chip"><span class="mail-acc-dot" style="width:7px;height:7px;flex-basis:7px;background:'+accColor(acc.id)+'"></span>'+esc(acc.display_name||acc.email_address)+'</span>' : '';
        var item = document.createElement('div');
        item.className = 'chat-item' + (activeChatId==chat.id?' active':'') + (chat.unread_count>0?' unread':'');
        item.onclick = function(){ selectChat(chat.id); };
        item.innerHTML = '<div class="chat-avatar">'+esc(initialsOf(chat.client_name))+'</div>'
            + '<div class="chat-info">'
            +   '<div class="chat-row1"><span class="chat-name">'+esc(chat.client_name||'Anonymous User')+'</span><span class="chat-time">'+esc(time)+'</span></div>'
            +   '<div class="chat-subject">'+esc(chat.subject || chat.product_name || 'General Inquiry')+(chat.unread_count>0?'<span class="chat-badge">'+chat.unread_count+'</span>':'')+'</div>'
            +   '<div class="chat-snippet">'+accChip+'<span style="min-width:0;overflow:hidden;text-overflow:ellipsis">'+esc(snippet || (chat.product_name||''))+'</span></div>'
            + '</div>';
        container.appendChild(item);
    });
}

function selectChat(id){
    activeChatId = id;
    var chat = chatsData.find(function(c){ return c.id==id; }); if(!chat) return;
    activeMailId = null; activeMail = null;
    toggleMobileView(true);
    document.getElementById('emptyState').style.display='none';
    document.getElementById('chatHeader').style.display='flex';
    document.getElementById('messagesContainer').style.display='flex';
    document.getElementById('inputArea').style.display='block';
    document.getElementById('mailReader').style.display='none';
    document.getElementById('readerStarBtn').style.display='none'; document.getElementById('readerUnreadBtn').style.display='none'; document.getElementById('readerReplyBtn').style.display=''; document.getElementById('viewLeadBtn').style.display='';
    document.getElementById('readerReplyAllBtn').style.display='none'; document.getElementById('readerForwardBtn').style.display='none';
    ['readerArchiveBtn','readerJunkBtn','readerTrashBtn','readerRestoreBtn','readerDeleteBtn','readerMoveSel'].forEach(function(id){ var b=document.getElementById(id); if(b) b.style.display='none'; });
    setComposerMode('lead');
    document.getElementById('activeName').innerText = chat.client_name || 'Anonymous User';
    document.getElementById('activeEmailHeader').innerText = chat.client_email || '';
    document.getElementById('activeSubject').innerText = chat.subject ? '· '+chat.subject : (chat.product_name ? '· '+chat.product_name : '');
    var ae = document.getElementById('activeEmail'); if(ae) ae.innerText = chat.client_email || '';
    document.getElementById('viewLeadBtn').href = '/crm/email/'+chat.id;
    document.getElementById('activeAvatar').innerText = initialsOf(chat.client_name);
    renderChatList(document.getElementById('chatSearch').value.toLowerCase());
    lastMsgId = 0; lastDisplayedDateStr = null;
    document.getElementById('messagesContainer').innerHTML = '<div class="chat-list-empty"><i class="fas fa-spinner fa-spin"></i></div>';
    fetchMessages(true);
    if (window.__chatMsgPoll) clearInterval(window.__chatMsgPoll);
    window.__chatMsgPoll = setInterval(fetchMessages, 5000);
}
function fetchMessages(initial){
    if (!activeChatId) return;
    if (!document.getElementById('messagesContainer')) { if(window.__chatMsgPoll){ clearInterval(window.__chatMsgPoll); window.__chatMsgPoll=null; } return; }
    var chatIdAtRequest = activeChatId;
    fetch('/crm/email/'+activeChatId+'/messages?last_id='+lastMsgId, {headers:{'X-Requested-With':'XMLHttpRequest'}, cache:'no-store'})
        .then(function(res){ return res.json(); })
        .then(function(messages){
            if (chatIdAtRequest !== activeChatId) return;
            var c = document.getElementById('messagesContainer');
            if (initial && c) c.innerHTML = '';
            if (messages.length > 0) {
                messages.forEach(function(msg){ appendMessage(msg); lastMsgId = msg.id; });
                scrollToBottom();
                loadChatList(true);
            } else if (initial && c && !c.children.length) {
                c.innerHTML = '<div class="chat-list-empty"><i class="fas fa-comment-slash"></i>No messages in this conversation yet.</div>';
            }
        }).catch(function(e){ console.error(e); });
}
function appendMessage(msg){
    var container = document.getElementById('messagesContainer'); if(!container) return;
    if (document.getElementById('msg-'+msg.id)) return;
    var ph = container.querySelector('.chat-list-empty'); if (ph) ph.remove();
    var msgDate = moment(msg.created_at).format('MMM D, YYYY');
    if (msgDate !== lastDisplayedDateStr) {
        lastDisplayedDateStr = msgDate;
        var today = moment().format('MMM D, YYYY'), yesterday = moment().subtract(1,'days').format('MMM D, YYYY');
        var displayDate = msgDate===today ? 'Today' : (msgDate===yesterday ? 'Yesterday' : msgDate);
        var dateHeader = document.createElement('div');
        dateHeader.style.cssText = 'text-align:center; margin:.6rem 0 .2rem; width:100%; align-self:center;';
        dateHeader.innerHTML = '<span style="background:var(--primary-soft); color:var(--primary-purple); padding:4px 12px; border-radius:99px; font-size:.72rem; font-weight:700;">'+displayDate+'</span>';
        container.appendChild(dateHeader);
    }
    var isSelf = msg.sender_type === 'admin' || msg.sender_type === 'agent';
    var time = moment(msg.created_at).format('h:mm A');
    var row = document.createElement('div'); row.id = 'msg-'+msg.id; row.className = 'msg-row'; row.style.alignItems = isSelf?'flex-end':'flex-start';
    var hasText = msg.message_body && msg.message_body.trim().length > 0;
    var hasAttachments = msg.attachments && msg.attachments.length > 0;
    var attachmentsHtml = '';
    if (hasAttachments) {
        attachmentsHtml = '<div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px;">';
        msg.attachments.forEach(function(path){
            var isImg = /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(path), url = window.location.origin+'/'+path;
            if (isImg) attachmentsHtml += '<a href="'+url+'" target="_blank" rel="noopener" style="display:block; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,.1);"><img src="'+url+'" style="max-width:280px; max-height:320px; display:block; object-fit:cover;"></a>';
            else attachmentsHtml += '<a href="'+url+'" target="_blank" rel="noopener" style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; text-decoration:none; color:#273449; font-size:.8rem;"><i class="fas fa-file" style="color:var(--primary-purple)"></i> '+esc(path.split('/').pop())+'</a>';
        });
        attachmentsHtml += '</div>';
    }
    var isOnlyImage = !hasText && hasAttachments && msg.attachments.every(function(p){ return /\.(jpg|jpeg|png|gif|webp)$/i.test(p); });
    var isTemplate = hasText && msg.message_body.indexOf('Custom Packaging Quote') !== -1;
    var bubbleStyle = isOnlyImage ? 'background:none; padding:0; box-shadow:none; border:0;' : (isTemplate ? 'background:transparent; padding:0; box-shadow:none; border:0; max-width:100%; color:inherit;' : '');
    var bodyHtml = hasText ? (isSelf ? msg.message_body : esc(msg.message_body).replace(/\n/g,'<br>')) : '';
    var bubbleClass = isTemplate ? '' : (isSelf ? 'msg-admin' : 'msg-client');
    var html = '';
    if (hasText) html += '<div class="msg-bubble '+bubbleClass+'" style="'+bubbleStyle+'">'+bodyHtml+'</div>';
    if (hasAttachments) html += '<div style="margin-top:'+(hasText?'5px':'0')+'; display:flex; flex-direction:column; align-items:'+(isSelf?'flex-end':'flex-start')+';">'+attachmentsHtml+'</div>';
    html += '<div class="msg-time">'+time+(isSelf ? ' • '+esc(msg.user ? msg.user.name : 'You') : '')+'</div>';
    row.innerHTML = html; container.appendChild(row);
}
function scrollToBottom(){ var c=document.getElementById('messagesContainer'); if(c) c.scrollTop = c.scrollHeight; }
function toggleMobileView(active){
    var app=document.getElementById('app'); if(!app) return;
    if (active) app.classList.add('chat-active');
    else { app.classList.remove('chat-active'); activeChatId=null; if(window.__chatMsgPoll){ clearInterval(window.__chatMsgPoll); window.__chatMsgPoll=null; } document.querySelectorAll('.chat-item.active').forEach(function(el){ el.classList.remove('active'); }); }
}
function toggleMailNav(open){ var app=document.getElementById('app'); if(app) app.classList.toggle('nav-open', !!open); }
function focusComposer(){ var ed = window.getReplyEditor && window.getReplyEditor(); if (ed) ed.focus(); else { var t=document.getElementById('messageInput'); if(t) t.focus(); } var ia=document.getElementById('inputArea'); if(ia) ia.scrollIntoView({behavior:'smooth', block:'end'}); }

// ============================== composer ===================================
window.getReplyEditor = function(){ return (window.tinymce && tinymce.get && tinymce.get('messageInput')) || null; };
function ensureTinymce(cb){
    if (window.tinymce && tinymce.init) return cb();
    if (!document.getElementById('tinymce-lib')) { var s=document.createElement('script'); s.id='tinymce-lib'; s.src=MAIL_TINYMCE_SRC; document.body.appendChild(s); }
    var tries=0; (function wait(){ if (window.tinymce && tinymce.init) return cb(); if (tries++ > 200) return; setTimeout(wait, 60); })();
}
function bootComposerEditor(){
    if (!document.getElementById('messageInput')) return;
    ensureTinymce(function(){
        if (!document.getElementById('messageInput')) return;
        if (tinymce.get('messageInput')) { try { tinymce.get('messageInput').remove(); } catch(e){} }
        tinymce.init({
            selector: '#messageInput', height: 220, menubar: false, branding: false, promotion: false, statusbar: false,
            convert_urls: false, paste_data_images: true, automatic_uploads: false,
            plugins: 'lists link image table code autolink',
            toolbar: 'undo redo | blocks | bold italic underline forecolor | alignleft aligncenter alignright | bullist numlist | link image table | removeformat | code',
            placeholder: 'Write your reply…',
            setup: function(editor){
                editor.on('drop', function(event){ var dt=event.dataTransfer; if (dt && dt.files && dt.files.length && window.addReplyAttachments){ event.preventDefault(); event.stopPropagation(); window.addReplyAttachments(Array.prototype.slice.call(dt.files)); } });
            },
            init_instance_callback: function(editor){ if (window.setupReplyDropZone) { try { window.setupReplyDropZone(editor.getBody()); } catch(e){} } }
        });
    });
}

// attachments (same helpers as the email page, window-scoped for inline handlers)
window.replyAttachmentFiles = [];
function syncReplyFileInput(){ var input=document.getElementById('fileInput'); if(!input) return; var t=new DataTransfer(); window.replyAttachmentFiles.forEach(function(f){ t.items.add(f); }); input.files = t.files; }
function renderReplyAttachments(){
    var tray=document.getElementById('attachment-tray'); if(!tray) return;
    (window.replyAttachmentObjectUrls||[]).forEach(function(u){ URL.revokeObjectURL(u); }); window.replyAttachmentObjectUrls=[];
    tray.innerHTML='';
    if (!window.replyAttachmentFiles.length) { tray.style.display='none'; return; }
    tray.style.display='flex';
    window.replyAttachmentFiles.forEach(function(file, index){
        var item=document.createElement('div'); item.className='attachment-chip';
        var url=URL.createObjectURL(file); window.replyAttachmentObjectUrls.push(url);
        var open=document.createElement('a'); open.className='attachment-chip-open'; open.href=url; open.target='_blank'; open.rel='noopener'; open.title='Open '+file.name;
        var preview; if ((file.type && file.type.indexOf('image/')===0) || /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file.name)) { preview=document.createElement('img'); preview.className='attachment-chip-preview'; preview.src=url; } else { preview=document.createElement('i'); preview.className='fas fa-file-alt'; preview.style.color='var(--primary-purple)'; }
        var name=document.createElement('span'); name.className='attachment-chip-name'; name.title=file.name; name.textContent=file.name;
        var remove=document.createElement('button'); remove.type='button'; remove.className='attachment-chip-remove'; remove.title='Remove file'; remove.innerHTML='<i class="fas fa-times"></i>'; remove.onclick=function(){ window.removeReplyAttachment(index); };
        open.append(preview, name); item.append(open, remove); tray.appendChild(item);
    });
}
window.addReplyAttachments = function(files, replace){
    var incoming = Array.from(files||[]); if (replace) window.replyAttachmentFiles = [];
    incoming.forEach(function(file){ var dup = window.replyAttachmentFiles.some(function(e){ return e.name===file.name && e.size===file.size && e.lastModified===file.lastModified; }); if(!dup) window.replyAttachmentFiles.push(file); });
    syncReplyFileInput(); renderReplyAttachments();
};
window.removeReplyAttachment = function(index){ window.replyAttachmentFiles.splice(index,1); syncReplyFileInput(); renderReplyAttachments(); };
window.handleFileSelect = function(input){ window.addReplyAttachments(input.files, true); };
window.setupReplyDropZone = function(target){
    if (!target || target.dataset.replyDropReady) return; target.dataset.replyDropReady='1';
    ['dragenter','dragover'].forEach(function(type){ target.addEventListener(type, function(e){ e.preventDefault(); e.stopPropagation(); var z=document.getElementById('replyDropOverlay'); if(z) z.classList.add('active'); }, true); });
    target.addEventListener('dragleave', function(e){ e.preventDefault(); e.stopPropagation(); var z=document.getElementById('replyDropOverlay'); if(z) z.classList.remove('active'); }, true);
    target.addEventListener('drop', function(e){ e.preventDefault(); e.stopImmediatePropagation(); var z=document.getElementById('replyDropOverlay'); if(z) z.classList.remove('active'); if (e.dataTransfer && e.dataTransfer.files.length) window.addReplyAttachments(e.dataTransfer.files); }, true);
};
(function(){ var zone=document.getElementById('replyDropZone'); if(zone) window.setupReplyDropZone(zone); var ov=document.getElementById('replyDropOverlay'); if(ov && !ov.dataset.ready){ ov.dataset.ready='1'; ov.addEventListener('drop', function(e){ e.preventDefault(); e.stopImmediatePropagation(); ov.classList.remove('active'); window.addReplyAttachments(e.dataTransfer.files); }, true); } })();

// ====================== mailbox reply / compose (Phase 4) ======================
var composerMode = 'lead';          // 'lead' | 'mail-reply' | 'mail-reply_all' | 'mail-forward' | 'mail-compose'
function ownSendableAccounts(){ return accountsData.filter(function(a){ return a.is_own && a.is_active; }); }
function toggleCcBcc(){ ['cfCcRow','cfBccRow'].forEach(function(id){ var r=document.getElementById(id); if(r) r.style.display = r.style.display==='none' ? 'flex' : 'none'; }); }
function fillFromSelect(selectedId){
    var sel = document.getElementById('cfFrom'); if(!sel) return;
    sel.innerHTML = ownSendableAccounts().map(function(a){ return '<option value="'+a.id+'" '+(a.id===selectedId?'selected':'')+'>'+esc(a.display_name ? a.display_name+' <'+a.email_address+'>' : a.email_address)+'</option>'; }).join('');
}
function setComposerMode(mode, opts){
    opts = opts || {};
    composerMode = mode;
    var fields = document.getElementById('composeFields'); if(!fields) return; // admin: no composer rendered
    var isMail = mode.indexOf('mail-') === 0;
    fields.style.display = isMail ? 'block' : 'none';
    var toLine = document.querySelector('.composer-to'); if (toLine) toLine.style.display = isMail ? 'none' : '';
    if (!isMail) return;
    fillFromSelect(opts.accountId || null);
    var fromSel = document.getElementById('cfFrom'); if (fromSel) fromSel.disabled = mode !== 'mail-compose';
    document.getElementById('cfTo').value = opts.to || '';
    document.getElementById('cfCc').value = opts.cc || '';
    document.getElementById('cfBcc').value = '';
    document.getElementById('cfCcRow').style.display = opts.cc ? 'flex' : 'none';
    document.getElementById('cfBccRow').style.display = 'none';
    document.getElementById('cfSubject').value = opts.subject || '';
    document.getElementById('cfModeLabel').textContent = {'mail-reply':'Reply','mail-reply_all':'Reply all','mail-forward':'Forward','mail-compose':'New message'}[mode] || '';
    var ia = document.getElementById('inputArea'); if (ia) ia.style.display = 'block';
    var ed = window.getReplyEditor && window.getReplyEditor(); if (ed) ed.setContent(''); var ta=document.getElementById('messageInput'); if (ta) ta.value='';
    window.replyAttachmentFiles = []; syncReplyFileInput(); renderReplyAttachments();
}
function stripRe(s){ return (s||'').replace(/^\s*((re|fw|fwd)\s*:\s*)+/i,''); }
function replyActive(mode){
    if (!activeMail) { focusComposer(); return; }
    var acc = accountsData.find(function(a){ return a.id===activeMail.account_id; });
    if (!acc || !acc.is_own || !acc.is_active) { toast('Only the mailbox owner can reply from this mailbox', 'error'); return; }
    var own = (acc.email_address||'').toLowerCase();
    var to = activeMail.is_outgoing ? (activeMail.to||[]).map(function(a){return a.email;}).join(', ') : (activeMail.reply_to || activeMail.from_email || '');
    var cc = '';
    if (mode === 'reply_all') {
        var others = (activeMail.to||[]).concat(activeMail.cc||[]).map(function(a){return (a.email||'').toLowerCase();}).filter(function(e){ return e && e!==own && e!==(to||'').toLowerCase(); });
        cc = Array.from(new Set(others)).join(', ');
    }
    setComposerMode('mail-'+mode, { accountId: acc.id, to: mode==='forward' ? '' : to, cc: cc, subject: (mode==='forward' ? 'Fwd: ' : 'Re: ') + stripRe(activeMail.subject) });
    setTimeout(function(){ var f = document.getElementById(mode==='forward' ? 'cfTo' : 'messageInput'); if (mode==='forward' && f) f.focus(); else focusComposer(); }, 50);
}
function startCompose(){
    var own = ownSendableAccounts(); if (!own.length) { openAccountModal(); return; }
    activeMailId = null; activeMail = null; activeChatId = null;
    if (window.__chatMsgPoll){ clearInterval(window.__chatMsgPoll); window.__chatMsgPoll=null; }
    toggleMobileView(true);
    document.getElementById('emptyState').style.display='none';
    document.getElementById('chatHeader').style.display='flex';
    document.getElementById('messagesContainer').style.display='none';
    document.getElementById('mailReader').style.display='none';
    document.getElementById('activeName').innerText = 'New message';
    document.getElementById('activeEmailHeader').innerText = '';
    document.getElementById('activeSubject').innerText = '';
    document.getElementById('activeAvatar').innerHTML = '<i class="fas fa-pen"></i>';
    ['readerStarBtn','readerUnreadBtn','readerReplyBtn','readerReplyAllBtn','readerForwardBtn','viewLeadBtn','readerArchiveBtn','readerJunkBtn','readerTrashBtn','readerRestoreBtn','readerDeleteBtn','readerMoveSel'].forEach(function(id){ var b=document.getElementById(id); if(b) b.style.display='none'; });
    var def = own.find(function(a){ return a.is_default; }) || own[0];
    setComposerMode('mail-compose', { accountId: def.id, to: '', subject: '' });
    setTimeout(function(){ var f=document.getElementById('cfTo'); if(f) f.focus(); }, 50);
}
function sendMailFromComposer(form){
    var ed = window.getReplyEditor && window.getReplyEditor(); if (ed) ed.save();
    var input = document.getElementById('messageInput');
    var bodyHtml = input.value || '';
    var plain = bodyHtml.replace(/<[^>]+>/g,'').replace(/&nbsp;/g,' ').trim();
    var to = document.getElementById('cfTo').value.trim();
    var files = document.getElementById('fileInput').files;
    if ((composerMode==='mail-compose' || composerMode==='mail-forward') && !to) { toast('Add a recipient', 'error'); document.getElementById('cfTo').focus(); return; }
    if (!plain && !files.length) { toast('Write a message or attach a file', 'error'); return; }
    var fd = new FormData();
    fd.append('_token', MAIL_CSRF);
    fd.append('to', to); fd.append('cc', document.getElementById('cfCc').value.trim()); fd.append('bcc', document.getElementById('cfBcc').value.trim());
    fd.append('subject', document.getElementById('cfSubject').value.trim()); fd.append('body', bodyHtml);
    Array.prototype.forEach.call(files, function(f){ fd.append('attachments[]', f); });
    var url;
    if (composerMode === 'mail-compose') { fd.append('account_id', document.getElementById('cfFrom').value); url = '{{ route("crm.mail.compose") }}'; }
    else { if (!activeMail) return; fd.append('mode', composerMode.replace('mail-','')); url = MAIL_ROUTES.messages + '/' + activeMail.id + '/reply'; }
    var btn = document.getElementById('sendBtn'), txt = document.getElementById('sendBtnText');
    btn.disabled = true; txt.textContent = 'Sending…';
    fetch(url, {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); })
        .then(function(x){
            if (!x.ok || !x.j.success) { var msg = x.j.message || (x.j.errors ? Object.values(x.j.errors)[0][0] : 'Could not send'); throw new Error(msg); }
            toast('Sent from ' + (ownSendableAccounts().find(function(a){ return a.id===x.j.message.account_id; })||{}).email_address);
            if (ed) ed.setContent(''); input.value = '';
            window.replyAttachmentFiles = []; syncReplyFileInput(); renderReplyAttachments();
            loadFolders();
            if (composerMode === 'mail-compose') { closeReader(); if (activeFolder==='sent') loadMailList(true); }
            else if (activeMail) { openMail(activeMail.id); }
        })
        .catch(function(e){ toast(e.message, 'error'); })
        .then(function(){ btn.disabled = false; txt.textContent = 'Send'; });
}

// sending
var chatForm = document.getElementById('chatForm');
if (chatForm) {
    chatForm.onsubmit = function(e){
        e.preventDefault();
        if (composerMode.indexOf('mail-') === 0) { sendMailFromComposer(e.target); return; }
        var ed = window.getReplyEditor && window.getReplyEditor(); if (ed) ed.save();
        var input = document.getElementById('messageInput');
        var body = (input.value||'').replace(/<[^>]+>/g,'').replace(/&nbsp;/g,' ').trim();
        if (!body && !document.getElementById('fileInput').files.length) return;
        pendingChatForm = e.target;
        var chat = chatsData.find(function(c){ return c.id==activeChatId; });
        document.getElementById('modalSubject').value = chat ? ('Re: ' + (chat.subject || chat.product_name || 'Your Inquiry')) : 'Re: Your Inquiry';
        document.getElementById('modalCc').value = document.getElementById('chatCcField').value || '';
        document.getElementById('modalBcc').value = document.getElementById('chatBccField').value || '';
        openEmailMetaModal();
    };
}
function openEmailMetaModal(){ document.getElementById('emailMetaModal').classList.add('open'); setTimeout(function(){ document.getElementById('modalSubject').focus(); }, 30); }
function closeEmailMetaModal(){ document.getElementById('emailMetaModal').classList.remove('open'); pendingChatForm = null; }
function submitEmailMeta(){
    var subject = document.getElementById('modalSubject').value.trim();
    if (!subject) { alert('Email subject is required.'); return; }
    if (!pendingChatForm || !activeChatId) return;
    document.getElementById('chatEmailSubject').value = subject;
    document.getElementById('chatCcField').value = document.getElementById('modalCc').value.trim();
    document.getElementById('chatBccField').value = document.getElementById('modalBcc').value.trim();
    var form = pendingChatForm, btn = document.getElementById('sendBtn'), txt = document.getElementById('sendBtnText');
    btn.disabled = true; txt.textContent = 'Sending…';
    fetch('/crm/email/'+activeChatId+'/message', {method:'POST', body:new FormData(form), headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){ return r.json(); })
        .then(function(result){
            if (result.success) {
                appendMessage(result.data); if (result.data && result.data.id) lastMsgId = Math.max(lastMsgId, result.data.id); scrollToBottom();
                var ed = window.getReplyEditor && window.getReplyEditor(); if (ed) ed.setContent(''); document.getElementById('messageInput').value='';
                window.replyAttachmentFiles = []; syncReplyFileInput(); renderReplyAttachments();
                closeEmailMetaModal(); toast('Reply sent'); loadChatList(true);
            } else { alert('Error: ' + (result.message||'Could not send')); }
        })
        .catch(function(err){ console.error(err); alert('Failed to send message. Please try again.'); })
        .then(function(){ btn.disabled=false; txt.textContent='Send'; });
}

// ================================ boot =====================================
loadAccounts().then(function(){
    // Default view: real mail when a mailbox is connected (or shared), otherwise lead conversations.
    activeFolder = accountsData.length ? 'inbox' : 'leads';
    document.querySelectorAll('.mail-nav-item[data-folder]').forEach(function(el){ el.classList.toggle('active', el.dataset.folder===activeFolder); });
    var t = document.getElementById('listTitle'); if (t) t.textContent = activeFolder==='leads' ? 'Lead conversations' : 'Inbox';
    var search = document.getElementById('chatSearch'); if (search) search.placeholder = isMailMode() ? 'Search mail…' : 'Search conversations…';
    loadFolders();
    loadChatList(true);           // keeps the Leads badge current even in mail mode
    if (isMailMode()) loadMailList(true);
});
bootComposerEditor();
if (window.__chatsListPoll) clearInterval(window.__chatsListPoll);
window.__chatsListPoll = setInterval(function(){ loadChatList(false); }, 3000);
if (window.__mailListPoll) clearInterval(window.__mailListPoll);
window.__mailListPoll = setInterval(function(){
    if (!document.getElementById('chatListContainer')) { clearInterval(window.__mailListPoll); window.__mailListPoll=null; return; }
    if (document.hidden) return;
    loadFolders();
    if (isMailMode() && mailPage === 1 && !(document.getElementById('chatSearch')||{}).value) {
        // quiet refresh of page 1; renderMailList keeps the active row
        var before = JSON.stringify(mailMessages.map(function(m){ return [m.id, m.is_read, m.is_starred]; }));
        fetch(MAIL_ROUTES.messages+'?'+new URLSearchParams(Object.assign({page:1}, activeAccountId?{account:activeAccountId}:{}, activeFolder.indexOf('custom:')===0?{folder:activeFolder.split(':')[1]}:{type:activeFolder})).toString(), {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function(r){ return r.json(); }).then(function(d){ var inc = d.messages||[]; var after = JSON.stringify(inc.map(function(m){ return [m.id, m.is_read, m.is_starred]; })); if (after !== before) { mailMessages = inc; mailHasMore = !!d.has_more; renderMailList(); } }).catch(function(){});
    }
}, 10000);
if (!window.__chatsListenersBound) {
    window.__chatsListenersBound = true;
    document.addEventListener('visibilitychange', function(){ if (!document.hidden && typeof resumeChatList==='function') resumeChatList(); });
    window.addEventListener('pageshow', function(){ if (typeof resumeChatList==='function') resumeChatList(); });
    document.addEventListener('keydown', function(e){ if (e.key==='Escape'){ var m=document.getElementById('accountModal'); if(m) m.classList.remove('open'); var em=document.getElementById('emailMetaModal'); if(em) em.classList.remove('open'); } });
}
</script>
@endsection
