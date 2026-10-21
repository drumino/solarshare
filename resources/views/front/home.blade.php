@extends('layouts.front')
@section('title', 'Accueil')
@section('hero')
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="display-5">Louez et partagez l'énergie solaire entre voisins.</h1>
                <p class="lead opacity-75">Panneaux portables, batteries, mini-éoliennes : accédez à l'énergie renouvelable sans acheter, ou rentabilisez votre matériel inutilisé.</p>
            </div>
            <div class="col-lg-5">
                <div class="row g-3 text-center">
                    <div class="col-4"><div class="stat"><strong>{{ $stats['equipment'] }}</strong>équipements</div></div>
                    <div class="col-4"><div class="stat"><strong>{{ $stats['rentals'] }}</strong>locations</div></div>
                    <div class="col-4"><div class="stat"><strong>{{ $stats['cities'] }}</strong>villes</div></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <h2 class="section-title h4 mb-3">Derniers avis</h2>
        <form method="GET" class="mb-3">
            <select name="tri" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="recents" @selected(($tri ?? 'recents') === 'recents')>Récents</option>
                <option value="utiles" @selected(($tri ?? '') === 'utiles')>Plus utiles</option>
                <option value="mieux_notes" @selected(($tri ?? '') === 'mieux_notes')>Mieux notés</option>
            </select>
        </form>
        <div class="row g-3">
            @forelse($latestReviews as $r)
                <div class="col-12">
                    <div class="card review-card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">{{ $r->equipment->title }}</div>
                                <div class="small text-muted">{{ $r->user->name }} · {{ $r->created_at->format('d/m/Y') }} @if($r->edited_at) <span class="badge text-bg-info ms-2">modifié le {{ $r->edited_at->format('d/m/Y') }}</span> @endif</div>
                            </div>
                            <div class="text-end"><x-stars :rating="$r->rating" /></div>
                        </div>
                        <p class="mt-2 mb-0 small">{{ \Illuminate\Support\Str::limit($r->comment, 180) }}</p>
                        @if($r->photos->count())
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            @foreach($r->photos as $p)
                                <img src="{{ $p->url }}" alt="Photo de l'avis de {{ $r->user->name }}" loading="lazy" style="width:80px;height:80px;object-fit:cover;cursor:pointer" class="lb-img" data-src="{{ $p->url }}">
                            @endforeach
                        </div>
                        @endif
                        @auth
                            @if($r->user_id !== auth()->id())
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2 vote-btn {{ ($r->voted_by_me ?? false) ? 'active' : '' }}" data-review="{{ $r->id }}" data-url="{{ route('front.reviews.vote', $r) }}">
                                    Utile (<span class="count">{{ $r->helpful_count }}</span>)
                                </button>
                            @else
                                <span class="small text-muted mt-2 d-inline-block">Utile ({{ $r->helpful_count }})</span>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary mt-2">Utile ({{ $r->helpful_count }})</a>
                        @endauth
                        @if($r->owner_reply)
                        <div class="mt-2 p-2 bg-light rounded small">
                            <strong>Réponse du propriétaire</strong> — {{ $r->owner_reply_at?->format('d/m/Y') }}
                            @if($r->owner_reply_edited_at) <em>(modifiée)</em> @endif
                            <p class="mb-0">{{ $r->owner_reply }}</p>
                        </div>
                        @endif
                        @auth
                            @if($r->user_id !== auth()->id())
                                <form class="mt-2" method="POST" action="{{ route('front.reviews.report', $r) }}">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <select name="reason" class="form-select form-select-sm" required>
                                            @foreach(\App\Models\ReviewReport::REASONS as $k=>$l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                                        </select>
                                        <button class="btn btn-outline-danger btn-sm">Signaler</button>
                                    </div>
                                    @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
                                </form>
                            @endif
                        @endauth
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted">Aucun avis pour le moment. Soyez le premier à en publier un !</p></div>
            @endforelse
        </div>
        <div class="mt-3">{{ $latestReviews->links() }}</div>
    </div>
</div>
<script>
document.querySelectorAll('.vote-btn').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const url = btn.dataset.url;
    const res = await fetch(url, {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
    const data = await res.json();
    btn.querySelector('.count').textContent = data.count;
    btn.classList.toggle('active', data.voted);
  });
});
</script>
<script>
const lb = document.createElement('div');
lb.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;';
lb.innerHTML = '<img style="max-width:90%;max-height:90%">';
document.body.appendChild(lb);
document.querySelectorAll('.lb-img').forEach(img=>{
  img.addEventListener('click', ()=>{ lb.querySelector('img').src=img.dataset.src; lb.style.display='flex'; });
});
lb.addEventListener('click', ()=>{ lb.style.display='none'; });
document.addEventListener('keydown', e=>{ if(e.key==='Escape') lb.style.display='none'; });
</script>
@endsection