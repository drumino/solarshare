@php
    $helpPage = null;
    if (request()->routeIs('admin.reviews.*')) {
        $helpPage = 'avis';
    } elseif (request()->routeIs('admin.review-reports.*')) {
        $helpPage = 'signalements';
    } elseif (request()->routeIs('admin.moderation.*')) {
        $helpPage = 'moderation';
    }
@endphp

@if($helpPage)
<style>
    #help-chat { font-family: inherit; }
    #help-toggle {
        position: fixed; bottom: 1.25rem; right: 1.25rem; width: 2.5rem; height: 2.5rem;
        border-radius: 50%; border: none; background: #0d6efd; color: #fff;
        font-size: 1.5rem; font-weight: 700; line-height: 1; cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 1060;
    }
    #help-toggle:hover { background: #0b5ed7; }
    #help-panel {
        position: fixed; bottom: 4.5rem; right: 1.25rem; width: 320px; height: 380px;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18); display: none; flex-direction: column;
        z-index: 1060; overflow: hidden;
    }
    #help-panel-header {
        background: #0d6efd; color: #fff; padding: 0.6rem 0.85rem; font-weight: 700; font-size: 0.95rem;
    }
    #help-messages {
        flex: 1; overflow: auto; padding: 0.6rem 0.85rem; font-size: 0.9rem; line-height: 1.4;
        background: #f8fafc;
    }
    #help-messages div { margin-bottom: 0.55rem; padding-bottom: 0.55rem; border-bottom: 1px solid #e5e7eb; }
    #help-messages strong { font-size: 0.78rem; text-transform: uppercase; color: #64748b; }
    #help-quick { padding: 0.4rem 0.85rem; display: flex; gap: 0.35rem; flex-wrap: wrap; border-bottom: 1px solid #e5e7eb; background: #fff; }
    #help-quick button {
        flex: 1; min-width: 70px; padding: 0.35rem 0.5rem; font-size: 0.78rem; border: 1px solid #e5e7eb;
        border-radius: 0.5rem; background: #fff; cursor: pointer; text-align: left;
    }
    #help-quick button:hover { background: #f1f5f9; }
    #help-form { display: flex; gap: 0.4rem; padding: 0.5rem 0.85rem; background: #fff; }
    #help-form input { flex: 1; padding: 0.4rem 0.6rem; font-size: 0.88rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
    #help-form button { padding: 0.4rem 0.75rem; font-size: 0.88rem; border: none; border-radius: 0.5rem; background: #0d6efd; color: #fff; cursor: pointer; }
    #help-form button:hover { background: #0b5ed7; }
    #help-engine { font-size: 0.72rem; color: #64748b; padding: 0.2rem 0.85rem; text-align: right; }
    @media print { #help-chat { display: none !important; } }
</style>
<div id="help-chat">
    <button id="help-toggle" type="button" aria-label="Ouvrir l aide">?</button>
    <div id="help-panel">
        <div id="help-panel-header">Assistant d'aide</div>
        <div id="help-messages"></div>
        <div id="help-quick">
            @php
                $quick = [];
                if ($helpPage === 'avis') {
                    $quick = ['Comment filtrer les avis ?', 'Comment créer un avis ?', 'C est quoi le badge suspect ?'];
                } elseif ($helpPage === 'signalements') {
                    $quick = ['Comment traiter un signalement ?', 'Quand un avis est il masqué automatiquement ?', 'Quels sont les statuts des signalements ?'];
                } elseif ($helpPage === 'moderation') {
                    $quick = ['Quels KPIs sont affichés ?', 'Comment exporter en CSV ?', 'C est quoi le cache de 5 minutes ?'];
                }
            @endphp
            @foreach($quick as $q)
                <button type="button" data-q="{{ $q }}">{{ $q }}</button>
            @endforeach
        </div>
        <form id="help-form">
            <input id="help-input" type="text" placeholder="Tapez votre question..." autocomplete="off">
            <button type="button" id="help-send">Envoyer</button>
        </form>
        <div id="help-engine"></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var chat = document.getElementById('help-chat');
    if (!chat) return;
    var toggle = document.getElementById('help-toggle');
    var panel = document.getElementById('help-panel');
    var messages = document.getElementById('help-messages');
    var input = document.getElementById('help-input');
    var form = document.getElementById('help-form');
    var send = document.getElementById('help-send');
    var engineEl = document.getElementById('help-engine');
    var quickBtns = chat.querySelectorAll('#help-quick button');
    var url = '{{ route('admin.help.ask') }}';
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var page = '{{ $helpPage }}';
    var open = false;

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        open = !open;
        panel.style.display = open ? 'flex' : 'none';
    });

    document.addEventListener('click', function (e) {
        if (open && !panel.contains(e.target) && e.target !== toggle) {
            open = false;
            panel.style.display = 'none';
        }
    });

    quickBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            input.value = btn.getAttribute('data-q');
        });
    });

    function addMessage(text, from) {
        var div = document.createElement('div');
        var strong = document.createElement('strong');
        var span = document.createElement('span');
        strong.textContent = from;
        span.textContent = ': ' + text;
        div.appendChild(strong);
        div.appendChild(span);
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return div;
    }

    function showLoading() {
        var div = document.createElement('div');
        div.id = 'help-loading';
        div.textContent = '...';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
        return div;
    }

    function submitQuestion() {
        var q = input.value.trim();
        if (!q) return;
        addMessage(q, 'Vous');
        input.value = '';
        var loading = showLoading();
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ question: q, page: page })
        }).then(function (r) {
            return r.json();
        }).then(function (data) {
            if (loading && loading.parentNode) loading.remove();
            var label = data.engine === 'llm' ? 'IA' : 'base locale';
            engineEl.textContent = label;
            addMessage(data.answer || 'Je ne sais pas.', label);
        }).catch(function () {
            if (loading && loading.parentNode) loading.remove();
            addMessage('Service indisponible', 'Erreur');
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitQuestion();
    });

    send.addEventListener('click', function (e) {
        e.preventDefault();
        submitQuestion();
    });
});
</script>
@endif
