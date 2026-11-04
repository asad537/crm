{{-- Team Chat calls. Lives outside .main-area so a call carries on while the user moves between CRM pages. --}}
@if(Auth::guard('crm')->check() && session('crm_workspace_id'))
<style>
    .cc-incoming { position:fixed; right:20px; bottom:20px; z-index:12000; width:320px; max-width:calc(100vw - 32px); background:#0f172a; color:#fff; border-radius:18px; padding:1.1rem; box-shadow:0 24px 60px -12px rgba(15,23,42,.6); display:none; }
    .cc-incoming.show { display:block; animation:ccUp .25s ease-out; }
    .cc-incoming-top { display:flex; align-items:center; gap:.8rem; margin-bottom:1rem; }
    .cc-avatar { width:52px; height:52px; border-radius:50%; background:var(--primary-purple,#f45a24); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.05rem; flex-shrink:0; }
    .cc-incoming .cc-avatar { animation:ccPulse 1.4s infinite; }
    .cc-incoming-kind { font-size:.75rem; color:#94a3b8; }
    .cc-incoming-name { font-weight:800; font-size:1rem; word-break:break-word; }
    .cc-incoming-actions { display:flex; gap:.6rem; }
    .cc-incoming-actions button { flex:1; border:none; color:#fff; border-radius:12px; padding:.65rem; font-weight:800; font-size:.85rem; cursor:pointer; font-family:inherit; }
    .cc-accept { background:#16a34a; } .cc-accept:hover { background:#15803d; }
    .cc-decline { background:#dc2626; } .cc-decline:hover { background:#b91c1c; }

    .cc-stage { position:fixed; inset:0; z-index:11900; background:#0f172a; color:#fff; display:none; overflow:hidden; }
    .cc-stage.show { display:block; }
    .cc-remote { position:absolute; inset:0; width:100%; height:100%; object-fit:contain; background:#0f172a; opacity:0; }
    .cc-stage.has-video .cc-remote { opacity:1; }
    .cc-local { position:absolute; right:20px; top:20px; width:210px; max-width:32vw; aspect-ratio:4/3; object-fit:cover; border-radius:14px; background:#1e293b; border:2px solid rgba(255,255,255,.25); transform:scaleX(-1); display:none; }
    .cc-stage.is-video .cc-local { display:block; }
    .cc-stage.cam-off .cc-local { opacity:.25; }
    .cc-person { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem; text-align:center; padding:1rem; pointer-events:none; }
    .cc-person .cc-avatar { width:112px; height:112px; font-size:2.2rem; margin-bottom:.6rem; }
    .cc-stage.has-video .cc-person .cc-avatar { display:none; }
    .cc-stage.has-video .cc-person { justify-content:flex-start; align-items:flex-start; padding:20px; text-shadow:0 1px 4px rgba(0,0,0,.7); }
    .cc-name { font-size:1.4rem; font-weight:800; }
    .cc-status { font-size:.9rem; color:#cbd5e1; font-variant-numeric:tabular-nums; }
    .cc-controls { position:absolute; left:50%; bottom:28px; transform:translateX(-50%); display:flex; gap:.8rem; padding:.7rem .9rem; background:rgba(15,23,42,.72); border-radius:99px; }
    .cc-btn { width:52px; height:52px; border-radius:50%; border:none; background:rgba(255,255,255,.14); color:#fff; font-size:1.05rem; cursor:pointer; display:flex; align-items:center; justify-content:center; }
    .cc-btn:hover { background:rgba(255,255,255,.26); }
    .cc-btn.off { background:#fff; color:#0f172a; }
    .cc-btn.hang { background:#dc2626; } .cc-btn.hang:hover { background:#b91c1c; }
    .cc-btn.cam { display:none; }
    .cc-stage.is-video .cc-btn.cam { display:flex; }

    /* Minimised: a small card, so the CRM can be used during the call. */
    .cc-stage.min { inset:auto 20px 20px auto; width:300px; max-width:calc(100vw - 32px); height:190px; border-radius:18px; box-shadow:0 24px 60px -12px rgba(15,23,42,.6); }
    .cc-stage.min .cc-local { width:74px; right:10px; top:10px; border-radius:9px; }
    .cc-stage.min .cc-person { padding:10px; gap:.1rem; justify-content:flex-start; align-items:flex-start; }
    .cc-stage.min .cc-person .cc-avatar { display:none; }
    .cc-stage.min .cc-name { font-size:.95rem; }
    .cc-stage.min .cc-status { font-size:.75rem; }
    .cc-stage.min .cc-controls { bottom:8px; gap:.35rem; padding:.3rem .45rem; }
    .cc-stage.min .cc-btn { width:38px; height:38px; font-size:.85rem; }
    @keyframes ccUp { from { transform:translateY(18px); opacity:0; } to { transform:none; opacity:1; } }
    @keyframes ccPulse { 0%,100% { box-shadow:0 0 0 0 rgba(244,90,36,.55); } 60% { box-shadow:0 0 0 12px rgba(244,90,36,0); } }
    @media (max-width:600px) { .cc-local { width:110px; right:12px; top:12px; } .cc-controls { bottom:18px; } }
</style>

<div class="cc-incoming" id="ccIncoming" role="alertdialog" aria-labelledby="ccIncomingName">
    <div class="cc-incoming-top">
        <div class="cc-avatar" id="ccIncomingAvatar"></div>
        <div>
            <div class="cc-incoming-kind" id="ccIncomingKind"></div>
            <div class="cc-incoming-name" id="ccIncomingName"></div>
        </div>
    </div>
    <div class="cc-incoming-actions">
        <button type="button" class="cc-decline" id="ccDeclineBtn"><i class="fas fa-phone-slash"></i> Decline</button>
        <button type="button" class="cc-accept" id="ccAcceptBtn"><i class="fas fa-phone"></i> Accept</button>
    </div>
</div>

<div class="cc-stage" id="ccStage">
    <video class="cc-remote" id="ccRemote" autoplay playsinline></video>
    <video class="cc-local" id="ccLocal" autoplay playsinline muted></video>
    <div class="cc-person">
        <div class="cc-avatar" id="ccAvatar"></div>
        <div class="cc-name" id="ccName"></div>
        <div class="cc-status" id="ccStatus"></div>
    </div>
    <div class="cc-controls">
        <button type="button" class="cc-btn" id="ccMuteBtn" title="Mute" aria-label="Mute"><i class="fas fa-microphone"></i></button>
        <button type="button" class="cc-btn cam" id="ccCamBtn" title="Turn camera off" aria-label="Turn camera off"><i class="fas fa-video"></i></button>
        <button type="button" class="cc-btn" id="ccMinBtn" title="Minimise" aria-label="Minimise"><i class="fas fa-compress-alt"></i></button>
        <button type="button" class="cc-btn hang" id="ccHangBtn" title="End call" aria-label="End call"><i class="fas fa-phone-slash"></i></button>
    </div>
</div>

<script>
(function () {
    if (window.CrmCall) return;

    var baseUrl = @json(url('crm/internal-chat/calls'));
    var csrf = @json(csrf_token());
    var iceServers = @json(config('crm.calls.ice_servers'));
    var POLL_MS = 1500, INCOMING_MS = 4000, ICE_WAIT_MS = 2500;
    var el = function (id) { return document.getElementById(id); };

    // The call on this tab, if any.
    var call = null;
    var incoming = null;
    var dismissed = {};
    var titleTimer = null, originalTitle = null;

    function toast(message, type) {
        if (typeof window.showToast === 'function') window.showToast(message, type || 'error');
        else alert(message);
    }

    function initials(name) {
        return String(name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map(function (p) { return p[0]; }).join('').toUpperCase();
    }

    function post(path, body) {
        return fetch(baseUrl + path, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body || {})
        });
    }

    function get(path) {
        return fetch(baseUrl + path, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    }

    // ── Sounds: made here, so there is no audio file to ship ─────────────────
    var audioCtx = null, toneTimer = null;
    function beep(freq, seconds, volume) {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            var osc = audioCtx.createOscillator(), gain = audioCtx.createGain();
            osc.frequency.value = freq;
            gain.gain.value = volume;
            osc.connect(gain); gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + seconds);
        } catch (e) { /* no sound is not worth failing a call for */ }
    }
    function startTone(kind) {
        stopTone();
        var play = kind === 'ring'
            ? function () { beep(880, .25, .12); setTimeout(function () { beep(660, .3, .12); }, 300); }
            : function () { beep(440, .5, .04); };
        play();
        toneTimer = setInterval(play, kind === 'ring' ? 1800 : 3000);
    }
    function stopTone() { if (toneTimer) { clearInterval(toneTimer); toneTimer = null; } }

    // ── Media and connection ─────────────────────────────────────────────────
    function getMedia(type) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.RTCPeerConnection) {
            return Promise.reject(new Error(window.isSecureContext === false
                ? 'Calls need the site to open over https://.'
                : 'This browser does not support calls.'));
        }
        var friendly = function (err) {
            if (err && (err.name === 'NotAllowedError' || err.name === 'SecurityError')) return new Error('Microphone / camera access is blocked. Allow it from the icon in the address bar, then try again.');
            if (err && err.name === 'NotFoundError') return new Error('No microphone was found on this computer.');
            if (err && err.name === 'NotReadableError') return new Error('The microphone or camera is being used by another app.');
            return new Error('Could not start the microphone / camera.');
        };
        if (type !== 'video') return navigator.mediaDevices.getUserMedia({ audio: true }).catch(function (err) { throw friendly(err); });
        return navigator.mediaDevices.getUserMedia({ audio: true, video: true }).catch(function (err) {
            // No camera: join with voice and still receive the other person's video.
            if (err && (err.name === 'NotFoundError' || err.name === 'OverconstrainedError' || err.name === 'NotReadableError')) {
                return navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                    toast('No camera available. Joining with voice only.', 'info');
                    return stream;
                }).catch(function (e) { throw friendly(e); });
            }
            throw friendly(err);
        });
    }

    function buildConnection(stream) {
        var pc = new RTCPeerConnection({ iceServers: iceServers });
        stream.getTracks().forEach(function (track) { pc.addTrack(track, stream); });
        if (call.type === 'video' && !stream.getVideoTracks().length) pc.addTransceiver('video', { direction: 'recvonly' });

        pc.ontrack = function (event) {
            var remote = el('ccRemote');
            if (!remote.srcObject) remote.srcObject = event.streams[0] || new MediaStream();
            if (!event.streams[0]) remote.srcObject.addTrack(event.track);
            remote.play().catch(function () {});
            if (event.track.kind === 'video') {
                var show = function () { el('ccStage').classList.add('has-video'); };
                var hide = function () { el('ccStage').classList.remove('has-video'); };
                event.track.onunmute = show;
                event.track.onmute = hide;
                if (!event.track.muted) show();
            }
        };
        pc.onconnectionstatechange = function () {
            if (!call) return;
            if (pc.connectionState === 'connected') markConnected();
            if (pc.connectionState === 'failed') {
                toast('The call could not connect. This network may be blocking calls.');
                hangUp();
            }
        };
        return pc;
    }

    /** No websocket here, so the network candidates travel inside the offer / answer: wait for them first. */
    function waitForCandidates(pc) {
        return new Promise(function (resolve) {
            if (pc.iceGatheringState === 'complete') return resolve();
            var done = function () { pc.removeEventListener('icegatheringstatechange', check); clearTimeout(timer); resolve(); };
            var check = function () { if (pc.iceGatheringState === 'complete') done(); };
            var timer = setTimeout(done, ICE_WAIT_MS);
            pc.addEventListener('icegatheringstatechange', check);
        });
    }

    // ── Screen ───────────────────────────────────────────────────────────────
    function setStatus(text) { el('ccStatus').textContent = text; }

    function openStage(name, type, status) {
        var stage = el('ccStage');
        stage.className = 'cc-stage show' + (type === 'video' ? ' is-video' : '');
        el('ccAvatar').textContent = initials(name);
        el('ccName').textContent = name;
        setStatus(status);
        setButton('ccMuteBtn', false, 'fa-microphone', 'fa-microphone-slash', 'Mute', 'Unmute');
        setButton('ccCamBtn', false, 'fa-video', 'fa-video-slash', 'Turn camera off', 'Turn camera on');
    }

    function setButton(id, off, onIcon, offIcon, onLabel, offLabel) {
        var btn = el(id);
        btn.classList.toggle('off', off);
        btn.title = off ? offLabel : onLabel;
        btn.setAttribute('aria-label', btn.title);
        btn.querySelector('i').className = 'fas ' + (off ? offIcon : onIcon);
    }

    function showLocal(stream) {
        var local = el('ccLocal');
        local.srcObject = stream;
        local.play().catch(function () {});
    }

    function markConnected() {
        if (!call || call.startedAt) return;
        stopTone();
        call.startedAt = Date.now();
        var tick = function () {
            if (!call) return;
            var s = Math.floor((Date.now() - call.startedAt) / 1000);
            setStatus(Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'));
        };
        tick();
        call.timer = setInterval(tick, 1000);
    }

    function closeStage() {
        stopTone();
        if (call) {
            if (call.poll) clearInterval(call.poll);
            if (call.timer) clearInterval(call.timer);
            if (call.pc) call.pc.close();
            if (call.stream) call.stream.getTracks().forEach(function (t) { t.stop(); });
        }
        el('ccRemote').srcObject = null;
        el('ccLocal').srcObject = null;
        el('ccStage').className = 'cc-stage';
        call = null;
        document.dispatchEvent(new CustomEvent('crm:call-ended'));
    }

    // ── Both sides watch the call's status while it lasts ────────────────────
    function startPolling() {
        call.poll = setInterval(pollCall, POLL_MS);
    }

    function pollCall() {
        if (!call || !call.id || call.polling) return;
        var current = call;
        current.polling = true;
        get('/' + current.id).then(function (res) {
            if (res.status === 403 || res.status === 404) return { status: 'ended' };
            return res.ok ? res.json() : null;
        }).then(function (data) {
            if (!data || call !== current) return;
            if (data.status === 'active' && current.role === 'caller' && !current.answered && data.answer) {
                current.answered = true;
                stopTone();
                setStatus('Connecting…');
                current.pc.setRemoteDescription(JSON.parse(data.answer)).catch(function () {
                    toast('The call could not connect.');
                    hangUp();
                });
            }
            if (data.status !== 'ringing' && data.status !== 'active') {
                var messages = { declined: current.name + ' declined the call.', missed: current.role === 'caller' ? 'No answer from ' + current.name + '.' : 'The call ended.', ended: 'Call ended.' };
                toast(messages[data.status] || 'Call ended.', 'info');
                closeStage();
            }
        }).catch(function () { /* a lost poll is fine, the next one retries */ }).finally(function () {
            current.polling = false;
        });
    }

    function hangUp() {
        if (!call) return;
        var id = call.id;
        closeStage();
        if (id) post('/' + id + '/end').catch(function () {});
    }

    // ── Calling someone ──────────────────────────────────────────────────────
    function start(receiverId, name, type) {
        if (call) { toast('You are already on a call.'); return; }
        if (incoming) { toast('Answer or decline the incoming call first.'); return; }
        beep(1, .01, 0);   // unlocks audio while we still have the click
        call = { id: null, role: 'caller', type: type, name: name };
        var current = call;
        openStage(name, type, 'Starting…');
        getMedia(type).then(function (stream) {
            if (call !== current) { stream.getTracks().forEach(function (t) { t.stop(); }); throw null; }
            current.stream = stream;
            showLocal(stream);
            current.pc = buildConnection(stream);
            return current.pc.createOffer();
        }).then(function (offer) {
            return current.pc.setLocalDescription(offer).then(function () { return waitForCandidates(current.pc); });
        }).then(function () {
            if (call !== current) throw null;
            return post('', { receiver_id: receiverId, type: type, offer: JSON.stringify(current.pc.localDescription) });
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) { return { ok: res.ok, data: data }; });
        }).then(function (result) {
            if (!result.ok || !result.data.id) throw new Error(result.data.message || 'The call could not start.');
            if (call !== current) { post('/' + result.data.id + '/end').catch(function () {}); return; }
            current.id = result.data.id;
            setStatus('Ringing…');
            startTone('ringback');
            startPolling();
        }).catch(function (err) {
            if (call === current) closeStage();
            if (err) toast(err.message);
        });
    }

    // ── Somebody calling me ──────────────────────────────────────────────────
    function showIncoming(data) {
        incoming = data;
        el('ccIncomingAvatar').textContent = initials(data.caller_name);
        el('ccIncomingName').textContent = data.caller_name;
        el('ccIncomingKind').textContent = data.type === 'video' ? 'Incoming video call' : 'Incoming voice call';
        el('ccIncoming').classList.add('show');
        startTone('ring');
        originalTitle = document.title;
        if (titleTimer) clearInterval(titleTimer);
        titleTimer = setInterval(function () {
            document.title = document.title === originalTitle ? '📞 ' + data.caller_name + ' is calling' : originalTitle;
        }, 1200);
    }

    function hideIncoming() {
        incoming = null;
        el('ccIncoming').classList.remove('show');
        stopTone();
        if (titleTimer) { clearInterval(titleTimer); titleTimer = null; document.title = originalTitle || document.title; }
    }

    function accept() {
        if (!incoming) return;
        var data = incoming;
        hideIncoming();
        call = { id: data.id, role: 'callee', type: data.type, name: data.caller_name };
        var current = call;
        openStage(data.caller_name, data.type, 'Connecting…');
        getMedia(data.type).then(function (stream) {
            if (call !== current) { stream.getTracks().forEach(function (t) { t.stop(); }); throw null; }
            current.stream = stream;
            showLocal(stream);
            current.pc = buildConnection(stream);
            return current.pc.setRemoteDescription(JSON.parse(data.offer));
        }).then(function () {
            return current.pc.createAnswer();
        }).then(function (answer) {
            return current.pc.setLocalDescription(answer).then(function () { return waitForCandidates(current.pc); });
        }).then(function () {
            return post('/' + data.id + '/answer', { answer: JSON.stringify(current.pc.localDescription) });
        }).then(function (res) {
            if (!res.ok) throw new Error('This call has already ended.');
            if (call === current) startPolling();
        }).catch(function (err) {
            dismissed[data.id] = true;
            if (err) post('/' + data.id + '/decline').catch(function () {});
            if (call === current) closeStage();
            if (err) toast(err.message);
        });
    }

    function decline() {
        if (!incoming) return;
        var id = incoming.id;
        dismissed[id] = true;
        hideIncoming();
        post('/' + id + '/decline').catch(function () {});
    }

    function pollIncoming() {
        if (call) return;
        get('/incoming').then(function (res) {
            return res.ok ? res.json() : null;
        }).then(function (data) {
            if (!data || call) return;
            var ringing = data.call && !dismissed[data.call.id] ? data.call : null;
            if (ringing && (!incoming || incoming.id !== ringing.id)) showIncoming(ringing);
            // The caller gave up, or this user picked it up in another tab.
            if (!ringing && incoming) hideIncoming();
        }).catch(function () {});
    }

    // ── Buttons ──────────────────────────────────────────────────────────────
    el('ccAcceptBtn').addEventListener('click', accept);
    el('ccDeclineBtn').addEventListener('click', decline);
    el('ccHangBtn').addEventListener('click', hangUp);
    el('ccMinBtn').addEventListener('click', function () {
        var min = el('ccStage').classList.toggle('min');
        setButton('ccMinBtn', false, min ? 'fa-expand-alt' : 'fa-compress-alt', '', min ? 'Maximise' : 'Minimise', '');
    });
    el('ccMuteBtn').addEventListener('click', function () {
        if (!call || !call.stream) return;
        call.muted = !call.muted;
        call.stream.getAudioTracks().forEach(function (t) { t.enabled = !call.muted; });
        setButton('ccMuteBtn', call.muted, 'fa-microphone', 'fa-microphone-slash', 'Mute', 'Unmute');
    });
    el('ccCamBtn').addEventListener('click', function () {
        if (!call || !call.stream) return;
        call.camOff = !call.camOff;
        call.stream.getVideoTracks().forEach(function (t) { t.enabled = !call.camOff; });
        el('ccStage').classList.toggle('cam-off', call.camOff);
        setButton('ccCamBtn', call.camOff, 'fa-video', 'fa-video-slash', 'Turn camera off', 'Turn camera on');
    });

    // Closing the tab mid-call hangs up straight away instead of leaving the other person waiting.
    window.addEventListener('pagehide', function () {
        if (!call || !call.id || !navigator.sendBeacon) return;
        var form = new FormData();
        form.append('_token', csrf);
        navigator.sendBeacon(baseUrl + '/' + call.id + '/end', form);
    });

    window.CrmCall = { start: start, isBusy: function () { return !!call; } };
    pollIncoming();
    setInterval(pollIncoming, INCOMING_MS);
})();
</script>
@endif
