@push('styles')
<style>
/* ── Trigger Button ─────────────────────────────────────────── */
#kma-btn{
    position:fixed;bottom:20px;right:20px;width:56px;height:56px;
    border-radius:50%;background:linear-gradient(135deg,#0b66c3,#063e7a);
    border:none;cursor:pointer;z-index:9998;
    box-shadow:0 6px 28px rgba(11,102,195,.55);
    display:flex;align-items:center;justify-content:center;
    transition:transform .2s;
}
@media (max-width: 640px) {
    #kma-btn {
        width: 48px;
        height: 48px;
        bottom: 85px;
        right: 15px;
    }
    #kma-btn i { font-size: 20px !important; }
}
#kma-btn:hover{transform:scale(1.1);}
#kma-btn i{color:#fff;font-size:25px;}
#kma-btn::before{
    content:'';position:absolute;inset:-7px;border-radius:50%;
    border:2px solid rgba(11,102,195,.35);
    animation:kma-pulse 2.2s ease-out infinite;
}
@keyframes kma-pulse{0%{transform:scale(1);opacity:1;}100%{transform:scale(1.5);opacity:0;}}
#kma-btn .kma-dot{
    position:absolute;top:3px;right:3px;
    width:13px;height:13px;background:#34a853;
    border-radius:50%;border:2px solid #fff;
}

/* ── Panel ──────────────────────────────────────────────────── */
#kma-panel{
    position:fixed;bottom:100px;right:26px;width:400px;max-height:600px;
    background:#fff;border-radius:22px;
    box-shadow:0 22px 70px rgba(0,0,0,.17);
    display:flex;flex-direction:column;z-index:9999;overflow:hidden;
    transform:scale(.84) translateY(22px);opacity:0;pointer-events:none;
    transition:transform .28s cubic-bezier(.34,1.56,.64,1),opacity .22s;
}
#kma-panel.open{transform:scale(1) translateY(0);opacity:1;pointer-events:all;}

/* ── Header ─────────────────────────────────────────────────── */
.kma-hdr{
    background:linear-gradient(135deg,#0b66c3,#063e7a);
    padding:14px 16px;display:flex;align-items:center;gap:11px;flex-shrink:0;
}
.kma-av{
    width:40px;height:40px;background:rgba(255,255,255,.18);
    border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.kma-av i{color:#fff;font-size:18px;}
.kma-hdr-info{flex:1;}
.kma-hdr-info h4{margin:0;color:#fff;font-size:14px;font-weight:700;}
.kma-hdr-info small{color:rgba(255,255,255,.78);font-size:11px;}
.kma-hdr-close{
    background:rgba(255,255,255,.14);border:none;color:#fff;
    width:29px;height:29px;border-radius:50%;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
}
.kma-hdr-close:hover{background:rgba(255,255,255,.28);}

/* ── Quick chips ────────────────────────────────────────────── */
.kma-chips{
    padding:8px 12px 6px;display:flex;flex-wrap:wrap;gap:5px;
    flex-shrink:0;border-bottom:1px solid #f0f0f0;background:#fafcff;
}
.kma-chip{
    background:#eef4ff;color:#0b66c3;border:1px solid #c5d9fb;
    border-radius:20px;padding:4px 12px;font-size:11px;cursor:pointer;
    transition:background .15s,transform .1s;
}
.kma-chip:hover{background:#dceeff;transform:translateY(-1px);}

/* ── Messages ───────────────────────────────────────────────── */
#kma-msgs{
    flex:1;overflow-y:auto;padding:14px;
    display:flex;flex-direction:column;gap:11px;scroll-behavior:smooth;
}
#kma-msgs::-webkit-scrollbar{width:3px;}
#kma-msgs::-webkit-scrollbar-thumb{background:#e0e0e0;border-radius:3px;}
.kma-msg{display:flex;gap:8px;max-width:97%;animation:kma-in .22s ease;}
@keyframes kma-in{from{opacity:0;transform:translateY(7px);}to{opacity:1;}}
.kma-msg.user{align-self:flex-end;flex-direction:row-reverse;}
.kma-bbl{padding:10px 14px;border-radius:15px;font-size:13px;line-height:1.65;}
.kma-msg.bot  .kma-bbl{background:#f0f4ff;color:#1a1a2e;border-bottom-left-radius:3px;}
.kma-msg.user .kma-bbl{background:linear-gradient(135deg,#0b66c3,#063e7a);color:#fff;border-bottom-right-radius:3px;}
.kma-ico{
    width:28px;height:28px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;
}
.kma-msg.bot  .kma-ico{background:#eef4ff;}
.kma-msg.bot  .kma-ico i{color:#0b66c3;font-size:12px;}
.kma-msg.user .kma-ico{background:#063e7a;}
.kma-msg.user .kma-ico i{color:#fff;font-size:12px;}

/* ── Typing dots ────────────────────────────────────────────── */
.kma-typing .kma-bbl{background:#f0f4ff;padding:13px 15px;}
.kma-dots{display:flex;gap:5px;}
.kma-dots span{
    width:7px;height:7px;background:#0b66c3;border-radius:50%;
    animation:kma-bounce 1.3s infinite;
}
.kma-dots span:nth-child(2){animation-delay:.2s;}
.kma-dots span:nth-child(3){animation-delay:.4s;}
@keyframes kma-bounce{0%,60%,100%{transform:translateY(0);}30%{transform:translateY(-7px);}}

/* ── Agent action card ──────────────────────────────────────── */
.kma-action{
    background:linear-gradient(135deg,#e8f5e9,#f1f8e9);
    border:1.5px solid #81c784;border-radius:13px;
    padding:13px 15px;font-size:13px;color:#1b5e20;
}
.kma-action-top{
    display:flex;align-items:center;gap:9px;
    font-weight:700;font-size:13px;margin-bottom:5px;
}
.kma-action-top i{font-size:17px;color:#2e7d32;}
.kma-action-sub{font-size:11px;color:#388e3c;margin-top:3px;}
.kma-bar{
    height:5px;background:#c8e6c9;border-radius:5px;
    margin-top:10px;overflow:hidden;
}
.kma-bar-fill{
    height:100%;width:0;
    background:linear-gradient(90deg,#34a853,#0b66c3);
    border-radius:5px;transition:width 1.4s ease-in-out;
}

/* ── Input area ─────────────────────────────────────────────── */
.kma-inp-wrap{
    padding:10px 12px;border-top:1px solid #f0f0f0;
    display:flex;gap:8px;align-items:flex-end;flex-shrink:0;
}
#kma-inp{
    flex:1;border:1.5px solid #e0e8f8;border-radius:11px;
    padding:9px 12px;font-size:13px;font-family:inherit;
    resize:none;outline:none;max-height:82px;overflow-y:auto;
    background:#fafcff;transition:border .15s;
}
#kma-inp:focus{border-color:#0b66c3;}
#kma-send{
    width:40px;height:40px;border-radius:50%;
    background:linear-gradient(135deg,#0b66c3,#063e7a);
    border:none;cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    transition:transform .15s,opacity .15s;flex-shrink:0;
}
#kma-send:hover{transform:scale(1.09);}
#kma-send:disabled{opacity:.38;cursor:default;transform:none;}
#kma-send i{color:#fff;font-size:15px;}
.kma-footer-note{
    text-align:center;font-size:10px;color:#c0c0c0;
    padding:0 12px 9px;flex-shrink:0;
}
@media(max-width:440px){#kma-panel{width:calc(100vw - 18px);right:9px;}}
</style>
@push('styles')

<!-- Trigger -->
<button id="kma-btn" title="KM Autonomous Agent">
    <i class="fa fa-robot"></i>
    <span class="kma-dot"></span>
</button>

<!-- Panel -->
<div id="kma-panel">
    <div class="kma-hdr">
        <div class="kma-av"><i class="fa fa-robot"></i></div>
        <div class="kma-hdr-info">
            <h4>KM Autonomous Agent</h4>
            <small>🟢 Active &nbsp;·&nbsp; I navigate &amp; fill forms for you</small>
        </div>
        <button class="kma-hdr-close" onclick="kmaToggle()"><i class="fa fa-times"></i></button>
    </div>

    <!-- Chips change per role -->
    <div class="kma-chips">
        @php
            $user = auth()->user();
            $role = $user ? (is_object($user->role) ? $user->role->value : $user->role) : 'guest';
            $userName = $user ? $user->name : 'Guest';
        @endphp

        @if ($role === 'admin')
            <span class="kma-chip" onclick="kmaChip(this)">Create a survey about customer feedback</span>
            <span class="kma-chip" onclick="kmaChip(this)">Go to manage users</span>
            <span class="kma-chip" onclick="kmaChip(this)">Show me reports</span>
            <span class="kma-chip" onclick="kmaChip(this)">Go to payments</span>
        @elseif ($role === 'organization')
            <span class="kma-chip" onclick="kmaChip(this)">Create a survey about employee satisfaction</span>
            <span class="kma-chip" onclick="kmaChip(this)">Show me responses</span>
            <span class="kma-chip" onclick="kmaChip(this)">Take me to reports</span>
            <span class="kma-chip" onclick="kmaChip(this)">Manage my surveys</span>
        @elseif ($role === 'independent')
            <span class="kma-chip" onclick="kmaChip(this)">Create a survey about climate change</span>
            <span class="kma-chip" onclick="kmaChip(this)">Show me responses</span>
            <span class="kma-chip" onclick="kmaChip(this)">Take me to reports</span>
            <span class="kma-chip" onclick="kmaChip(this)">Manage my surveys</span>
        @elseif ($role === 'respondent')
            <span class="kma-chip" onclick="kmaChip(this)">Show my available surveys</span>
            <span class="kma-chip" onclick="kmaChip(this)">View my submitted responses</span>
            <span class="kma-chip" onclick="kmaChip(this)">Go to dashboard</span>
        @else
            <span class="kma-chip" onclick="kmaChip(this)">Login as organization</span>
            <span class="kma-chip" onclick="kmaChip(this)">Register as researcher</span>
            <span class="kma-chip" onclick="kmaChip(this)">Browse public surveys</span>
            <span class="kma-chip" onclick="kmaChip(this)">What is KMSurveyTool?</span>
        @endif
    </div>

    <div id="kma-msgs">
        <div class="kma-msg bot">
            <div class="kma-ico"><i class="fa fa-robot"></i></div>
            <div class="kma-bbl">
                @if ($role === 'admin')
                    👋 Hi <strong>{{ $userName }}</strong>! I'm <strong>KM Agent</strong> — I control the platform for you.<br>Just <em>tell me what you need</em> and I'll navigate there and fill everything in. No clicking required!
                @elseif ($role === 'organization')
                    👋 Hi <strong>{{ $userName }}</strong>! I'm <strong>KM Agent</strong>.<br>Tell me what to do — I'll open any page and fill in forms for you automatically. Just speak!
                @elseif ($role === 'independent')
                    👋 Hi <strong>{{ $userName }}</strong>! I'm your <strong>KM Research Agent</strong>.<br>Just tell me your research goal — I'll open the right page and set everything up!
                @elseif ($role === 'respondent')
                    👋 Hi <strong>{{ $userName }}</strong>! I'm <strong>KM Agent</strong>.<br>Tell me what you'd like to do — I'll take you there instantly, no clicking needed!
                @else
                    👋 Welcome to <strong>KMSurveyTool</strong>!<br>I'm <strong>KM Agent</strong>. Tell me what you want and I'll take you there automatically.<br><small>Try: <em>"Take me to register as a researcher"</em></small>
                @endif
            </div>
        </div>
    </div>

    <div class="kma-inp-wrap">
        <textarea id="kma-inp" rows="1"
            placeholder="Tell me what to do — I'll navigate and fill for you..."
            oninput="kmaResize(this)" onkeydown="kmaKey(event)"></textarea>
        <button id="kma-send" onclick="kmaSend()"><i class="fa fa-paper-plane"></i></button>
    </div>
    <div class="kma-footer-note">KM Autonomous Agent · Auto-navigate · Auto-fill · Powered by Groq AI</div>
</div>

@push('scripts')
<script>
(function(){
    // Restoring history from localStorage
    let hist  = JSON.parse(localStorage.getItem('kma_hist')) || [];
    let busy  = false;

    // ── Open / close ───────────────────────────────────────────
    window.kmaToggle = () => document.getElementById('kma-panel').classList.toggle('open');
    document.getElementById('kma-btn').addEventListener('click', kmaToggle);

    window.kmaChip = el => {
        document.getElementById('kma-inp').value = el.textContent.trim();
        kmaSend();
    };

    window.kmaResize = el => {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 82) + 'px';
    };

    window.kmaKey = e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); kmaSend(); }
    };

    // ── Add a message bubble ───────────────────────────────────
    function addMsg(role, html) {
        const wrap = document.getElementById('kma-msgs');
        const d    = document.createElement('div');
        d.className = 'kma-msg ' + role;
        const ico   = role === 'user' ? 'fa-user' : 'fa-robot';
        d.innerHTML = `<div class="kma-ico"><i class="fa ${ico}"></i></div><div class="kma-bbl">${html}</div>`;
        wrap.appendChild(d);
        wrap.scrollTop = wrap.scrollHeight;
        return d;
    }

    // ── Typing indicator ───────────────────────────────────────
    function showTyping() {
        const wrap = document.getElementById('kma-msgs');
        const d    = document.createElement('div');
        d.className = 'kma-msg bot kma-typing'; d.id = 'kma-typing';
        d.innerHTML = '<div class="kma-ico"><i class="fa fa-robot"></i></div>'
                    + '<div class="kma-bbl"><div class="kma-dots"><span></span><span></span><span></span></div></div>';
        wrap.appendChild(d);
        wrap.scrollTop = wrap.scrollHeight;
    }
    function removeTyping() { const t = document.getElementById('kma-typing'); if(t) t.remove(); }

    // ── Format markdown-lite ───────────────────────────────────
    function fmt(t) {
        return t.replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
                .replace(/\*(.*?)\*/g,'<em>$1</em>')
                .replace(/\n/g,'<br>');
    }

    // ── Restore historical messages on load ──────────────────
    if (hist.length > 0) {
        hist.forEach(m => {
            if (m.role === 'user') addMsg('user', fmt(m.content));
            else if (m.role === 'assistant') addMsg('bot', fmt(m.content));
        });
    }

    // ── Show agent action card + auto-execute ──────────────────
    function executeAction(data) {
        const wrap = document.getElementById('kma-msgs');
        const d    = document.createElement('div');
        d.className = 'kma-msg bot';

        const isPrefill = data.action === 'prefill';
        const icon  = isPrefill ? 'fa-magic' : 'fa-location-arrow';
        const sub   = isPrefill
            ? 'Survey designed! Launch the builder to finalize.'
            : 'Navigating to page...';

        // Build destination URL
        let url = data.url;
        if (!url) { addMsg('bot','⚠️ I could not find that page. Try again.'); return; }

        if (isPrefill && data.data) {
            const p = new URLSearchParams();
            p.set('prefill','1');
            if (data.data.title)       p.set('title',       data.data.title);
            if (data.data.description) p.set('description',  data.data.description);
            if (data.data.category)    p.set('category',     data.data.category);
            if (data.data.type)        p.set('type',         data.data.type);
            if (data.data.questions)   p.set('questions',    JSON.stringify(data.data.questions));
            url = url + '?' + p.toString();
        }

        const btnHtml = isPrefill 
            ? `<button onclick="window.location.href='${url}'" style="margin-top:10px;background:#2e7d32;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-weight:bold;width:100%;"><i class="fa fa-external-link-alt"></i> Launch Survey Builder</button>`
            : '';

        d.innerHTML = `
            <div class="kma-ico"><i class="fa fa-robot"></i></div>
            <div class="kma-action">
                <div class="kma-action-top"><i class="fa ${icon}"></i>${fmt(data.message || 'Action ready...')}</div>
                <div class="kma-action-sub">${sub}</div>
                ${btnHtml}
                ${!isPrefill ? '<div class="kma-bar"><div class="kma-bar-fill" id="kma-bar-fill"></div></div>' : ''}
            </div>`;
        wrap.appendChild(d);
        wrap.scrollTop = wrap.scrollHeight;

        if (!isPrefill) {
            // Animate progress bar for simple navigation
            setTimeout(() => {
                const bar = d.querySelector('.kma-bar-fill');
                if (bar) bar.style.width = '100%';
            }, 80);
            // Navigate automatically after progress bar for simple navigation
            setTimeout(() => { window.location.href = url; }, 1700);
        }
    }

    // ── Main send ──────────────────────────────────────────────
    window.kmaSend = async function() {
        const inp  = document.getElementById('kma-inp');
        const text = inp.value.trim();
        if (!text || busy) return;

        inp.value = '';
        inp.style.height = 'auto';
        busy = true;
        document.getElementById('kma-send').disabled = true;

        addMsg('user', fmt(text));
        hist.push({ role:'user', content:text });
        localStorage.setItem('kma_hist', JSON.stringify(hist));
        showTyping();

        try {
            const res  = await fetch('{{ route('api.agent.chat') }}', {
                method : 'POST',
                headers: { 
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body   : JSON.stringify({ messages: hist })
            });
            const data = await res.json();
            removeTyping();

            if (data.action === 'navigate' || data.action === 'prefill') {
                executeAction(data);
                // We don't save 'prefill' as assistant message because it has complex data, 
                // just save the intent if needed, or keep it simple.
                // For now, let's just save the bot's text reply.
                hist.push({ role:'assistant', content: data.message || 'Action executed.' });
                localStorage.setItem('kma_hist', JSON.stringify(hist));

            } else {
                const reply = data.message || '⚠️ No response.';
                hist.push({ role:'assistant', content:reply });
                localStorage.setItem('kma_hist', JSON.stringify(hist));
                addMsg('bot', fmt(reply));
            }

        } catch(err) {
            removeTyping();
            addMsg('bot','⚠️ Agent error. Check your connection and try again.');
            console.error(err);
        }

        busy  = false;
        document.getElementById('kma-send').disabled = false;
        inp.focus();
    };
})();
</script>
@endpush
