@extends('crm.layout')
@section('title', 'Team Chat')

@section('styles')
    /* Full height layout adjustment */
    .main-area { padding: 0 !important; overflow: hidden !important; background: #fff; display: flex; flex-direction:
    column; }
    .top-bar { display: none !important; }

    .chat-container {
    display: flex;
    height: 100vh;
    width: 100%;
    background: white;
    }

    /* Left Sidebar: Agent List */
    .chat-list-sidebar {
    width: 320px;
    border-right: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    background: #fff;
    flex-shrink: 0;
    }

    .chat-list-header {
    padding: 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    }

    .chat-list-header h2 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    }

    .search-chat {
    background: #f1f5f9;
    border-radius: 10px;
    padding: 0.6rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    }

    .search-chat input {
    border: none;
    background: none;
    outline: none;
    width: 100%;
    font-size: 0.9rem;
    }

    .chat-list-items { flex: 1; overflow-y: auto; }

    .chat-item {
    padding: 1.2rem 1.5rem;
    border-bottom: 1px solid #f8fafc;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 15px;
    }

    .chat-item:hover { background: #f8fafcb3; }
    .chat-item.active { background: #f0f3ff; border-right: 3px solid var(--primary-purple); }

    .chat-avatar {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #ecf0ff;
    color: var(--primary-purple);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    font-size: 1.1rem;
    position: relative;
    }

    .online-dot {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid white;
    }
    .online-dot.online  { background: #10b981; }
    .online-dot.recent  { background: #f59e0b; }
    .online-dot.offline { background: #cbd5e1; }

    .chat-info { flex: 1; min-width: 0; }
    .chat-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; margin-bottom: 2px; display: block; }
    .chat-role { font-size: 0.75rem; color: var(--primary-purple); font-weight: 700; text-transform: uppercase; margin-bottom: 4px;
    display: block; }
    .chat-last-msg { font-size: 0.82rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    display: block; }
    .chat-meta { text-align: right; min-width: 60px; }
    .chat-time { font-size: 0.7rem; color: #94a3b8; }
    .chat-badge {
    background: #ef4444; color: white; border-radius: 99px;
    font-size: 0.68rem; padding: 2px 7px; font-weight: 700;
    margin-top: 6px; display: inline-block;
    }

    /* Right Side: Chat Window */
    .chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f8fafc;
    position: relative;
    }

    .chat-header {
    padding: 1rem 2rem;
    background: white;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 75px;
    z-index: 10;
    }

    .chat-messages-container {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    scroll-behavior: smooth;
    }

    .chat-input-area {
    padding: 1.2rem 2rem;
    background: white;
    border-top: 1px solid #f1f5f9;
    }

    .chat-input-wrapper {
    display: flex;
    gap: 1rem;
    align-items: center;
    background: #f1f5f9;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    }

    .chat-input-wrapper textarea {
    flex: 1;
    border: none;
    background: none;
    outline: none;
    padding: 0.5rem 0;
    resize: none;
    font-family: inherit;
    font-size: 0.95rem;
    max-height: 100px;
    }

    .attach-btn {
    background: none;
    border: none;
    color: #64748b;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    transition: color 0.2s;
    }
    .attach-btn:hover { color: var(--primary-purple); }

    .attachment-preview {
    display: none;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 8px;
    align-items: center;
    gap: 10px;
    font-size: 0.85rem;
    }

    .msg-attachment {
    margin-top: 8px;
    padding: 8px 12px;
    background: rgba(0,0,0,0.05);
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(0,0,0,0.1);
    }

    .msg-sent .msg-attachment {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    }

    .msg-forwarded-label {
    font-size: 0.7rem;
    font-style: italic;
    opacity: 0.8;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
    }

    .msg-actions {
    position: absolute;
    right: -30px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0;
    transition: opacity 0.2s;
    display: flex;
    flex-direction: column;
    gap: 5px;
    }

    .msg-row:hover .msg-actions { opacity: 1; }
    .msg-received .msg-actions { left: -30px; right: auto; }

    .action-icon {
    width: 24px;
    height: 24px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    color: #64748b;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .action-icon:hover { color: var(--primary-purple); border-color: var(--primary-purple); }

    /* Modal for Forward */
    .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
    }

    .modal-content {
    background: white;
    width: 90%;
    max-width: 440px;
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
    max-height: 85vh;
    animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    }

    .modal-body {
    padding: 1.25rem;
    overflow-y: auto;
    }

    .forward-search {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.6rem 1rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 10px;
    }

    .forward-search input {
    border: none;
    background: none;
    outline: none;
    width: 100%;
    font-size: 0.9rem;
    }

    .forward-agent-item {
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 15px;
    cursor: pointer;
    border-radius: 16px;
    transition: all 0.2s;
    margin-bottom: 4px;
    }
    .forward-agent-item:hover { background: #f8fafc; }
    .forward-agent-item.selected {
    background: #eff6ff;
    box-shadow: inset 0 0 0 1px #3b82f6;
    }
    .forward-agent-item .initials {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    color: #475569;
    }
    .forward-agent-item.selected .initials {
    background: #3b82f6;
    color: white;
    }

    .modal-footer {
    padding: 1.25rem 1.75rem;
    border-top: 1px solid #f1f5f9;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    }

    .btn-modal {
    padding: 0.7rem 1.5rem;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    }

    .btn-modal-cancel {
    background: #f1f5f9;
    color: #64748b;
    }
    .btn-modal-cancel:hover { background: #e2e8f0; color: #475569; }

    .btn-modal-primary {
    background: var(--primary-purple);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(var(--primary-rgb), 0.2);
    }
    .btn-modal-primary:hover {
    background: var(--primary-purple);
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(var(--primary-rgb), 0.3);
    }
    .btn-modal-primary:active { transform: translateY(0); }

    .send-btn {
    background: var(--primary-purple);
    color: white;
    padding: 0 1.5rem;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    border: none;
    transition: transform 0.2s, background 0.2s;
    font-weight: 600;
    font-size: 0.9rem;
    }
    .send-btn:hover { transform: scale(1.03); background: var(--primary-purple); }

    .msg-row { display: flex; flex-direction: column; width: 100%; }
    .msg-bubble {
    max-width: 72%;
    padding: 0.85rem 1.1rem;
    border-radius: 14px;
    font-size: 0.95rem;
    line-height: 1.5;
    }

    .msg-sent {
    align-self: flex-end;
    background: var(--primary-purple);
    color: white;
    border-bottom-right-radius: 4px;
    }

    .msg-received {
    align-self: flex-start;
    background: white;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .msg-time { font-size: 0.7rem; margin-top: 5px; color: #94a3b8; }
    .msg-sent .msg-time { color: #c7d2fe; }

    .chat-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #94a3b8;
    padding: 2rem;
    text-align: center;
    }
    .chat-empty i { font-size: 4rem; opacity: 0.3; margin-bottom: 1.5rem; }
    .chat-empty h3 { color: #64748b; font-weight: 700; }

    /* Responsive */
    @media (max-width: 1024px) { .chat-list-sidebar { width: 280px; } }

    @media (max-width: 768px) {
    .chat-container { height: 100vh; }
    .chat-list-sidebar { width: 100%; }
    .chat-main { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: 1000; display: none; }
    .chat-container.chat-active .chat-list-sidebar { display: none; }
    .chat-container.chat-active .chat-main { display: flex; }
    .chat-header { padding: 1rem; height: auto; min-height: 65px; }
    .chat-messages-container { padding: 1rem; gap: 1rem; }
    .chat-input-area { padding: 1rem; }
    #mobileBackBtn { display: flex !important; }
    }

    .chat-search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f1f5f9;
    padding: 5px 12px;
    border-radius: 20px;
    transition: all 0.3s ease;
    width: 40px;
    overflow: hidden;
    cursor: pointer;
    }

    .chat-search-wrapper.expanded {
    width: 220px;
    }

    .chat-search-wrapper input {
    border: none;
    background: none;
    outline: none;
    font-size: 0.85rem;
    width: 0;
    transition: width 0.3s ease;
    }

    .chat-search-wrapper.expanded input {
    width: 160px;
    }

    .msg-highlight {
    background: #fff9c4;
    color: #1e293b;
    font-weight: 600;
    }
    .media-container {
    position: relative;
    border-radius: 12px;
    cursor: pointer;
    max-width: 260px;
    transition: all 0.3s ease;
    }

    .media-container:hover img, .media-container:hover video {
    filter: blur(1.5px);
    transform: scale(1.02);
    }

    .media-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.15);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    flex-direction: column;
    padding: 10px;
    pointer-events: none; /* Let clicks pass to the image by default */
    }

    .media-container:hover .media-overlay {
    opacity: 1;
    }

    .media-dots {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1e293b;
    cursor: pointer;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    pointer-events: auto; /* Catch clicks on the dots */
    }

    .media-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    display: none;
    flex-direction: column;
    min-width: 160px;
    z-index: 2000;
    overflow: hidden;
    padding: 6px;
    border: 1px solid rgba(0,0,0,0.05);
    }

    .msg-received .media-dropdown {
        right: auto;
        left: 0;
    }

    .media-dropdown.show {
    display: flex;
    }

    .media-dropdown.open-up {
        top: auto;
        bottom: 100%;
        margin-bottom: 8px;
        box-shadow: 0 -10px 30px rgba(0,0,0,0.2), 0 0 1px rgba(0,0,0,0.1);
    }

    .media-item {
    padding: 10px 15px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;
    border-radius: 8px;
    cursor: pointer;
    }

    .media-item:hover {
    background: #f1f5f9;
    color: #1e293b;
    }

    .media-item.delete:hover {
    background: #fef2f2;
    color: #ef4444;
    }

    .msg-bubble { position: relative; overflow: visible !important; }
    .msg-bubble:hover .bubble-dots { opacity: 1; }
    .bubble-dots {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s;
    color: #94a3b8;
    z-index: 2000;
    border-radius: 50%;
    }
    .msg-bubble:hover .bubble-dots:hover {
    background: rgba(0,0,0,0.05);
    }
    .msg-sent .bubble-dots { color: #c7d2fe; }
    .msg-sent .bubble-dots:hover { background: rgba(0,0,0,0.1); }

    .msg-attachment-placeholder {
        background: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        border: 1px solid #e2e8f0;
        min-width: 140px;
        max-width: 200px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .msg-attachment-placeholder:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
@endsection

@section('content')
    <div class="chat-container" id="teamChatApp">

        {{-- Left: Agent List --}}
        <div class="chat-list-sidebar">
            <div class="chat-list-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"
                        style="margin-right:0; margin-bottom:1rem;"></i>
                    <h2>Team Chat</h2>
                </div>
                <div class="search-chat">
                    <i class="fas fa-search" style="color:#94a3b8"></i>
                    <input type="text" id="agentSearch" placeholder="Search teammates..." onkeyup="filterAgents()">
                </div>
            </div>
            <div class="chat-list-items" id="agentListContainer">
                @forelse(($initialAgents ?? []) as $agent)
                    @php
                        $initials = collect(preg_split('/\s+/', $agent['name']))->filter()->map(function ($part) { return strtoupper(substr($part, 0, 1)); })->take(2)->implode('');
                        $statusClass = $agent['online_status'] ?? 'offline';
                        $statusLabel = $statusClass === 'online' ? 'Online' : (($agent['last_seen_human'] ?? 'Never') !== 'Never' ? $agent['last_seen_human'] : 'Offline');
                    @endphp
                    <div class="chat-item" id="agent-item-{{ $agent['id'] }}" onclick="selectAgent(agentsData.find(function(agent){ return String(agent.id) === @json((string) $agent['id']); }))">
                        <div class="chat-avatar">
                            {{ $initials }}
                            <span class="online-dot {{ $statusClass }}"></span>
                        </div>
                        <div class="chat-info">
                            <span class="chat-name">{{ $agent['name'] }}</span>
                            <span class="chat-role" style="color:{{ $statusClass === 'online' ? '#10b981' : '#94a3b8' }};font-weight:600;font-size:.72rem;display:block;margin-bottom:2px;text-transform:none;">{{ $statusLabel }}</span>
                            <span class="chat-last-msg">
                                @if(!empty($agent['last_message']))
                                    {{ \Illuminate\Support\Str::limit($agent['last_message'], 45) }}
                                @else
                                    <span style="color:#cbd5e1;font-style:italic;">No messages yet</span>
                                @endif
                            </span>
                        </div>
                        <div class="chat-meta">
                            @if(!empty($agent['last_message_at']))
                                <div class="chat-time">{{ \Carbon\Carbon::parse($agent['last_message_at'])->format('g:i A') }}</div>
                            @endif
                            @if(($agent['unread_count'] ?? 0) > 0)<span class="chat-badge">{{ $agent['unread_count'] }}</span>@endif
                        </div>
                    </div>
                @empty
                    <div style="padding:2rem;text-align:center;color:#94a3b8;">No teammates found.</div>
                @endforelse
            </div>
        </div>

        {{-- Right: Chat Window --}}
        <div class="chat-main" id="chatWindow">

            {{-- Empty State --}}
            <div class="chat-empty" id="emptyState">
                <i class="fas fa-users"></i>
                <h3>Select a teammate</h3>
                <p>Pick someone from the left to start a private conversation.</p>
            </div>

            {{-- Chat Header --}}
            <div class="chat-header" id="chatHeader" style="display:none;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <button id="mobileBackBtn" onclick="toggleMobileView(false)"
                        style="display:none; background:none; border:none; color:#64748b; font-size:1.2rem; cursor:pointer; padding:0 5px;">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="chat-avatar" id="activeAvatar" style="width:40px; height:40px; font-size:0.9rem;">U</div>
                    <div>
                        <div style="font-weight:700; color:#1e293b; font-size:1rem;" id="activeName">Name</div>
                        <div id="activeStatus" style="font-size:0.75rem; display:flex; align-items:center; gap:4px;">
                            <!-- Status loaded dynamically -->
                        </div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <div class="chat-search-wrapper" id="chatSearchWrapper" onclick="expandChatSearch()">
                        <i class="fas fa-search" style="color:#64748b; font-size:0.9rem;"></i>
                        <input type="text" id="msgSearchInput" placeholder="Search in chat..." onkeyup="filterMessages()"
                            onclick="event.stopPropagation()">
                        <i class="fas fa-times" id="clearSearchIcon"
                            style="color:#94a3b8; font-size:0.8rem; display:none; cursor:pointer;"
                            onclick="clearMsgSearch(event)"></i>
                    </div>
                </div>
            </div>

            {{-- Messages --}}
            <div class="chat-messages-container" id="messagesContainer" style="display:none;"></div>

            {{-- Input --}}
            <div class="chat-input-area" id="inputArea" style="display:none;">
                <div id="editModeBar"
                    style="display:none; background:#f1f5f9; padding:8px 15px; border-top:1px solid #e2e8f0; font-size:0.85rem; color:#475569; align-items:center; justify-content:space-between;">
                    <span><i class="fas fa-edit"></i> Editing message...</span>
                    <button onclick="cancelEdit()"
                        style="background:none; border:none; color:#ef4444; font-weight:700; cursor:pointer;">Cancel</button>
                </div>
                <div id="attachmentPreview" class="attachment-preview">
                    <i class="fas fa-file"></i>
                    <span id="fileName" style="flex:1;">file.pdf</span>
                    <i class="fas fa-times" style="cursor:pointer;" onclick="clearAttachment()"></i>
                </div>
                <div class="chat-input-wrapper">
                    <input type="file" id="chatFile" style="display:none;" onchange="handleFileSelect(this)">
                    <button type="button" class="attach-btn" onclick="document.getElementById('chatFile').click()">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <textarea id="teamMsgInput" placeholder="Write a message... " rows="1"
                        oninput="this.style.height='auto'; this.style.height=this.scrollHeight+'px'"></textarea>
                    <button type="button" class="send-btn" id="sendBtn" onclick="sendTeamMessage()">
                        <span>Send</span>
                        <i class="fas fa-paper-plane" id="sendBtnIcon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Forward Modal --}}
    <div class="modal-overlay" id="forwardModal" onclick="closeForwardModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 style="margin:0; font-size:1.25rem; font-weight:800; color:#1e293b; letter-spacing:-0.5px;">Forward
                    Message</h3>
                <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:#f1f5f9; cursor:pointer;"
                    onclick="closeForwardModal()">
                    <i class="fas fa-times" style="color:#64748b; font-size:0.9rem;"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="forward-search">
                    <i class="fas fa-search" style="color:#94a3b8; font-size:0.9rem;"></i>
                    <input type="text" id="forwardSearchInput" placeholder="Search teammates..."
                        onkeyup="filterForwardList()">
                </div>
                <div id="forwardList">
                    <!-- Agents will be listed here -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-modal btn-modal-cancel" onclick="closeForwardModal()">Cancel</button>
                <button class="btn-modal btn-modal-primary" id="confirmForwardBtn" onclick="confirmForward()">Forward
                    Message</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let activeAgentId = null;
        let activeAgentData = null;
        let agentsData = @json($initialAgents ?? []);
        let teamPollingInterval = null;
        const currentUserId = {{ Auth::guard('crm')->id() }};
        const storageUrl = "{{ asset('storage') }}/";
        // Docroot IS the public/ folder — serve chat_attachments straight from web root.
        const publicUrl = "{{ rtrim(url('/'), '/') }}/";

        const getFullUrl = (path) => {
            if (!path) return '';
            if (path.startsWith('chat_attachments/')) return publicUrl + path;
            return storageUrl + path;
        };

        const agentsUrl = '{{ route("crm.internal_chat.agents") }}';
        const sendUrl = '{{ route("crm.internal_chat.send") }}';
        const csrfToken = '{{ csrf_token() }}';
        let agentListLoading = false;
        let agentListController = null;

        function filterAgents() {
            const query = document.getElementById('agentSearch').value.toLowerCase();
            renderAgentList(query);
        }

        function loadAgentList() {
            if (agentListLoading || document.hidden) return Promise.resolve();
            agentListLoading = true;
            agentListController = new AbortController();
            const controller = agentListController;
            const timeout = setTimeout(() => controller.abort(), 12000);

            return fetch(agentsUrl, {
                    signal: controller.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => {
                    if (!res.ok) throw new Error(`Team request failed (${res.status})`);
                    return res.json();
                })
                .then(data => {
                    agentsData = data;
                    renderAgentList();
                    if (activeAgentId) {
                        const activeAgent = agentsData.find(a => a.id == activeAgentId);
                        if (activeAgent) {
                            updateActiveStatus(activeAgent);
                        }
                    }
                })
                .catch(err => {
                    if (err.name === 'AbortError') return;
                    document.getElementById('agentListContainer').innerHTML =
                        '<div style="padding:2rem; text-align:center; color:#ef4444;"><i class="fas fa-exclamation-triangle"></i><br>Failed to load team.<br><small>' + err.message + '</small></div>';
                })
                .finally(() => {
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

        function renderAgentList(filter = '') {
            const container = document.getElementById('agentListContainer');
            container.innerHTML = '';

            const filtered = agentsData.filter(a => a.name.toLowerCase().includes(filter));

            if (filtered.length === 0) {
                container.innerHTML = '<div style="padding:2rem; text-align:center; color:#94a3b8;">No teammates found.</div>';
                return;
            }

            filtered.forEach(agent => {
                let initials = agent.name.split(' ').filter(n => n && n !== '-').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const lastMsg = agent.last_message
                    ? agent.last_message.substring(0, 45) + (agent.last_message.length > 45 ? '...' : '')
                    : '<span style="color:#cbd5e1; font-style:italic;">No messages yet</span>';
                const lastTime = agent.last_message_at
                    ? new Date(agent.last_message_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
                    : '';
                const isActive = activeAgentId == agent.id ? 'active' : '';
                const unreadBadge = agent.unread_count > 0 ? `<span class="chat-badge">${agent.unread_count}</span>` : '';

                const statusClass = agent.online_status || 'offline';
                const statusLabel = statusClass === 'online' ? 'Online'
                    : (agent.last_seen_human && agent.last_seen_human !== 'Never' ? agent.last_seen_human : 'Offline');

                const item = document.createElement('div');
                item.className = `chat-item ${isActive}`;
                item.id = `agent-item-${agent.id}`;
                item.onclick = () => selectAgent(agent);
                item.innerHTML = `
                        <div class="chat-avatar">
                            ${initials}
                            <span class="online-dot ${statusClass}"></span>
                        </div>
                        <div class="chat-info">
                            <span class="chat-name">${agent.name}</span>
                            <span class="chat-role" style="color: ${statusClass === 'online' ? '#10b981' : '#94a3b8'}; font-weight:600; font-size:0.72rem; display:block; margin-bottom:2px; text-transform:none;">${statusLabel}</span>
                            <span class="chat-last-msg">${lastMsg}</span>
                        </div>
                        <div class="chat-meta">
                            <div class="chat-time">${lastTime}</div>
                            ${unreadBadge}
                        </div>
                    `;
                container.appendChild(item);
            });
        }

        function updateActiveStatus(agent) {
            const statusEl = document.getElementById('activeStatus');
            const sClass = agent.online_status || 'offline';
            const sColor = sClass === 'online' ? '#10b981' : '#94a3b8';
            const sLabel = sClass === 'online' ? 'Online'
                : (agent.last_seen_human && agent.last_seen_human !== 'Never' ? 'Last seen ' + agent.last_seen_human : 'Offline');
            statusEl.style.color = sColor;
            statusEl.innerHTML = `<i class="fas fa-circle" style="font-size:7px;"></i> ${sLabel}`;
        }

        function selectAgent(agent) {
            activeAgentId = agent.id;
            activeAgentData = agent;

            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('agent', agent.id);
            window.history.pushState({}, '', url);

            toggleMobileView(true);

            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('chatHeader').style.display = 'flex';
            document.getElementById('messagesContainer').style.display = 'flex';
            document.getElementById('inputArea').style.display = 'block';

            let initials = agent.name.split(' ').filter(n => n && n !== '-').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            document.getElementById('activeAvatar').innerHTML = initials;
            document.getElementById('activeName').innerText = agent.name;

            // Update header status dynamically
            updateActiveStatus(agent);

            document.getElementById('messagesContainer').innerHTML = '';

            // Highlight active
            document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
            const activeItem = document.getElementById(`agent-item-${agent.id}`);
            if (activeItem) activeItem.classList.add('active');

            fetchTeamMessages();

            if (teamPollingInterval) clearInterval(teamPollingInterval);
            teamPollingInterval = setInterval(fetchTeamMessages, 4000);
        }

        function fetchTeamMessages(force = false) {
            if (!activeAgentId) return;

            fetch(`/crm/internal-chat/messages/${activeAgentId}`)
                .then(res => res.json())
                .then(messages => {
                    const container = document.getElementById('messagesContainer');
                    const currentCount = container.querySelectorAll('.msg-row').length;

                    // If force is true or count changed, re-render everything
                    if (force || messages.length !== currentCount) {
                        container.innerHTML = '';
                        let lastDateStr = null;

                        messages.forEach(msg => {
                            // Date separator
                            const msgDate = new Date(msg.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                            if (msgDate !== lastDateStr) {
                                lastDateStr = msgDate;
                                const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                                const yesterday = new Date(Date.now() - 86400000).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                                let label = msgDate;
                                if (msgDate === today) label = 'Today';
                                else if (msgDate === yesterday) label = 'Yesterday';

                                const sep = document.createElement('div');
                                sep.style.cssText = 'text-align:center; margin:1.5rem 0 1rem; align-self:center;';
                                sep.innerHTML = `<span style="background:#eef2ff; color:var(--primary-purple); padding:4px 14px; border-radius:99px; font-size:0.75rem; font-weight:600;">${label}</span>`;
                                container.appendChild(sep);
                            }

                            const isSent = msg.sender_id == currentUserId;
                            const time = new Date(msg.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
                            const isCustomerChat = String(activeAgentId).startsWith('customer-');

                            const row = document.createElement('div');
                            row.className = 'msg-row';
                            row.style.alignItems = isSent ? 'flex-end' : 'flex-start';
                            row.style.position = 'relative';

                            let attachmentHtml = '';
                            let hasMedia = false;
                            if (msg.attachment_path) {
                                hasMedia = true;
                                const isImage = msg.attachment_path.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                                const isVideo = msg.attachment_path.match(/\.(mp4|webm|ogg|mov)$/i);
                                const fullUrl = getFullUrl(msg.attachment_path);
                                
                                attachmentHtml = `
                                    <div class="media-container" id="media-${msg.id}">
                                        ${isImage ? `
                                            <img src="${fullUrl}" style="width:100%; max-height:320px; display:block; object-fit: cover;" onclick="window.open('${fullUrl}', '_blank')">
                                        ` : isVideo ? `
                                            <video style="width:100%; display:block; border-radius:12px;">
                                                <source src="${fullUrl}">
                                            </video>
                                        ` : `
                                            <div class="msg-attachment-placeholder" onclick="window.open('${fullUrl}', '_blank')">
                                                <i class="fas fa-file-alt" style="font-size:2rem; margin-bottom:10px; color:var(--primary-purple);"></i>
                                                <div style="font-size:0.8rem; font-weight:700; word-break:break-all;">${msg.attachment_name || 'View File'}</div>
                                            </div>
                                        `}
                                        <div class="media-overlay">
                                            <div class="media-dots" onclick="toggleMediaMenu(event, ${msg.id})">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </div>
                                        </div>
                                        <div class="media-dropdown" id="dropdown-${msg.id}" onclick="event.stopPropagation()">
                                            ${!isCustomerChat ? `
                                            <div class="media-item" onclick="openForwardModal(${msg.id}); closeAllMenus();">
                                                <i class="fas fa-share"></i> Forward
                                            </div>
                                            ` : ''}
                                            <div class="media-item" onclick="window.open('${fullUrl}', '_blank'); closeAllMenus();">
                                                <i class="fas fa-external-link-alt"></i> Open
                                            </div>
                                            <a href="${fullUrl}" download="${msg.attachment_name || 'file'}" class="media-item" style="text-decoration:none;" onclick="closeAllMenus()">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            ${!isCustomerChat ? `
                                            <div class="media-item delete" onclick="deleteTeamMessage(${msg.id}); closeAllMenus();">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                `;
                                if (!isImage && !isVideo) hasMedia = false; // Treat regular files as normal bubbles
                            }

                            const hasText = msg.message_body && msg.message_body.trim().length > 0;
                            const isMediaOnly = hasMedia && !hasText;
                            let forwardedLabel = msg.is_forwarded ? `<div class="msg-forwarded-label" style="${isMediaOnly ? 'color:#64748b; margin-bottom:8px;' : ''}"><i class="fas fa-share"></i> Forwarded</div>` : '';

                            const safeBody = msg.message_body ? msg.message_body.replace(/`/g, '\\`').replace(/\n/g, '\\n') : '';

                            let dropdownHtml = '';
                            if (isCustomerChat) {
                                dropdownHtml = `
                                    <div class="media-item" onclick="copyToClipboard(\`${safeBody}\`); closeAllMenus();">
                                        <i class="fas fa-copy"></i> Copy Text
                                    </div>
                                `;
                            } else {
                                dropdownHtml = `
                                    <div class="media-item" onclick="openForwardModal(${msg.id}); closeAllMenus();">
                                        <i class="fas fa-share"></i> Forward
                                    </div>
                                    ${isSent ? `
                                        <div class="media-item" onclick="startEditMessage(${msg.id}, \`${safeBody}\`); closeAllMenus();">
                                            <i class="fas fa-edit"></i> Edit
                                        </div>
                                    ` : ''}
                                    <div class="media-item" onclick="copyToClipboard(\`${safeBody}\`); closeAllMenus();">
                                        <i class="fas fa-copy"></i> Copy Text
                                    </div>
                                    <div class="media-item delete" onclick="deleteTeamMessage(${msg.id}); closeAllMenus();">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </div>
                                `;
                            }

                            row.innerHTML = `
                                    <div class="msg-bubble ${isSent ? 'msg-sent' : 'msg-received'}" 
                                         style="${isMediaOnly ? 'background:none; padding:0; box-shadow:none; border:none;' : ''}">

                                        ${!isMediaOnly ? `
                                            <div class="bubble-dots" onclick="toggleMediaMenu(event, ${msg.id})">
                                                <i class="fas fa-ellipsis-h" style="font-size:0.8rem;"></i>
                                            </div>
                                            <div class="media-dropdown" id="dropdown-${msg.id}" onclick="event.stopPropagation()" style="z-index:3000;">
                                                ${dropdownHtml}
                                            </div>
                                        ` : ''}

                                        ${forwardedLabel}
                                        ${hasText ? `<div style="margin-bottom:${hasMedia ? '10px' : '0'}; padding-right: 25px;">
                                            ${msg.message_body.replace(/\n/g, '<br>')}
                                        </div>` : ''}
                                        ${attachmentHtml}
                                    </div>
                                    <div class="msg-time">${time}</div>
                                `;
                            container.appendChild(row);
                        });

                        container.scrollTop = container.scrollHeight;
                        // Refresh agent list to clear unread badge
                        loadAgentList();
                    }
                })
                .catch(err => console.error('Fetch messages error:', err));
        }

        let selectedAttachment = null;
        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                selectedAttachment = input.files[0];
                document.getElementById('fileName').innerText = selectedAttachment.name;
                document.getElementById('attachmentPreview').style.display = 'flex';
            }
        }

        function clearAttachment() {
            selectedAttachment = null;
            document.getElementById('chatFile').value = '';
            document.getElementById('attachmentPreview').style.display = 'none';
        }

        function sendTeamMessage() {
            const input = document.getElementById('teamMsgInput');
            const body = input.value.trim();
            if (!body && !selectedAttachment && !isEditing) return;
            if (!activeAgentId) return;

            if (isEditing) {
                updateTeamMessage(editingMessageId, body);
                return;
            }

            const btn = document.getElementById('sendBtn');
            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const fd = new FormData();
            fd.append('receiver_id', activeAgentId);
            fd.append('message_body', body);
            if (selectedAttachment) {
                fd.append('attachment', selectedAttachment);
            }
            fd.append('_token', csrfToken);

            fetch(sendUrl, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(r => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                    if (r.success) {
                        input.value = '';
                        input.style.height = 'auto';
                        clearAttachment();
                        fetchTeamMessages(true); // Force refresh to show new message
                    } else {
                        alert('Failed to send message: ' + (r.message || 'Unknown error'));
                    }
                })
                .catch(e => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                    console.error('Send fetch error:', e);
                });
        }

        function updateTeamMessage(id, text) {
            fetch(`/crm/internal-chat/edit/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ message_body: text })
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        cancelEdit();
                        fetchTeamMessages(true); // Force refresh to show edited content
                    } else {
                        alert('Update failed');
                    }
                })
                .catch(err => console.error('Edit error:', err));
        }

        let messageToForward = null;
        let selectedForwardIds = [];

        function openForwardModal(msgId) {
            messageToForward = msgId;
            selectedForwardIds = [];
            document.getElementById('forwardSearchInput').value = '';
            renderForwardList();
            document.getElementById('forwardModal').style.display = 'flex';
        }

        function renderForwardList(filter = '') {
            const container = document.getElementById('forwardList');
            container.innerHTML = '';

            const filtered = agentsData.filter(agent =>
                agent.id !== currentUserId &&
                agent.name.toLowerCase().includes(filter.toLowerCase())
            );

            if (filtered.length === 0) {
                container.innerHTML = '<div style="padding:2rem; text-align:center; color:#94a3b8;">No teammates found.</div>';
                return;
            }

            filtered.forEach(agent => {
                const initials = agent.name.split(' ').filter(n => n).map(n => n[0]).join('').substring(0, 2).toUpperCase();
                const isSelected = selectedForwardIds.includes(agent.id);

                const item = document.createElement('div');
                item.className = `forward-agent-item ${isSelected ? 'selected' : ''}`;
                item.onclick = () => toggleForwardSelection(agent.id, item);
                item.innerHTML = `
                        <div class="initials">${initials}</div>
                        <div style="flex:1;">
                            <div style="font-weight:700; font-size:0.95rem; color:#1e293b;">${agent.name}</div>
                        </div>
                        <div class="checkbox">
                            <i class="${isSelected ? 'fas fa-check-circle' : 'far fa-circle'}" 
                               id="check-${agent.id}" 
                               style="color:${isSelected ? '#3b82f6' : '#cbd5e1'}; font-size:1.2rem;"></i>
                        </div>
                    `;
                container.appendChild(item);
            });
        }

        function filterForwardList() {
            const query = document.getElementById('forwardSearchInput').value;
            renderForwardList(query);
        }

        function toggleForwardSelection(id, el) {
            const index = selectedForwardIds.indexOf(id);
            const icon = document.getElementById(`check-${id}`);
            if (index > -1) {
                selectedForwardIds.splice(index, 1);
                el.classList.remove('selected');
                icon.className = 'far fa-circle';
                icon.style.color = '#cbd5e1';
            } else {
                selectedForwardIds.push(id);
                el.classList.add('selected');
                icon.className = 'fas fa-check-circle';
                icon.style.color = 'var(--primary-purple)';
            }
        }

        function closeForwardModal() {
            document.getElementById('forwardModal').style.display = 'none';
        }

        function confirmForward() {
            if (selectedForwardIds.length === 0) {
                alert('Please select at least one teammate.');
                return;
            }

            const btn = document.getElementById('confirmForwardBtn');
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Forwarding...';

            fetch('{{ route("crm.internal_chat.forward") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    message_id: messageToForward,
                    receiver_ids: selectedForwardIds
                })
            })
                .then(res => res.json())
                .then(res => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    if (res.success) {
                        closeForwardModal();
                        // If the active chat is one of the receivers, refresh it
                        if (selectedForwardIds.includes(activeAgentId)) {
                            fetchTeamMessages();
                        }
                        alert('Message forwarded successfully!');
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                    console.error('Forward error:', err);
                });
        }

        // Enter to send
        document.addEventListener('keydown', function (e) {
            if (e.target && e.target.id === 'teamMsgInput') {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendTeamMessage();
                }
            }
        });

        function toggleMobileView(active) {
            const container = document.getElementById('teamChatApp');
            if (active) {
                container.classList.add('chat-active');
            } else {
                container.classList.remove('chat-active');
                activeAgentId = null;
                // Remove agent from URL when going back on mobile
                const url = new URL(window.location);
                url.searchParams.delete('agent');
                window.history.pushState({}, '', url);
                if (teamPollingInterval) clearInterval(teamPollingInterval);
                closeChatSearch();
            }
        }

        // Message Search Functions
        function expandChatSearch() {
            document.getElementById('chatSearchWrapper').classList.add('expanded');
            document.getElementById('msgSearchInput').focus();
        }

        function closeChatSearch() {
            const wrapper = document.getElementById('chatSearchWrapper');
            wrapper.classList.remove('expanded');
            document.getElementById('msgSearchInput').value = '';
            document.getElementById('clearSearchIcon').style.display = 'none';
            filterMessages();
        }

        function clearMsgSearch(e) {
            e.stopPropagation();
            document.getElementById('msgSearchInput').value = '';
            document.getElementById('clearSearchIcon').style.display = 'none';
            filterMessages();
            document.getElementById('msgSearchInput').focus();
        }

        function filterMessages() {
            const query = document.getElementById('msgSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#messagesContainer .msg-row');
            const clearBtn = document.getElementById('clearSearchIcon');

            clearBtn.style.display = query ? 'block' : 'none';

            rows.forEach(row => {
                const bubble = row.querySelector('.msg-bubble');
                const text = bubble.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });

            // Also hide date separators if no messages are visible under them
            const separators = document.querySelectorAll('#messagesContainer > div:not(.msg-row)');
            separators.forEach(sep => {
                let next = sep.nextElementSibling;
                let hasVisible = false;
                while (next && next.classList.contains('msg-row')) {
                    if (next.style.display !== 'none') {
                        hasVisible = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }
                sep.style.display = hasVisible ? 'block' : 'none';
            });
        }

        // Init
        renderAgentList();
        loadAgentList();
        setInterval(loadAgentList, 5000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) resumeAgentList();
        });
        window.addEventListener('pageshow', resumeAgentList);

        // Auto-select agent from URL
        window.addEventListener('load', () => {
            setTimeout(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const agentId = urlParams.get('agent');
                if (agentId && agentsData.length > 0) {
                    const agent = agentsData.find(a => a.id == agentId);
                    if (agent) selectAgent(agent);
                }
            }, 800); // Small delay to ensure agentsData is loaded
        });

        // Media Menu Logic
        function toggleMediaMenu(e, id) {
            e.stopPropagation();
            const dropdown = document.getElementById(`dropdown-${id}`);
            if (!dropdown) return;

            const isOpen = dropdown.classList.contains('show');
            closeAllMenus();
            
            if (!isOpen) {
                // Check if we should open upwards
                const rect = e.currentTarget.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                if (rect.bottom > viewportHeight - 240) {
                    dropdown.classList.add('open-up');
                } else {
                    dropdown.classList.remove('open-up');
                }
                dropdown.classList.add('show');
            }
        }

        function closeAllMenus() {
            document.querySelectorAll('.media-dropdown').forEach(d => d.classList.remove('show'));
        }

        window.addEventListener('click', closeAllMenus);

        function deleteTeamMessage(id) {
            if (!confirm('Are you sure you want to delete this message?')) return;

            fetch(`/crm/internal-chat/delete/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        fetchTeamMessages();
                    } else {
                        alert('Delete failed: ' + (res.message || 'Unknown error'));
                    }
                })
                .catch(err => console.error('Delete error:', err));
        }

        let isEditing = false;
        let editingMessageId = null;

        function startEditMessage(id, text) {
            isEditing = true;
            editingMessageId = id;
            const input = document.getElementById('teamMsgInput');
            input.value = text;
            input.focus();
            document.getElementById('editModeBar').style.display = 'flex';
            document.getElementById('sendBtnIcon').className = 'fas fa-check';
        }

        function cancelEdit() {
            isEditing = false;
            editingMessageId = null;
            const input = document.getElementById('teamMsgInput');
            if (input) input.value = '';

            const bar = document.getElementById('editModeBar');
            if (bar) bar.style.setProperty('display', 'none', 'important');

            const icon = document.getElementById('sendBtnIcon');
            if (icon) icon.className = 'fas fa-paper-plane';
        }

        function copyToClipboard(text) {
            const el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            alert('Copied to clipboard');
        }
    </script>
@endsection
