@extends('crm.layout')
@section('title', 'Team Chat')

@section('styles')
    .main-area { padding: 0 !important; overflow: hidden !important; background: #fff; display: flex; flex-direction: column; }
    .top-bar { display: none !important; }

    .tc-app { --tc-brand: var(--primary-purple, #f45a24); --tc-ink: #0f172a; --tc-muted: #64748b; --tc-faint: #94a3b8; --tc-line: #e8ecf1; --tc-well: #f4f6f9; display: flex; height: 100vh; width: 100%; background: #fff; color: var(--tc-ink); }

    /* Conversation list */
    .tc-side { width: 350px; flex-shrink: 0; border-right: 1px solid var(--tc-line); display: flex; flex-direction: column; background: #fff; min-height: 0; }
    .tc-side-head { padding: 1.1rem 1.15rem .8rem; }
    .tc-title { display: flex; align-items: center; gap: .6rem; margin-bottom: .85rem; }
    .tc-title h2 { margin: 0; font-size: 1.25rem; font-weight: 800; letter-spacing: -.02em; color: var(--tc-ink); }
    .tc-title .menu-toggle { margin: 0; }
    .tc-search { display: flex; align-items: center; gap: .55rem; background: var(--tc-well); border: 1.5px solid transparent; border-radius: 11px; padding: .55rem .8rem; }
    .tc-search:focus-within, .tc-msg-search:focus-within { background: #fff; border-color: var(--tc-brand); box-shadow: 0 0 0 3px var(--primary-soft, #fff4ef); }
    .tc-search:focus-within i { color: var(--tc-brand); }
    .tc-search i { color: var(--tc-faint); font-size: .82rem; }
    .tc-search input { border: none; background: none; outline: none; width: 100%; font-size: .86rem; font-family: inherit; color: var(--tc-ink); }
    .tc-filters { display: flex; gap: .35rem; margin-top: .75rem; }
    .tc-filter { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .3rem; border: none; background: none; border-radius: 9px; padding: .42rem .2rem; font-size: .78rem; font-weight: 700; color: var(--tc-muted); cursor: pointer; font-family: inherit; }
    .tc-filter:hover { background: var(--tc-well); }
    .tc-filter.active { background: var(--tc-brand); color: #fff; }
    .tc-filter small { font-size: .66rem; font-weight: 800; background: var(--tc-brand); color: #fff; border-radius: 99px; padding: 0 .36rem; line-height: 1.4; }
    .tc-filter.active small { background: #fff; color: var(--tc-brand); }
    .tc-list { flex: 1; overflow-y: auto; border-top: 1px solid var(--tc-line); min-height: 0; }
    .tc-list-empty { padding: 2.5rem 1.5rem; text-align: center; color: var(--tc-faint); font-size: .85rem; }
    .tc-list-empty i { display: block; font-size: 1.6rem; margin-bottom: .6rem; color: #cbd5e1; }

    .tc-item { display: flex; gap: .75rem; align-items: center; padding: .72rem 1.15rem; cursor: pointer; border-left: 3px solid transparent; }
    .tc-item:hover { background: #fafbfc; }
    .tc-item.active { background: var(--primary-soft, #fff4ef); border-left-color: var(--tc-brand); }
    .tc-avatar { position: relative; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: .88rem; flex-shrink: 0; color: #fff; }
    .tc-dot { position: absolute; right: 0; bottom: 0; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #fff; background: #cbd5e1; }
    .tc-dot.online { background: #22c55e; } .tc-dot.recent { background: #f59e0b; }
    .tc-item-body { flex: 1; min-width: 0; }
    .tc-item-top, .tc-item-bottom { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .tc-item-name { font-weight: 700; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tc-item-time { font-size: .68rem; color: var(--tc-faint); flex-shrink: 0; }
    .tc-item-preview { font-size: .78rem; color: var(--tc-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: .12rem; }
    .tc-item-preview i { font-size: .7rem; margin-right: .2rem; }
    .tc-item-preview.none { color: var(--tc-faint); }
    .tc-item.unread .tc-item-name { font-weight: 800; }
    .tc-item.unread .tc-item-preview { color: var(--tc-ink); font-weight: 600; }
    .tc-item.unread .tc-item-time { color: var(--tc-brand); font-weight: 700; }
    .tc-count { background: var(--tc-brand); color: #fff; font-size: .66rem; font-weight: 800; border-radius: 99px; min-width: 18px; height: 18px; padding: 0 .3rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

    /* Open conversation */
    .tc-main { flex: 1; min-width: 0; display: flex; flex-direction: column; background: var(--tc-well); position: relative; }
    .tc-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--tc-muted); text-align: center; padding: 2rem; }
    .tc-empty-icon { width: 76px; height: 76px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--tc-brand); margin-bottom: 1rem; box-shadow: 0 8px 24px -12px rgba(15,23,42,.25); }
    .tc-empty h3 { margin: 0 0 .3rem; font-size: 1.05rem; font-weight: 800; color: var(--tc-ink); }
    .tc-empty p { margin: 0; font-size: .85rem; }

    .tc-head { display: none; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem 1.2rem; background: #fff; border-bottom: 1px solid var(--tc-line); }
    .tc-head-person { display: flex; align-items: center; gap: .7rem; min-width: 0; }
    .tc-head-name { font-weight: 800; font-size: .98rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tc-head-status { font-size: .74rem; color: var(--tc-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tc-head-status.online { color: #16a34a; font-weight: 600; }
    .tc-head-actions { display: flex; align-items: center; gap: .3rem; flex-shrink: 0; }
    .tc-icon-btn { width: 38px; height: 38px; border: none; border-radius: 50%; background: none; color: var(--tc-muted); font-size: .95rem; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .tc-icon-btn:hover { background: var(--tc-well); color: var(--tc-ink); }
    .tc-icon-btn.call { color: var(--tc-brand); }
    .tc-icon-btn.call:hover { background: var(--primary-soft, #fff4ef); color: var(--tc-brand); }
    .tc-back { display: none; }
    .tc-msg-search { display: none; align-items: center; gap: .5rem; background: var(--tc-well); border: 1.5px solid transparent; border-radius: 99px; padding: .4rem .8rem; }
    .tc-msg-search.show { display: flex; }
    .tc-msg-search input { border: none; background: none; outline: none; font-size: .82rem; width: 160px; font-family: inherit; }

    .tc-messages { display: none; flex: 1; min-height: 0; overflow-y: auto; flex-direction: column; gap: .2rem; padding: 1.1rem clamp(.8rem, 4vw, 3rem); }
    .tc-date { align-self: center; margin: .9rem 0 .5rem; background: #fff; color: var(--tc-muted); font-size: .7rem; font-weight: 700; padding: .25rem .8rem; border-radius: 99px; box-shadow: 0 1px 2px rgba(15,23,42,.06); }
    .tc-row { display: flex; flex-direction: column; align-items: flex-start; margin-top: .3rem; }
    .tc-row.mine { align-items: flex-end; }
    .tc-bubble { position: relative; max-width: min(68%, 560px); background: #fff; padding: .5rem .72rem .35rem; border-radius: 14px 14px 14px 4px; font-size: .88rem; line-height: 1.45; box-shadow: 0 1px 2px rgba(15,23,42,.07); word-break: break-word; }
    .tc-row.mine .tc-bubble { background: var(--tc-brand); color: #fff; border-radius: 14px 14px 4px 14px; }
    .tc-bubble a { color: inherit; text-decoration: underline; }
    .tc-forwarded { font-size: .68rem; opacity: .75; font-style: italic; margin-bottom: .2rem; }
    .tc-meta { display: flex; justify-content: flex-end; align-items: center; gap: .3rem; font-size: .63rem; color: var(--tc-faint); margin-top: .2rem; white-space: nowrap; }
    .tc-row.mine .tc-meta { color: rgba(255,255,255,.8); }
    .tc-ticks.read { color: #bbf7d0; }
    .tc-more { position: absolute; top: 3px; right: 3px; width: 24px; height: 24px; border: none; border-radius: 50%; background: rgba(15,23,42,.08); color: inherit; cursor: pointer; opacity: 0; font-size: .7rem; display: flex; align-items: center; justify-content: center; }
    .tc-bubble:hover .tc-more, .tc-more:focus { opacity: 1; }
    .tc-row.mine .tc-more { background: rgba(0,0,0,.2); }
    .tc-menu { position: absolute; top: 28px; right: 3px; z-index: 30; min-width: 150px; background: #fff; color: var(--tc-ink); border-radius: 11px; padding: .3rem; box-shadow: 0 14px 34px -8px rgba(15,23,42,.3); display: none; }
    .tc-menu.show { display: block; }
    .tc-menu.up { top: auto; bottom: 28px; }
    .tc-menu button { display: flex; align-items: center; gap: .55rem; width: 100%; border: none; background: none; padding: .48rem .6rem; border-radius: 8px; cursor: pointer; font-size: .8rem; font-weight: 600; color: inherit; text-align: left; font-family: inherit; }
    .tc-menu button:hover { background: var(--tc-well); }
    .tc-menu button.danger { color: #dc2626; }

    .tc-media { display: block; max-width: 100%; max-height: 320px; border-radius: 10px; cursor: zoom-in; background: rgba(15,23,42,.05); }
    video.tc-media { cursor: default; width: 100%; }
    .tc-audio { display: block; width: 270px; max-width: 100%; margin: .15rem 0; }
    .tc-file { display: flex; align-items: center; gap: .6rem; background: rgba(15,23,42,.05); border-radius: 10px; padding: .5rem .6rem; min-width: 220px; max-width: 100%; }
    .tc-row.mine .tc-file { background: rgba(255,255,255,.18); }
    .tc-file-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; font-size: .95rem; }
    .tc-file-info { flex: 1; min-width: 0; }
    .tc-file-name { font-weight: 700; font-size: .8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; text-decoration: none !important; }
    .tc-file-sub { font-size: .67rem; opacity: .75; }
    .tc-file-dl { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; text-decoration: none !important; }
    .tc-file-dl:hover { background: rgba(15,23,42,.1); }
    .tc-text { margin-top: 0; padding-right: 1.1rem; }
    .tc-media + .tc-text, .tc-file + .tc-text, .tc-audio + .tc-text { margin-top: .35rem; }

    .tc-call-row { align-self: center; display: inline-flex; align-items: center; gap: .5rem; margin: .5rem 0; background: #fff; border-radius: 99px; padding: .32rem .8rem; font-size: .76rem; font-weight: 600; color: var(--tc-muted); box-shadow: 0 1px 2px rgba(15,23,42,.06); }
    .tc-call-row.missed { color: #dc2626; }
    .tc-call-row small { color: var(--tc-faint); font-weight: 500; }
    .tc-call-row button { border: none; background: none; color: var(--tc-brand); font-weight: 800; font-size: .72rem; cursor: pointer; padding: 0 0 0 .3rem; font-family: inherit; }

    /* Composer */
    .tc-composer { display: none; background: #fff; border-top: 1px solid var(--tc-line); padding: .85rem clamp(.8rem, 3vw, 1.6rem); }
    .tc-edit-bar { display: none; align-items: center; justify-content: space-between; font-size: .78rem; color: var(--tc-muted); padding: 0 .2rem .45rem; }
    .tc-edit-bar button { border: none; background: none; color: #dc2626; font-weight: 800; cursor: pointer; font-family: inherit; }
    .tc-tray { display: none; flex-wrap: wrap; gap: .5rem; padding-bottom: .55rem; max-height: 170px; overflow-y: auto; }
    .tc-tray-item { position: relative; display: flex; align-items: center; gap: .5rem; background: var(--tc-well); border-radius: 10px; padding: .35rem 1.7rem .35rem .4rem; max-width: 230px; font-size: .74rem; }
    .tc-tray-thumb { width: 38px; height: 38px; border-radius: 7px; object-fit: cover; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; }
    .tc-tray-name { font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tc-tray-size { color: var(--tc-faint); font-size: .66rem; }
    .tc-tray-remove { position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; border: none; border-radius: 50%; background: rgba(15,23,42,.1); cursor: pointer; font-size: .62rem; display: flex; align-items: center; justify-content: center; color: var(--tc-ink); }
    .tc-compose-row { display: flex; align-items: flex-end; gap: .45rem; }
    .tc-attach { position: relative; flex-shrink: 0; }
    .tc-attach-menu { position: absolute; left: 0; bottom: 58px; z-index: 40; background: #fff; border-radius: 13px; padding: .35rem; min-width: 205px; box-shadow: 0 14px 34px -8px rgba(15,23,42,.3); display: none; }
    .tc-attach-menu.show { display: block; }
    .tc-attach-menu button { display: flex; align-items: center; gap: .65rem; width: 100%; border: none; background: none; border-radius: 9px; padding: .5rem .6rem; font-size: .83rem; font-weight: 600; color: var(--tc-ink); cursor: pointer; font-family: inherit; text-align: left; }
    .tc-attach-menu button:hover { background: var(--tc-well); }
    .tc-attach-menu i { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: .75rem; }
    .tc-input { box-sizing: border-box; min-height: 58px; flex: 1; min-width: 0; border: 1.5px solid #d5dbe3; outline: none; resize: none; background: #fff; border-radius: 16px; padding: 1rem 1.2rem; font-size: 1rem; line-height: 1.45; max-height: 190px; font-family: inherit; color: var(--tc-ink); }
    .tc-input::placeholder { color: var(--tc-muted); }
    #teamMsgInput:focus { border-color: var(--tc-brand); box-shadow: 0 0 0 3px var(--primary-soft, #fff4ef); }
    .tc-attach .tc-icon-btn { width: 48px; height: 48px; font-size: 1.15rem; margin-bottom: 5px; }
    .tc-send { width: 48px; height: 48px; margin-bottom: 5px; border: none; border-radius: 50%; background: var(--tc-brand); color: #fff; cursor: pointer; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; }
    .tc-send:disabled { opacity: .55; cursor: default; }
    .tc-drop { position: absolute; inset: 0; z-index: 50; display: none; align-items: center; justify-content: center; flex-direction: column; gap: .5rem; background: rgba(255,255,255,.93); border: 3px dashed var(--tc-brand); color: var(--tc-brand); font-weight: 800; pointer-events: none; }
    .tc-drop.show { display: flex; }
    .tc-drop i { font-size: 2rem; }

    .tc-modal { position: fixed; inset: 0; z-index: 10500; display: none; align-items: center; justify-content: center; background: rgba(15,23,42,.5); padding: 1rem; }
    .tc-modal.show { display: flex; }
    .tc-modal-box { background: #fff; border-radius: 18px; width: 420px; max-width: 100%; max-height: 82vh; display: flex; flex-direction: column; overflow: hidden; }
    .tc-modal-head { display: flex; align-items: center; justify-content: space-between; padding: .95rem 1.15rem; font-weight: 800; }
    .tc-modal-body { flex: 1; overflow-y: auto; padding: 0 .6rem; min-height: 0; }
    .tc-modal-foot { display: flex; justify-content: flex-end; gap: .5rem; padding: .8rem 1.15rem; border-top: 1px solid var(--tc-line); }
    .tc-modal-foot button { border: none; border-radius: 10px; padding: .55rem 1rem; font-weight: 700; font-size: .84rem; cursor: pointer; font-family: inherit; background: var(--tc-well); color: var(--tc-ink); }
    .tc-modal-foot button.primary { background: var(--tc-brand); color: #fff; }
    .tc-forward-item { display: flex; align-items: center; gap: .65rem; padding: .5rem .55rem; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: .87rem; }
    .tc-forward-item:hover { background: var(--tc-well); }
    .tc-forward-item .tc-avatar { width: 34px; height: 34px; font-size: .72rem; }
    .tc-forward-item > span { flex: 1; min-width: 0; }
    .tc-forward-item > i { color: #cbd5e1; font-size: 1.05rem; }
    .tc-forward-item.selected > i { color: var(--tc-brand); }
    .tc-lightbox { position: fixed; inset: 0; z-index: 10600; display: none; align-items: center; justify-content: center; background: rgba(15,23,42,.9); padding: 1.5rem; cursor: zoom-out; }
    .tc-lightbox.show { display: flex; }
    .tc-lightbox img { max-width: 100%; max-height: 100%; border-radius: 8px; }

    @media (max-width: 768px) {
        .tc-side { width: 100%; }
        .tc-main { display: none; }
        .tc-app.chat-active .tc-side { display: none; }
        .tc-app.chat-active .tc-main { display: flex; }
        .tc-back { display: inline-flex; }
        .tc-bubble { max-width: 86%; }
        .tc-more { opacity: 1; }
        .tc-msg-search input { width: 100px; }
    }
@endsection

@section('content')
    <div class="tc-app" id="teamChatApp">
        <aside class="tc-side">
            <div class="tc-side-head">
                <div class="tc-title">
                    <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
                    <h2>Team Chat</h2>
                </div>
                <div class="tc-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="agentSearch" placeholder="Search teammates…" oninput="renderAgentList()" autocomplete="off">
                </div>
                <div class="tc-filters" role="tablist" aria-label="Filter conversations">
                    <button type="button" class="tc-filter" data-filter="all" onclick="setChatFilter('all')">All</button>
                    <button type="button" class="tc-filter" data-filter="unread" onclick="setChatFilter('unread')">Unread</button>
                    <button type="button" class="tc-filter" data-filter="read" onclick="setChatFilter('read')">Read</button>
                    <button type="button" class="tc-filter" data-filter="recent" onclick="setChatFilter('recent')">Recent</button>
                </div>
            </div>
            <div class="tc-list" id="agentListContainer"></div>
        </aside>

        <section class="tc-main" id="tcMain">
            <div class="tc-empty" id="emptyState">
                <div class="tc-empty-icon"><i class="fas fa-comments"></i></div>
                <h3>Select a teammate</h3>
                <p>Send messages and files, or start a voice or video call.</p>
            </div>

            <header class="tc-head" id="chatHeader">
                <div class="tc-head-person">
                    <button type="button" class="tc-icon-btn tc-back" onclick="closeConversation()" aria-label="Back"><i class="fas fa-arrow-left"></i></button>
                    <div class="tc-avatar" id="activeAvatar"></div>
                    <div style="min-width:0">
                        <div class="tc-head-name" id="activeName"></div>
                        <div class="tc-head-status" id="activeStatus"></div>
                    </div>
                </div>
                <div class="tc-head-actions">
                    <div class="tc-msg-search" id="msgSearch">
                        <i class="fas fa-search" style="color:#94a3b8;font-size:.78rem"></i>
                        <input type="text" id="msgSearchInput" placeholder="Search in chat…" oninput="filterMessages()" autocomplete="off">
                    </div>
                    <button type="button" class="tc-icon-btn" onclick="toggleMsgSearch()" title="Search in chat" aria-label="Search in chat"><i class="fas fa-search"></i></button>
                    <button type="button" class="tc-icon-btn call" id="voiceCallBtn" onclick="startCall('audio')" title="Voice call" aria-label="Voice call"><i class="fas fa-phone"></i></button>
                    <button type="button" class="tc-icon-btn call" id="videoCallBtn" onclick="startCall('video')" title="Video call" aria-label="Video call"><i class="fas fa-video"></i></button>
                </div>
            </header>

            <div class="tc-messages" id="messagesContainer"></div>

            <footer class="tc-composer" id="inputArea">
                <div class="tc-edit-bar" id="editModeBar">
                    <span><i class="fas fa-pen"></i> Editing message</span>
                    <button type="button" onclick="cancelEdit()">Cancel</button>
                </div>
                <div class="tc-tray" id="attachmentTray"></div>
                <div class="tc-compose-row">
                    <div class="tc-attach">
                        <button type="button" class="tc-icon-btn" onclick="toggleAttachMenu(event)" title="Attach" aria-label="Attach files"><i class="fas fa-paperclip"></i></button>
                        <div class="tc-attach-menu" id="attachMenu">
                            <button type="button" onclick="document.getElementById('filesMedia').click()"><i class="fas fa-image" style="background:#7c3aed"></i> Photos &amp; videos</button>
                            <button type="button" onclick="document.getElementById('filesDocs').click()"><i class="fas fa-file-pdf" style="background:#dc2626"></i> PDF &amp; documents</button>
                            <button type="button" onclick="document.getElementById('filesAny').click()"><i class="fas fa-folder-open" style="background:#0891b2"></i> Any file</button>
                        </div>
                    </div>
                    <input type="file" id="filesMedia" accept="image/*,video/*" multiple hidden onchange="addFiles(this.files); this.value=''">
                    <input type="file" id="filesDocs" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.rtf,.odt,.ods" multiple hidden onchange="addFiles(this.files); this.value=''">
                    <input type="file" id="filesAny" multiple hidden onchange="addFiles(this.files); this.value=''">
                    <textarea class="tc-input" id="teamMsgInput" rows="1" placeholder="Write a message…"></textarea>
                    <button type="button" class="tc-send" id="sendBtn" onclick="sendTeamMessage()" title="Send" aria-label="Send"><i class="fas fa-paper-plane" id="sendBtnIcon"></i></button>
                </div>
            </footer>

            <div class="tc-drop" id="dropOverlay"><i class="fas fa-cloud-upload-alt"></i> Drop files to attach</div>
        </section>
    </div>

    <div class="tc-modal" id="forwardModal" onclick="closeForwardModal()">
        <div class="tc-modal-box" onclick="event.stopPropagation()">
            <div class="tc-modal-head">Forward message
                <button type="button" class="tc-icon-btn" onclick="closeForwardModal()" aria-label="Close"><i class="fas fa-times"></i></button>
            </div>
            <div style="padding:0 1.15rem .6rem">
                <div class="tc-search"><i class="fas fa-search"></i><input type="text" id="forwardSearchInput" placeholder="Search teammates…" oninput="renderForwardList()" autocomplete="off"></div>
            </div>
            <div class="tc-modal-body" id="forwardList"></div>
            <div class="tc-modal-foot">
                <button type="button" onclick="closeForwardModal()">Cancel</button>
                <button type="button" class="primary" id="confirmForwardBtn" onclick="confirmForward()">Forward</button>
            </div>
        </div>
    </div>

    <div class="tc-lightbox" id="imageLightbox" onclick="this.classList.remove('show')"><img id="lightboxImg" alt=""></div>
@endsection

@section('scripts')
    <script>
        // var (not let/const) for every top-level declaration: the CRM AJAX navigator
        // re-executes this inline script on each partial visit to Team Chat, and
        // top-level let/const would throw "already declared" on the 2nd visit.
        var activeAgentId = null;
        var activeAgentData = null;
        var agentsData = @json($initialAgents ?? []);
        var currentUserId = {{ Auth::guard('crm')->id() }};
        var agentsUrl = @json(route('crm.internal_chat.agents'));
        var sendUrl = @json(route('crm.internal_chat.send'));
        var forwardUrl = @json(route('crm.internal_chat.forward'));
        var messagesBaseUrl = @json(url('crm/internal-chat'));
        var csrfToken = @json(csrf_token());
        var allowedExtensions = @json(\App\Http\Controllers\Crm\InternalChatController::ATTACHMENT_TYPES).split(',');
        var maxFileBytes = {{ \App\Http\Controllers\Crm\InternalChatController::ATTACHMENT_MAX_KB * 1024 }};

        var chatFilter = 'all';
        var agentListLoading = false;
        var agentListController = null;
        var messagesById = {};
        var lastMessagesSignature = null;
        var selectedFiles = [];
        var isEditing = false;
        var editingMessageId = null;
        var messageToForward = null;
        var selectedForwardIds = [];

        // ── Helpers ──────────────────────────────────────────────────────────
        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function linkify(escaped) {
            return escaped.replace(/https?:\/\/[^\s<]+/g, function (url) {
                return '<a href="' + url + '" target="_blank" rel="noopener noreferrer">' + url + '</a>';
            });
        }

        /** Message text is escaped; the one link the server itself writes (customer attachments) stays a link. */
        function renderBody(raw) {
            var pattern = /<a href='(https?:\/\/[^']+)' target='_blank'>([\s\S]*?)<\/a>/g;
            var html = '', last = 0, match;
            while ((match = pattern.exec(raw))) {
                html += linkify(esc(raw.slice(last, match.index)));
                html += '<a href="' + esc(match[1]) + '" target="_blank" rel="noopener noreferrer">' + match[2].replace(/[<>]/g, '') + '</a>';
                last = match.index + match[0].length;
            }
            return (html + linkify(esc(raw.slice(last)))).replace(/\n/g, '<br>');
        }

        function initialsOf(name) {
            return String(name || '?').split(/\s+/).filter(function (n) { return n && n !== '-'; }).slice(0, 2).map(function (n) { return n[0]; }).join('').toUpperCase();
        }

        function avatarColor(id) {
            var colors = ['#f45a24', '#2563eb', '#7c3aed', '#0891b2', '#16a34a', '#db2777', '#d97706', '#475569'];
            var seed = String(id).split('').reduce(function (sum, c) { return sum + c.charCodeAt(0); }, 0);
            return colors[seed % colors.length];
        }

        function parseDate(value) {
            return value ? new Date(String(value).replace(' ', 'T')) : null;
        }

        function shortTime(value) {
            var date = parseDate(value);
            if (!date || isNaN(date)) return '';
            var now = new Date();
            if (date.toDateString() === now.toDateString()) return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            var yesterday = new Date(now.getTime() - 86400000);
            if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
            if (now - date < 6 * 86400000) return date.toLocaleDateString('en-US', { weekday: 'short' });
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        function formatBytes(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return Math.round(bytes / 1024) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function fileExtension(name) {
            var parts = String(name || '').toLowerCase().split('.');
            return parts.length > 1 ? parts.pop() : '';
        }

        function fileLook(ext) {
            if (ext === 'pdf') return ['fa-file-pdf', '#dc2626'];
            if (['doc', 'docx', 'rtf', 'odt', 'txt'].indexOf(ext) > -1) return ['fa-file-word', '#2563eb'];
            if (['xls', 'xlsx', 'csv', 'ods'].indexOf(ext) > -1) return ['fa-file-excel', '#16a34a'];
            if (['ppt', 'pptx'].indexOf(ext) > -1) return ['fa-file-powerpoint', '#ea580c'];
            if (['zip', 'rar', '7z'].indexOf(ext) > -1) return ['fa-file-archive', '#a16207'];
            if (['ai', 'psd', 'eps', 'cdr', 'indd', 'dxf'].indexOf(ext) > -1) return ['fa-pen-nib', '#7c3aed'];
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'tif', 'tiff'].indexOf(ext) > -1) return ['fa-file-image', '#0891b2'];
            if (['mp4', 'webm', 'mov', 'mkv'].indexOf(ext) > -1) return ['fa-file-video', '#7c3aed'];
            if (['mp3', 'wav', 'm4a', 'ogg', 'aac'].indexOf(ext) > -1) return ['fa-file-audio', '#db2777'];
            return ['fa-file', '#64748b'];
        }

        function notify(message, type) {
            if (typeof showToast === 'function') showToast(message, type || 'error');
            else alert(message);
        }

        // ── Conversation list ────────────────────────────────────────────────
        function loadAgentList() {
            // Navigated away: stop the background poll.
            if (!document.getElementById('agentListContainer')) {
                if (window.__teamListPoll) { clearInterval(window.__teamListPoll); window.__teamListPoll = null; }
                return Promise.resolve();
            }
            if (agentListLoading || document.hidden) return Promise.resolve();
            agentListLoading = true;
            agentListController = new AbortController();
            var controller = agentListController;
            var timeout = setTimeout(function () { controller.abort(); }, 12000);

            return fetch(agentsUrl, { signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('Team request failed (' + res.status + ')');
                    // A browser keeps one login: another login here replaced this tab's user, so show who it really is now.
                    var loggedInAs = res.headers.get('X-Crm-User');
                    if (loggedInAs && loggedInAs !== String(currentUserId)) {
                        window.location.reload();
                        return new Promise(function () {});
                    }
                    return res.json();
                })
                .then(function (data) {
                    agentsData = data;
                    renderAgentList();
                    if (activeAgentId) {
                        var active = agentsData.find(function (a) { return a.id == activeAgentId; });
                        if (active) { activeAgentData = active; updateActiveStatus(active); }
                    }
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') return;
                    console.error('Team list error:', err);
                })
                .finally(function () {
                    clearTimeout(timeout);
                    if (agentListController === controller) {
                        agentListController = null;
                        agentListLoading = false;
                    }
                });
        }

        function resumeAgentList() {
            if (document.hidden) return;
            if (agentListController) agentListController.abort();
            agentListController = null;
            agentListLoading = false;
            loadAgentList();
            if (activeAgentId) fetchTeamMessages();
        }

        function setChatFilter(filter) {
            chatFilter = filter;
            renderAgentList();
        }

        function hasConversation(agent) { return !!agent.last_message_at; }

        function renderFilters() {
            var unread = agentsData.filter(function (a) { return a.unread_count > 0; }).length;
            document.querySelectorAll('.tc-filter').forEach(function (btn) {
                var name = btn.getAttribute('data-filter');
                btn.classList.toggle('active', name === chatFilter);
                btn.innerHTML = btn.textContent.replace(/\d+/g, '').trim() + (name === 'unread' && unread ? ' <small>' + unread + '</small>' : '');
            });
        }

        function previewHtml(agent) {
            if (!hasConversation(agent)) return '<div class="tc-item-preview none">' + esc(agent.role_label || 'No messages yet') + '</div>';
            var you = agent.last_from_me ? 'You: ' : '';
            if (agent.last_is_call) {
                return '<div class="tc-item-preview"><i class="fas ' + (/video/i.test(agent.last_message) ? 'fa-video' : 'fa-phone') + '"></i>' + esc(agent.last_message) + '</div>';
            }
            var text = String(agent.last_message || '').replace(/<[^>]+>/g, '').trim();
            if (!text && agent.last_attachment) {
                return '<div class="tc-item-preview">' + you + '<i class="fas fa-paperclip"></i>' + esc(agent.last_attachment) + '</div>';
            }
            return '<div class="tc-item-preview">' + you + esc(text) + '</div>';
        }

        function renderAgentList() {
            var container = document.getElementById('agentListContainer');
            if (!container) return;
            renderFilters();

            var query = (document.getElementById('agentSearch').value || '').toLowerCase();
            var list = agentsData.filter(function (a) { return String(a.name).toLowerCase().indexOf(query) > -1; });
            // Searching a name looks through everyone, so a teammate is never hidden by the filter that happens to be on.
            if (!query && chatFilter === 'unread') list = list.filter(function (a) { return a.unread_count > 0; });
            if (!query && chatFilter === 'read') list = list.filter(function (a) { return hasConversation(a) && !a.unread_count; });
            if (!query && chatFilter === 'recent') list = list.filter(hasConversation);

            if (!list.length) {
                var messages = { unread: ['fa-check-double', 'You are all caught up.'], read: ['fa-comment-slash', 'No read conversations yet.'], recent: ['fa-history', 'No recent conversations yet.'], all: ['fa-users', 'No teammates found.'] };
                var empty = query ? ['fa-search', 'No teammates match your search.'] : messages[chatFilter];
                container.innerHTML = '<div class="tc-list-empty"><i class="fas ' + empty[0] + '"></i>' + esc(empty[1]) + '</div>';
                return;
            }

            container.innerHTML = list.map(function (agent) {
                var status = agent.online_status || 'offline';
                var unread = agent.unread_count > 0;
                return '<div class="tc-item' + (activeAgentId == agent.id ? ' active' : '') + (unread ? ' unread' : '') + '" id="agent-item-' + esc(agent.id) + '" data-agent="' + esc(agent.id) + '">'
                    + '<div class="tc-avatar" style="background:' + avatarColor(agent.id) + '">' + esc(initialsOf(agent.name)) + '<span class="tc-dot ' + esc(status) + '"></span></div>'
                    + '<div class="tc-item-body">'
                    + '<div class="tc-item-top"><span class="tc-item-name">' + esc(agent.name) + '</span><span class="tc-item-time">' + esc(shortTime(agent.last_message_at)) + '</span></div>'
                    + '<div class="tc-item-bottom">' + previewHtml(agent) + (unread ? '<span class="tc-count">' + agent.unread_count + '</span>' : '') + '</div>'
                    + '</div></div>';
            }).join('');
            container.querySelectorAll('.tc-item').forEach(function (item) {
                item.addEventListener('click', function () {
                    var agent = agentsData.find(function (a) { return String(a.id) === item.getAttribute('data-agent'); });
                    if (agent) selectAgent(agent);
                });
            });
        }

        function updateActiveStatus(agent) {
            var el = document.getElementById('activeStatus');
            var status = agent.online_status || 'offline';
            var label = status === 'online' ? 'Online'
                : (agent.last_seen_human && agent.last_seen_human !== 'Never' ? 'Last seen ' + agent.last_seen_human : 'Offline');
            if (String(agent.id).indexOf('customer-') === 0) label = 'Customer';
            el.className = 'tc-head-status' + (status === 'online' ? ' online' : '');
            el.textContent = (agent.role_label && agent.role !== 'customer' ? agent.role_label + ' · ' : '') + label;
        }

        // ── Open conversation ────────────────────────────────────────────────
        function selectAgent(agent) {
            activeAgentId = agent.id;
            activeAgentData = agent;
            lastMessagesSignature = null;
            messagesById = {};
            cancelEdit();

            var url = new URL(window.location);
            url.searchParams.set('agent', agent.id);
            window.history.pushState({}, '', url);

            document.getElementById('teamChatApp').classList.add('chat-active');
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('chatHeader').style.display = 'flex';
            document.getElementById('messagesContainer').style.display = 'flex';
            document.getElementById('messagesContainer').innerHTML = '';
            document.getElementById('inputArea').style.display = 'block';

            var avatar = document.getElementById('activeAvatar');
            avatar.textContent = initialsOf(agent.name);
            avatar.style.background = avatarColor(agent.id);
            document.getElementById('activeName').textContent = agent.name;
            updateActiveStatus(agent);

            var isCustomer = String(agent.id).indexOf('customer-') === 0;
            document.getElementById('voiceCallBtn').style.display = isCustomer ? 'none' : '';
            document.getElementById('videoCallBtn').style.display = isCustomer ? 'none' : '';

            document.querySelectorAll('.tc-item').forEach(function (el) { el.classList.remove('active'); });
            var activeItem = document.getElementById('agent-item-' + agent.id);
            if (activeItem) activeItem.classList.add('active');

            fetchTeamMessages(true);
            if (window.__teamMessagePoll) clearInterval(window.__teamMessagePoll);
            window.__teamMessagePoll = setInterval(fetchTeamMessages, 4000);
            document.getElementById('teamMsgInput').focus();
        }

        function closeConversation() {
            document.getElementById('teamChatApp').classList.remove('chat-active');
            activeAgentId = null;
            var url = new URL(window.location);
            url.searchParams.delete('agent');
            window.history.pushState({}, '', url);
            if (window.__teamMessagePoll) { clearInterval(window.__teamMessagePoll); window.__teamMessagePoll = null; }
            renderAgentList();
        }

        function startCall(type) {
            if (!activeAgentData || !window.CrmCall) return;
            window.CrmCall.start(activeAgentData.id, activeAgentData.name, type);
        }

        function fetchTeamMessages(force) {
            if (!document.getElementById('messagesContainer')) {
                if (window.__teamMessagePoll) { clearInterval(window.__teamMessagePoll); window.__teamMessagePoll = null; }
                return;
            }
            if (!activeAgentId) return;
            var requestedFor = activeAgentId;

            fetch(messagesBaseUrl + '/messages/' + encodeURIComponent(activeAgentId), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (res) { return res.ok ? res.json() : Promise.reject(new Error('Messages request failed (' + res.status + ')')); })
                .then(function (messages) {
                    if (requestedFor != activeAgentId) return;
                    // Redraw only when something really changed (new, edited or newly read), so playing videos and open menus survive the poll.
                    var signature = messages.map(function (m) { return m.id + ':' + m.updated_at + ':' + m.is_read; }).join('|');
                    if (force !== true && signature === lastMessagesSignature) return;
                    var isFirstDraw = lastMessagesSignature === null;
                    lastMessagesSignature = signature;
                    renderMessages(messages, isFirstDraw);
                    loadAgentList();
                })
                .catch(function (err) { console.error('Fetch messages error:', err); });
        }

        function attachmentHtml(msg) {
            if (!msg.attachment_url) return '';
            var url = esc(msg.attachment_url);
            var name = msg.attachment_name || 'File';
            var ext = fileExtension(name);
            var downloadUrl = url + '?download=1';
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].indexOf(ext) > -1) {
                return '<img class="tc-media" src="' + url + '" alt="' + esc(name) + '" loading="lazy" onclick="openLightbox(this.src)">';
            }
            if (['mp4', 'webm', 'mov'].indexOf(ext) > -1) {
                return '<video class="tc-media" src="' + url + '" controls preload="metadata"></video>';
            }
            if (['mp3', 'wav', 'm4a', 'ogg', 'aac'].indexOf(ext) > -1) {
                return '<audio class="tc-audio" src="' + url + '" controls preload="metadata"></audio>';
            }
            var look = fileLook(ext);
            var sub = [ext.toUpperCase(), formatBytes(msg.attachment_size)].filter(Boolean).join(' · ');
            return '<div class="tc-file">'
                + '<div class="tc-file-icon" style="background:' + look[1] + '"><i class="fas ' + look[0] + '"></i></div>'
                + '<div class="tc-file-info"><a class="tc-file-name" href="' + (ext === 'pdf' ? url : downloadUrl) + '" target="_blank" rel="noopener" title="' + esc(name) + '">' + esc(name) + '</a><span class="tc-file-sub">' + esc(sub) + '</span></div>'
                + '<a class="tc-file-dl" href="' + downloadUrl + '" title="Download" aria-label="Download"><i class="fas fa-download"></i></a>'
                + '</div>';
        }

        function menuHtml(msg, isMine, isCustomerChat) {
            var items = [];
            var hasText = msg.message_body && msg.message_body.trim();
            if (!isCustomerChat) items.push('<button type="button" onclick="openForwardModal(' + msg.id + ')"><i class="fas fa-share"></i> Forward</button>');
            if (msg.attachment_url) items.push('<button type="button" onclick="window.open(messagesById[' + msg.id + '].attachment_url + \'?download=1\')"><i class="fas fa-download"></i> Download</button>');
            if (!isCustomerChat && isMine && hasText) items.push('<button type="button" onclick="startEditMessage(' + msg.id + ')"><i class="fas fa-pen"></i> Edit</button>');
            if (hasText) items.push('<button type="button" onclick="copyMessage(' + msg.id + ')"><i class="fas fa-copy"></i> Copy text</button>');
            if (!isCustomerChat && isMine) items.push('<button type="button" class="danger" onclick="deleteTeamMessage(' + msg.id + ')"><i class="fas fa-trash-alt"></i> Delete</button>');
            return items.join('');
        }

        function renderMessages(messages, scrollToEnd) {
            var container = document.getElementById('messagesContainer');
            var atBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 120;
            var isCustomerChat = String(activeAgentId).indexOf('customer-') === 0;
            var today = new Date().toDateString();
            var yesterday = new Date(Date.now() - 86400000).toDateString();
            var lastDay = null;
            var html = '';
            messagesById = {};

            if (!messages.length) {
                container.innerHTML = '<div class="tc-empty"><div class="tc-empty-icon"><i class="fas fa-hand-paper"></i></div><h3>No messages yet</h3><p>Say hello to ' + esc(activeAgentData ? activeAgentData.name : 'your teammate') + '.</p></div>';
                return;
            }

            messages.forEach(function (msg) {
                messagesById[msg.id] = msg;
                var created = parseDate(msg.created_at);
                var day = created.toDateString();
                if (day !== lastDay) {
                    lastDay = day;
                    var label = day === today ? 'Today' : (day === yesterday ? 'Yesterday' : created.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }));
                    html += '<div class="tc-date">' + esc(label) + '</div>';
                }
                var time = created.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

                if (msg.call_id) {
                    var missed = /^(Missed|Declined)/.test(msg.message_body || '');
                    html += '<div class="tc-call-row tc-search-row' + (missed ? ' missed' : '') + '"><i class="fas ' + (msg.call_type === 'video' ? 'fa-video' : 'fa-phone') + '"></i>'
                        + esc(msg.message_body) + ' <small>' + esc(time) + '</small>'
                        + (missed && !isCustomerChat ? '<button type="button" onclick="startCall(\'' + (msg.call_type === 'video' ? 'video' : 'audio') + '\')">Call back</button>' : '')
                        + '</div>';
                    return;
                }

                var isMine = msg.sender_id == currentUserId;
                var hasText = msg.message_body && msg.message_body.trim().length > 0;
                var edited = msg.updated_at && msg.updated_at !== msg.created_at && !isCustomerChat;
                var menu = menuHtml(msg, isMine, isCustomerChat);
                html += '<div class="tc-row tc-search-row' + (isMine ? ' mine' : '') + '" data-msg="' + msg.id + '">'
                    + '<div class="tc-bubble">'
                    + (menu ? '<button type="button" class="tc-more" onclick="toggleMsgMenu(event, ' + msg.id + ')" aria-label="Message options"><i class="fas fa-chevron-down"></i></button><div class="tc-menu" id="menu-' + msg.id + '" onclick="event.stopPropagation()">' + menu + '</div>' : '')
                    + (msg.is_forwarded ? '<div class="tc-forwarded"><i class="fas fa-share"></i> Forwarded</div>' : '')
                    + attachmentHtml(msg)
                    + (hasText ? '<div class="tc-text">' + renderBody(msg.message_body) + '</div>' : '')
                    + '<div class="tc-meta">' + (edited ? '<span>edited</span>' : '') + '<span>' + esc(time) + '</span>'
                    + (isMine && !isCustomerChat ? '<i class="fas ' + (msg.is_read ? 'fa-check-double tc-ticks read' : 'fa-check tc-ticks') + '" title="' + (msg.is_read ? 'Read' : 'Sent') + '"></i>' : '')
                    + '</div></div></div>';
            });

            container.innerHTML = html;
            filterMessages();
            if (scrollToEnd || atBottom) {
                container.scrollTop = container.scrollHeight;
                // Images load after the first draw; keep the newest message in view.
                container.querySelectorAll('img.tc-media').forEach(function (img) {
                    img.addEventListener('load', function () { container.scrollTop = container.scrollHeight; }, { once: true });
                });
            }
        }

        // ── Message actions ──────────────────────────────────────────────────
        function closeAllMenus() {
            document.querySelectorAll('.tc-menu.show, .tc-attach-menu.show').forEach(function (m) { m.classList.remove('show'); });
        }

        function toggleMsgMenu(event, id) {
            event.stopPropagation();
            var menu = document.getElementById('menu-' + id);
            if (!menu) return;
            var wasOpen = menu.classList.contains('show');
            closeAllMenus();
            if (!wasOpen) {
                menu.classList.toggle('up', event.currentTarget.getBoundingClientRect().bottom > window.innerHeight - 240);
                menu.classList.add('show');
            }
        }

        function toggleAttachMenu(event) {
            event.stopPropagation();
            var menu = document.getElementById('attachMenu');
            var wasOpen = menu.classList.contains('show');
            closeAllMenus();
            if (!wasOpen) menu.classList.add('show');
        }

        function openLightbox(src) {
            document.getElementById('lightboxImg').src = src;
            document.getElementById('imageLightbox').classList.add('show');
        }

        function copyMessage(id) {
            closeAllMenus();
            var text = (messagesById[id] || {}).message_body || '';
            var done = function () { notify('Copied', 'success'); };
            if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).then(done).catch(function () {}); return; }
            var el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            done();
        }

        function deleteTeamMessage(id) {
            closeAllMenus();
            if (!confirm('Delete this message?')) return;
            fetch(messagesBaseUrl + '/delete/' + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res.success) fetchTeamMessages(true);
                    else notify(res.message || 'Could not delete the message.');
                })
                .catch(function () { notify('Could not delete the message.'); });
        }

        function startEditMessage(id) {
            closeAllMenus();
            isEditing = true;
            editingMessageId = id;
            var input = document.getElementById('teamMsgInput');
            input.value = (messagesById[id] || {}).message_body || '';
            resizeInput();
            input.focus();
            document.getElementById('editModeBar').style.display = 'flex';
            document.getElementById('sendBtnIcon').className = 'fas fa-check';
        }

        function cancelEdit() {
            isEditing = false;
            editingMessageId = null;
            var input = document.getElementById('teamMsgInput');
            if (input) { input.value = ''; resizeInput(); }
            var bar = document.getElementById('editModeBar');
            if (bar) bar.style.display = 'none';
            var icon = document.getElementById('sendBtnIcon');
            if (icon) icon.className = 'fas fa-paper-plane';
        }

        function resizeInput() {
            var input = document.getElementById('teamMsgInput');
            input.style.height = 'auto';
            // Empty, or still hidden: stay at the default size.
            if (!input.value || !input.scrollHeight) return;
            input.style.height = input.scrollHeight + 'px';
        }

        // ── Attachments ──────────────────────────────────────────────────────
        function addFiles(fileList) {
            closeAllMenus();
            Array.from(fileList || []).forEach(function (file) {
                if (allowedExtensions.indexOf(fileExtension(file.name)) === -1) {
                    notify(file.name + ': this file type cannot be sent.');
                    return;
                }
                if (file.size > maxFileBytes) {
                    notify(file.name + ' is larger than ' + formatBytes(maxFileBytes) + '.');
                    return;
                }
                selectedFiles.push(file);
            });
            renderTray();
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderTray();
        }

        function renderTray() {
            var tray = document.getElementById('attachmentTray');
            tray.querySelectorAll('img').forEach(function (img) { URL.revokeObjectURL(img.src); });
            tray.style.display = selectedFiles.length ? 'flex' : 'none';
            tray.innerHTML = selectedFiles.map(function (file, i) {
                var ext = fileExtension(file.name);
                var look = fileLook(ext);
                var thumb = /^image\/(jpeg|png|gif|webp|bmp)$/.test(file.type)
                    ? '<img class="tc-tray-thumb" src="' + URL.createObjectURL(file) + '" alt="">'
                    : '<div class="tc-tray-thumb" style="background:' + look[1] + '"><i class="fas ' + look[0] + '"></i></div>';
                return '<div class="tc-tray-item">' + thumb
                    + '<div style="min-width:0"><div class="tc-tray-name">' + esc(file.name) + '</div><div class="tc-tray-size">' + esc(formatBytes(file.size)) + '</div></div>'
                    + '<button type="button" class="tc-tray-remove" onclick="removeFile(' + i + ')" aria-label="Remove"><i class="fas fa-times"></i></button></div>';
            }).join('');
        }

        function postMessage(body, file) {
            var data = new FormData();
            data.append('receiver_id', activeAgentId);
            data.append('message_body', body);
            if (file) data.append('attachment', file);
            data.append('_token', csrfToken);
            return fetch(sendUrl, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                .then(function (res) {
                    return res.json().catch(function () { return {}; }).then(function (json) {
                        if (!res.ok || !json.success) {
                            var first = json.errors ? Object.values(json.errors)[0][0] : (json.message || 'The message could not be sent.');
                            throw new Error((file ? file.name + ': ' : '') + first);
                        }
                        return json;
                    });
                });
        }

        function sendTeamMessage() {
            var input = document.getElementById('teamMsgInput');
            var body = input.value.trim();
            if (!activeAgentId) return;
            if (isEditing) {
                if (body) updateTeamMessage(editingMessageId, body);
                return;
            }
            if (!body && !selectedFiles.length) return;

            var btn = document.getElementById('sendBtn');
            var icon = document.getElementById('sendBtnIcon');
            // Enter pressed again while the last message is still going: don't send it twice.
            if (btn.disabled) return;
            btn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin';

            // One message per file, in order; the typed text goes with the first one.
            var files = selectedFiles.slice();
            var chain = files.length ? Promise.resolve() : postMessage(body, null);
            files.forEach(function (file, i) {
                chain = chain.then(function () { return postMessage(i === 0 ? body : '', file); }).then(function () {
                    selectedFiles.shift();
                    renderTray();
                    if (i === 0) { input.value = ''; resizeInput(); }
                });
            });
            chain.then(function () {
                input.value = '';
                resizeInput();
            }).catch(function (err) {
                notify(err.message);
            }).finally(function () {
                btn.disabled = false;
                icon.className = 'fas fa-paper-plane';
                fetchTeamMessages(true);
                input.focus();
            });
        }

        function updateTeamMessage(id, text) {
            fetch(messagesBaseUrl + '/edit/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ message_body: text })
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (res.success) { cancelEdit(); fetchTeamMessages(true); }
                    else notify(res.message || 'Could not edit the message.');
                })
                .catch(function () { notify('Could not edit the message.'); });
        }

        // ── Forward ──────────────────────────────────────────────────────────
        function openForwardModal(id) {
            closeAllMenus();
            messageToForward = id;
            selectedForwardIds = [];
            document.getElementById('forwardSearchInput').value = '';
            renderForwardList();
            document.getElementById('forwardModal').classList.add('show');
        }

        function closeForwardModal() {
            document.getElementById('forwardModal').classList.remove('show');
        }

        function renderForwardList() {
            var query = document.getElementById('forwardSearchInput').value.toLowerCase();
            var people = agentsData.filter(function (a) { return String(a.id).indexOf('customer-') !== 0 && String(a.name).toLowerCase().indexOf(query) > -1; });
            var container = document.getElementById('forwardList');
            if (!people.length) {
                container.innerHTML = '<div class="tc-list-empty">No teammates found.</div>';
                return;
            }
            container.innerHTML = people.map(function (a) {
                var selected = selectedForwardIds.indexOf(a.id) > -1;
                return '<div class="tc-forward-item' + (selected ? ' selected' : '') + '" onclick="toggleForward(' + Number(a.id) + ')">'
                    + '<div class="tc-avatar" style="background:' + avatarColor(a.id) + '">' + esc(initialsOf(a.name)) + '</div><span>' + esc(a.name) + '</span>'
                    + '<i class="' + (selected ? 'fas fa-check-circle' : 'far fa-circle') + '"></i></div>';
            }).join('');
        }

        function toggleForward(id) {
            var index = selectedForwardIds.indexOf(id);
            if (index > -1) selectedForwardIds.splice(index, 1);
            else selectedForwardIds.push(id);
            renderForwardList();
        }

        function confirmForward() {
            if (!selectedForwardIds.length) { notify('Please select at least one teammate.'); return; }
            var btn = document.getElementById('confirmForwardBtn');
            btn.disabled = true;
            fetch(forwardUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: JSON.stringify({ message_id: messageToForward, receiver_ids: selectedForwardIds })
            })
                .then(function (res) { return res.json(); })
                .then(function (res) {
                    if (!res.success) throw new Error(res.message);
                    closeForwardModal();
                    notify('Message forwarded.', 'success');
                    fetchTeamMessages(true);
                })
                .catch(function (err) { notify(err.message || 'Could not forward the message.'); })
                .finally(function () { btn.disabled = false; });
        }

        // ── Search inside a conversation ─────────────────────────────────────
        function toggleMsgSearch() {
            var box = document.getElementById('msgSearch');
            var input = document.getElementById('msgSearchInput');
            if (box.classList.toggle('show')) { input.focus(); return; }
            input.value = '';
            filterMessages();
        }

        function filterMessages() {
            var input = document.getElementById('msgSearchInput');
            if (!input) return;
            var query = input.value.toLowerCase();
            document.querySelectorAll('#messagesContainer .tc-search-row').forEach(function (row) {
                row.style.display = !query || row.textContent.toLowerCase().indexOf(query) > -1 ? '' : 'none';
            });
            document.querySelectorAll('#messagesContainer .tc-date').forEach(function (date) {
                var next = date.nextElementSibling, visible = false;
                while (next && !next.classList.contains('tc-date')) {
                    if (next.style.display !== 'none') { visible = true; break; }
                    next = next.nextElementSibling;
                }
                date.style.display = visible ? '' : 'none';
            });
        }

        // ── Page wiring. Elements are new on every visit, so these bind directly. ──
        (function () {
            var input = document.getElementById('teamMsgInput');
            input.addEventListener('input', resizeInput);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendTeamMessage(); }
                if (e.key === 'Escape' && isEditing) cancelEdit();
            });
            input.addEventListener('paste', function (e) {
                var files = e.clipboardData && e.clipboardData.files;
                if (!files || !files.length) return;
                e.preventDefault();
                // A pasted screenshot arrives as "image.png" for every paste: keep them apart.
                Array.from(files).forEach(function (file, i) {
                    var stamp = new Date().toISOString().replace(/[:.]/g, '-');
                    addFiles([file.name === 'image.png' ? new File([file], 'screenshot-' + stamp + '-' + i + '.png', { type: file.type }) : file]);
                });
            });

            var main = document.getElementById('tcMain');
            var overlay = document.getElementById('dropOverlay');
            var isFileDrag = function (e) { return e.dataTransfer && Array.from(e.dataTransfer.types || []).indexOf('Files') > -1; };
            main.addEventListener('dragover', function (e) {
                if (!activeAgentId || !isFileDrag(e)) return;
                e.preventDefault();
                overlay.classList.add('show');
            });
            main.addEventListener('dragleave', function (e) {
                if (!main.contains(e.relatedTarget)) overlay.classList.remove('show');
            });
            main.addEventListener('drop', function (e) {
                if (!activeAgentId || !isFileDrag(e)) return;
                e.preventDefault();
                overlay.classList.remove('show');
                addFiles(e.dataTransfer.files);
            });
        })();

        // Init: runs on every (full or AJAX partial) visit.
        renderAgentList();
        loadAgentList();
        if (window.__teamListPoll) clearInterval(window.__teamListPoll);
        window.__teamListPoll = setInterval(loadAgentList, 5000);
        if (window.__teamMessagePoll) { clearInterval(window.__teamMessagePoll); window.__teamMessagePoll = null; }

        // Global listeners call global functions on the live DOM, so they bind only once.
        if (!window.__teamListenersBound) {
            window.__teamListenersBound = true;
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden && document.getElementById('teamChatApp')) resumeAgentList();
            });
            window.addEventListener('pageshow', function () {
                if (document.getElementById('teamChatApp')) resumeAgentList();
            });
            window.addEventListener('click', function () {
                if (document.getElementById('teamChatApp')) closeAllMenus();
            });
            // The call's log appears in the conversation as soon as a call ends.
            document.addEventListener('crm:call-ended', function () {
                if (document.getElementById('teamChatApp')) setTimeout(function () { fetchTeamMessages(true); }, 700);
            });
        }

        // Open a conversation from the URL (the 'load' event never re-fires on AJAX nav).
        (function () {
            var agentId = new URLSearchParams(window.location.search).get('agent');
            var agent = agentId && agentsData.find(function (a) { return String(a.id) === agentId; });
            if (agent) selectAgent(agent);
        })();
    </script>
@endsection
