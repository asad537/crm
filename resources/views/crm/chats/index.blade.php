@extends('crm.layout')
@section('title', 'Live Chat Center')

@section('styles')
    /* Full height layout adjustment */
    .main-area { padding: 0 !important; overflow: hidden !important; background: #fff; display: flex; flex-direction:
    column; }
    .top-bar {
    display: none !important;
    }

    .chat-container {
    display: flex;
    height: 100vh;
    width: 100%;
    background: white;
    }

    /* Left Sidebar: Contact List */
    .chat-list-sidebar {
    width: 380px;
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

    .chat-list-items {
    flex: 1;
    overflow-y: auto;
    }

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
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: #ecf0ff;
    color: var(--primary-purple);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    font-size: 1.1rem;
    }

    .chat-info { flex: 1; min-width: 0; }
    .chat-name { font-weight: 600; color: #1e293b; font-size: 0.95rem; margin-bottom: 2px; display: block; }
    .chat-product { font-size: 0.75rem; color: var(--primary-purple); font-weight: 700; text-transform: uppercase; margin-bottom: 4px;
    display: block; }
    .chat-last-msg { font-size: 0.85rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    display: block; }
    .chat-meta { text-align: right; min-width: 95px; }
    .chat-time { font-size: 0.75rem; color: #94a3b8; white-space: nowrap; }
    .chat-badge {
    background: #ef4444; color: white; border-radius: 99px;
    font-size: 0.7rem; padding: 2px 8px; font-weight: 700;
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
    gap: 1.5rem;
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

    .email-copy-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    }

    .email-copy-field {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.55rem 0.75rem;
    }

    .email-copy-field label {
    color: #475569;
    font-size: 0.8rem;
    font-weight: 700;
    }

    .email-copy-field input {
    flex: 1;
    min-width: 0;
    border: none;
    background: transparent;
    outline: none;
    color: #1e293b;
    font: inherit;
    font-size: 0.82rem;
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

    .send-btn {
    background: var(--primary-purple);
    color: white;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: none;
    transition: transform 0.2s;
    }
    .send-btn:hover { transform: scale(1.05); background: var(--primary-purple); }

    .msg-row { display: flex; flex-direction: column; width: 100%; }
    .msg-bubble {
    max-width: 75%;
    padding: 0.85rem 1.1rem;
    border-radius: 14px;
    font-size: 0.95rem;
    line-height: 1.5;
    position: relative;
    }
    .msg-bubble > *:first-child { margin-top: 0; }
    .msg-bubble > *:last-child { margin-bottom: 0; }
    .msg-bubble p { margin-top: 0; margin-bottom: 0; }

    .msg-admin {
    align-self: flex-end;
    background: linear-gradient(
        135deg,
        color-mix(in srgb, var(--primary-purple) 20%, #fff) 0%,
        color-mix(in srgb, var(--primary-purple) 34%, #fff) 100%
    );
    color: #273449;
    border: 1px solid color-mix(in srgb, var(--primary-purple) 22%, #fff);
    border-bottom-right-radius: 4px;
    box-shadow: 0 8px 20px color-mix(in srgb, var(--primary-shadow) 60%, transparent);
    }

    .msg-client {
    align-self: flex-start;
    background: white;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .msg-time { font-size: 0.7rem; margin-top: 5px; color: #94a3b8; }
    .msg-admin .msg-time { color: #64748b; opacity: 0.9; }

    /* Empty State */
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

    /* Responsive */
    @media (max-width: 1024px) {
    .chat-list-sidebar { width: 300px; }
    }

    @media (max-width: 768px) {
    .chat-container { height: 100vh; }

    .chat-list-sidebar {
    width: 100%;
    display: flex;
    }

    .chat-main {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    z-index: 1000;
    display: none;
    }

    .chat-container.chat-active .chat-list-sidebar { display: none; }
    .chat-container.chat-active .chat-main { display: flex; }

    .chat-header { padding: 1rem; height: auto; min-height: 65px; }
    .chat-messages-container { padding: 1rem; gap: 1rem; }
    .chat-input-area { padding: 1rem; }

    .email-copy-fields { grid-template-columns: 1fr; }

    #mobileBackBtn { display: flex !important; }
    .view-lead-text { display: none; }
    }
@endsection

@section('content')
    <div class="chat-container" id="app">
        <!-- Chat List Sidebar -->
        <div class="chat-list-sidebar">
            <div class="chat-list-header">
                <div style="display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"
                        style="margin-right:0; margin-bottom:1rem;"></i>
                    <h2>Live Chat</h2>
                </div>
                <div class="search-chat">
                    <i class="fas fa-search" style="color:#94a3b8"></i>
                    <input type="text" id="chatSearch" placeholder="Search conversations..." onkeyup="filterChats()">
                </div>
            </div>
            <div class="chat-list-items" id="chatListContainer">
                <!-- Loaded via JS -->
                <div style="text-align:center; padding: 2rem; color: #94a3b8;">
                    <i class="fas fa-spinner fa-spin"></i> Loading chats...
                </div>
            </div>
        </div>

        <!-- Chat Main Area -->
        <div class="chat-main" id="chatWindow">
            <div class="chat-empty" id="emptyState">
                <i class="fas fa-comments"></i>
                <h3>Select a conversation</h3>
                <p>Pick a client from the list on the left to start messaging.</p>
            </div>

            <!-- Chat Header (Hidden by default) -->
            <div class="chat-header" id="chatHeader" style="display:none">
                <div style="display:flex; align-items:center; gap:12px;">
                    <button id="mobileBackBtn" onclick="toggleMobileView(false)"
                        style="display:none; background:none; border:none; color:#64748b; font-size:1.2rem; cursor:pointer; padding:0 5px;">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="chat-avatar" id="activeAvatar">U</div>
                    <div style="min-width: 0; display: flex; align-items: center; gap: 6px;">
                        <div class="chat-name" id="activeName"
                            style="margin:0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">User Name
                        </div>
                        <i class="fas fa-circle" style="font-size:8px; color:#10b981;"></i>
                    </div>
                </div>
                <div>
                    <a href="#" id="viewLeadBtn" class="btn-action"
                        style="padding: 0.5rem 1rem; background: #fff; border: 1px solid #e2e8f0; color: #475569; font-size: 0.8rem; border-radius: 8px; text-decoration:none; display: flex; align-items: center; gap: 5px;">
                        <span class="view-lead-text">View Case</span> <i class="fas fa-external-link-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="chat-messages-container" id="messagesContainer" style="display:none">
                <!-- Messages load here -->
            </div>

            <div class="chat-input-area" id="inputArea" style="display:none">
                @if(Auth::guard('crm')->user()->isAdmin())
                    <div style="text-align: center; color: #94a3b8; padding: 1rem;">
                        Admin can view chat only
                    </div>
                @else
                    <form id="chatForm" enctype="multipart/form-data">
                        {{ csrf_field() }}
                        <div id="attachment-tray"
                            style="display:none; padding: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <!-- Previews go here -->
                        </div>
                        <input type="hidden" name="email_subject" id="chatEmailSubject">
                        <input type="hidden" name="cc" id="chatCcField">
                        <input type="hidden" name="bcc" id="chatBccField">
                        <div class="chat-input-wrapper">
                            <label for="fileInput" class="send-btn"
                                style="background: #f1f5f9; color: #64748b; font-size: 1.2rem; cursor: pointer;">
                                <i class="fas fa-paperclip"></i>
                                <input type="file" id="fileInput" name="attachments[]" multiple style="display:none"
                                    onchange="handleFileSelect(this)">
                            </label>
                            <textarea id="messageInput" name="message_body" placeholder="Write your message here..." rows="1"
                                oninput="autoExpand(this)"></textarea>
                            <button type="submit" class="send-btn" id="sendBtn"
                                style="width: auto; padding: 0 1.5rem; border-radius: 12px; font-weight: 600; display: flex; gap: 8px;">
                                <span id="sendBtnText">Send</span>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div
                            style="font-size: 0.8rem; color: #64748b; margin-top: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-info-circle"></i> This will send an email to <strong
                                id="activeEmail">client@example.com</strong>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div id="emailMetaModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:99999; align-items:center; justify-content:center; padding:20px;">
        <div style="width:100%; max-width:560px; background:#fff; border-radius:18px; box-shadow:0 24px 60px rgba(15,23,42,.25); overflow:hidden;">
            <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between;">
                <div style="font-size:1rem; font-weight:800; color:#0f172a;">Email Details</div>
                <button type="button" onclick="closeEmailMetaModal()" style="border:none; background:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <div style="padding:20px 22px;">
                <div style="display:grid; gap:12px;">
                    <div>
                        <label style="display:block; font-size:.82rem; font-weight:700; color:#475569; margin-bottom:6px;">Subject</label>
                        <input type="text" id="modalSubject" style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:.95rem; outline:none;">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div>
                            <label style="display:block; font-size:.82rem; font-weight:700; color:#475569; margin-bottom:6px;">CC</label>
                            <input type="text" id="modalCc" placeholder="cc@example.com, cc2@example.com" style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:.9rem; outline:none;">
                        </div>
                        <div>
                            <label style="display:block; font-size:.82rem; font-weight:700; color:#475569; margin-bottom:6px;">BCC</label>
                            <input type="text" id="modalBcc" placeholder="bcc@example.com" style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:.9rem; outline:none;">
                        </div>
                    </div>
                </div>
            </div>
            <div style="padding:16px 22px 22px; display:flex; gap:10px; justify-content:flex-end; border-top:1px solid #e2e8f0; background:#f8fafc;">
                <button type="button" onclick="closeEmailMetaModal()" style="border:1px solid #cbd5e1; background:#fff; color:#475569; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer;">Cancel</button>
                <button type="button" onclick="submitEmailMeta()" style="border:none; background:var(--primary-purple); color:#fff; border-radius:10px; padding:10px 16px; font-weight:800; cursor:pointer;">Send Reply</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let activeChatId = null;
        let chatsData = [];
        let lastMsgId = 0;
        let pollingInterval = null;
        let lastDisplayedDateStr = null;
        let pendingChatForm = null;
        let chatListLoading = false;
        let chatListController = null;
        let inboxSyncRunning = false;

        function syncInbox() {
            if (inboxSyncRunning || document.hidden) return;
            inboxSyncRunning = true;
            fetch('{{ route("crm.chats.sync") }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`Inbox sync failed (${response.status})`);
                    return response.json();
                })
                .then(() => {
                    loadChatList();
                    if (activeChatId) fetchMessages();
                })
                .catch(error => console.error('Inbox sync error:', error))
                .finally(() => { inboxSyncRunning = false; });
        }

        function autoExpand(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        function filterChats() {
            const query = document.getElementById('chatSearch').value.toLowerCase();
            renderChatList(query);
        }

        function loadChatList() {
            if (chatListLoading) return Promise.resolve();
            chatListLoading = true;
            chatListController = new AbortController();
            const controller = chatListController;
            const timeout = setTimeout(() => controller.abort(), 12000);

            return fetch('{{ route("crm.chats.list") }}', {
                    signal: controller.signal,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => {
                    if (!res.ok) throw new Error(`Chat list request failed (${res.status})`);
                    return res.json();
                })
                .then(data => {
                    chatsData = Array.isArray(data) ? data : [];
                    renderChatList();
                })
                .catch(error => {
                    if (error.name === 'AbortError') return;
                    console.error('Chat list error:', error);
                    if (!chatsData.length) {
                        document.getElementById('chatListContainer').innerHTML = `
                            <div style="padding:2rem;text-align:center;color:#64748b">
                                <div style="margin-bottom:.75rem">Chats could not be loaded.</div>
                                <button type="button" onclick="loadChatList()" style="border:1px solid #cbd5e1;background:#fff;color:var(--primary-purple);border-radius:8px;padding:.5rem .9rem;font-weight:700;cursor:pointer">Retry</button>
                            </div>`;
                    }
                })
                .finally(() => {
                    clearTimeout(timeout);
                    if (chatListController === controller) {
                        chatListController = null;
                        chatListLoading = false;
                    }
                });
        }

        function resumeChatList() {
            if (document.hidden) return;
            if (chatListController) chatListController.abort();
            chatListController = null;
            chatListLoading = false;
            loadChatList();
            if (activeChatId) fetchMessages();
        }

        function renderChatList(filter = '') {
            const container = document.getElementById('chatListContainer');
            container.innerHTML = '';

            const filtered = chatsData.filter(chat =>
                (chat.client_name || '').toLowerCase().includes(filter) ||
                (chat.client_email || '').toLowerCase().includes(filter)
            );

            if (filtered.length === 0) {
                container.innerHTML = '<div style="padding:2rem; text-align:center; color:#94a3b8">No conversations found.</div>';
                return;
            }

            filtered.forEach(chat => {
                const lastMessage = chat.latest_message || null;
                const time = lastMessage ? moment.utc(lastMessage.created_at).local().format('MMM D, h:mm A') : '';
                const isActive = activeChatId == chat.id ? 'active' : '';
                const unreadBadge = chat.unread_count > 0 ? `<span class="chat-badge">${chat.unread_count}</span>` : '';

                const initials = chat.client_name ? chat.client_name.split(' ').filter(n => n).map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

                const item = document.createElement('div');
                item.className = `chat-item ${isActive}`;
                item.onclick = () => selectChat(chat.id);
                item.innerHTML = `
                    <div class="chat-avatar">${initials}</div>
                    <div class="chat-info">
                        <span class="chat-name">${chat.client_name || 'Anonymous User'}</span>
                        <span class="chat-product">${chat.product_name || 'General Inquiry'}</span>
                    </div>
                    <div class="chat-meta">
                        <div class="chat-time">${time}</div>
                        ${unreadBadge}
                    </div>
                `;
                container.appendChild(item);
            });
        }

        function selectChat(id) {
            activeChatId = id;
            const chat = chatsData.find(c => c.id == id);
            if (!chat) return;

            // Mobile Toggle
            toggleMobileView(true);

            // Update UI
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('chatHeader').style.display = 'flex';
            document.getElementById('messagesContainer').style.display = 'flex';
            document.getElementById('inputArea').style.display = 'block';

            document.getElementById('activeName').innerText = chat.client_name || 'Anonymous User';
            if (document.getElementById('activeEmail')) {
                document.getElementById('activeEmail').innerText = chat.client_email;
            }
            document.getElementById('viewLeadBtn').href = `/crm/email/${chat.id}`;

            const initials = chat.client_name ? chat.client_name.split(' ').filter(n => n).map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
            document.getElementById('activeAvatar').innerText = initials;

            // Highlighting
            document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
            renderChatList(document.getElementById('chatSearch').value.toLowerCase());

            // Load Messages
            lastMsgId = 0;
            lastDisplayedDateStr = null;
            document.getElementById('messagesContainer').innerHTML = '';
            fetchMessages();

            // Start polling for this chat
            if (pollingInterval) clearInterval(pollingInterval);
            pollingInterval = setInterval(fetchMessages, 5000);
        }

        function fetchMessages() {
            if (!activeChatId) return;

            fetch(`/crm/email/${activeChatId}/messages?last_id=${lastMsgId}`)
                .then(res => res.json())
                .then(messages => {
                    if (messages.length > 0) {
                        messages.forEach(msg => {
                            appendMessage(msg);
                            lastMsgId = msg.id;
                        });
                        scrollToBottom();

                        // Also refresh chat list unread counts
                        loadChatList();
                    }
                });
        }

        function appendMessage(msg) {
            const container = document.getElementById('messagesContainer');

            // 🚨 DUPLICATE CHECK: Don't add if already exists in DOM
            if (document.getElementById(`msg-${msg.id}`)) return;

            const msgDate = moment(msg.created_at).format('MMM D, YYYY');
            if (msgDate !== lastDisplayedDateStr) {
                lastDisplayedDateStr = msgDate;
                const today = moment().format('MMM D, YYYY');
                const yesterday = moment().subtract(1, 'days').format('MMM D, YYYY');
                let displayDate = msgDate;
                if (msgDate === today) displayDate = 'Today';
                else if (msgDate === yesterday) displayDate = 'Yesterday';

                const dateHeader = document.createElement('div');
                dateHeader.style.cssText = "text-align:center; margin: 1.5rem 0 1rem; position: relative; width: 100%; align-self: center;";
                dateHeader.innerHTML = `<span style="background: #eef2ff; color: var(--primary-purple); padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 600;">${displayDate}</span>`;
                container.appendChild(dateHeader);
            }

            const isSelf = msg.sender_type === 'admin';
            const time = moment(msg.created_at).format('h:mm A');

            const row = document.createElement('div');
            row.id = `msg-${msg.id}`; // Assign unique ID
            row.className = 'msg-row';
            row.style.alignItems = isSelf ? 'flex-end' : 'flex-start';

            const hasText = msg.message_body && msg.message_body.trim().length > 0;
            let attachmentsHtml = '';
            let hasImageAttachment = false;

            const hasAttachments = msg.attachments && msg.attachments.length > 0;
            const isOnlyImage = !hasText && hasAttachments && msg.attachments.every(path => /\.(jpg|jpeg|png|gif|webp)$/i.test(path));

            if (hasAttachments) {
                attachmentsHtml = '<div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:8px;">';
                msg.attachments.forEach(path => {
                    const isImg = /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(path);
                    const baseUrl = window.location.origin;
                    if (isImg) {
                        hasImageAttachment = true;
                        attachmentsHtml += `<a href="${baseUrl}/${path}" target="_blank" style="display:block; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1);"><img src="${baseUrl}/${path}" style="max-width:280px; max-height:350px; display:block; object-fit:cover;"></a>`;
                    } else {
                        const filename = path.split('/').pop();
                        attachmentsHtml += `<a href="${baseUrl}/${path}" target="_blank" style="display:flex; align-items:center; gap:8px; padding:8px 12px; background:${isSelf ? 'color-mix(in srgb, var(--primary-purple) 22%, #fff)' : '#fff'}; border:1px solid ${isSelf ? 'color-mix(in srgb, var(--primary-purple) 28%, #fff)' : '#e2e8f0'}; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.05); text-decoration:none; color:#273449; font-size:0.8rem;"><i class="fas fa-file" style="color:${isSelf ? 'var(--primary-purple)' : '#64748b'}"></i> ${filename}</a>`;
                    }
                });
                attachmentsHtml += '</div>';
            }

            let bubbleStyle = '';
            const isTemplate = hasText && msg.message_body.includes('Custom Packaging Quote');
            
            if (isOnlyImage) {
                bubbleStyle = 'background:none; padding:0; box-shadow:none;';
            } else if (isTemplate) {
                bubbleStyle = 'background:transparent; padding:0; box-shadow:none; max-width:100%; color:inherit;';
            }

            let bodyHtml = hasText ? (isSelf ? msg.message_body : msg.message_body.replace(/\n/g, '<br>')) : '';
            if (hasText && hasImageAttachment) {
                // Text is slightly larger when there is an image
                bodyHtml = `<div style="font-size: 1.15rem; margin-bottom: 8px;">${bodyHtml}</div>`;
            }

            const bubbleClass = isTemplate ? '' : (isSelf ? 'msg-admin' : 'msg-client');

            row.innerHTML = '';
            if (hasText) {
                row.innerHTML += `
                    <div class="msg-bubble ${bubbleClass}" style="${bubbleStyle}">
                        ${bodyHtml}
                    </div>
                `;
            }
            if (hasAttachments) {
                row.innerHTML += `
                    <div style="margin-top: ${hasText ? '5px' : '0'}; display: flex; flex-direction: column; align-items: ${isSelf ? 'flex-end' : 'flex-start'};">
                        ${attachmentsHtml}
                    </div>
                `;
            }
            row.innerHTML += `<div class="msg-time">${time} ${isSelf ? '• ' + (msg.user ? msg.user.name : 'Admin') : ''}</div>`;
            container.appendChild(row);
        }

        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        }

        // Handle Sending
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.onsubmit = function (e) {
                e.preventDefault();
                const form = e.target;
                const input = document.getElementById('messageInput');
                const btn = document.getElementById('sendBtn');
                const btnText = document.getElementById('sendBtnText');
                const body = input.value.trim();

                if (!body && !document.getElementById('fileInput').files.length) return;

                pendingChatForm = form;
                const chat = chatsData.find(c => c.id == activeChatId);
                document.getElementById('modalSubject').value = chat ? ("Re: " + (chat.subject || "")) : "Re: Your Inquiry";
                document.getElementById('modalCc').value = form.querySelector('[name="cc"]').value || '';
                document.getElementById('modalBcc').value = form.querySelector('[name="bcc"]').value || '';
                openEmailMetaModal();
            };
        }

        function openEmailMetaModal() {
            document.getElementById('emailMetaModal').style.display = 'flex';
            document.getElementById('modalSubject').focus();
        }

        function closeEmailMetaModal() {
            document.getElementById('emailMetaModal').style.display = 'none';
            pendingChatForm = null;
        }

        function submitEmailMeta() {
            const subject = document.getElementById('modalSubject').value.trim();
            if (!subject) {
                alert('Email subject is required to send a message.');
                return;
            }
            if (!pendingChatForm) return;

            pendingChatForm.querySelector('[name="email_subject"]').value = subject;
            pendingChatForm.querySelector('[name="cc"]').value = document.getElementById('modalCc').value.trim();
            pendingChatForm.querySelector('[name="bcc"]').value = document.getElementById('modalBcc').value.trim();

            const form = pendingChatForm;
            const input = document.getElementById('messageInput');
            const btn = document.getElementById('sendBtn');
            const btnText = document.getElementById('sendBtnText');

            btn.disabled = true;
            btn.style.opacity = '0.7';
            btnText.innerText = 'Sending...';

            const formData = new FormData(form);

            fetch(`/crm/email/${activeChatId}/message`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        appendMessage(res.data);
                        lastMsgId = res.data.id;
                        form.reset();
                        input.style.height = 'auto';
                        document.getElementById('attachment-tray').style.display = 'none';
                        scrollToBottom();
                        loadChatList();
                        closeEmailMetaModal();
                    } else {
                        alert('Error: ' + res.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Failed to send message.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btnText.innerText = 'Send';
                });
        }

        function handleFileSelect(input) {
            const tray = document.getElementById('attachment-tray');
            tray.innerHTML = '';
            if (input.files.length > 0) {
                tray.style.display = 'flex';
                Array.from(input.files).forEach(file => {
                    const item = document.createElement('div');
                    item.style.padding = '4px 8px';
                    item.style.background = '#eef2ff';
                    item.style.borderRadius = '6px';
                    item.style.fontSize = '0.75rem';
                    item.style.color = 'var(--primary-purple)';
                    item.innerHTML = `<i class="fas fa-file"></i> ${file.name}`;
                    tray.appendChild(item);
                });
            } else {
                tray.style.display = 'none';
            }
        }

        function toggleMobileView(active) {
            const container = document.getElementById('app');
            if (active) {
                container.classList.add('chat-active');
            } else {
                container.classList.remove('chat-active');
                activeChatId = null;
                if (pollingInterval) clearInterval(pollingInterval);
            }
        }

        // Initial Load
        loadChatList();
        setInterval(loadChatList, 10000); // Peer list refresh
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) resumeChatList();
        });
        window.addEventListener('pageshow', resumeChatList);
        // Email import runs through crm:imap-daemon. Running it from the browser
        // blocked chat-list requests on single-worker/local servers.
    </script>
@endsection
