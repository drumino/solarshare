@extends('layouts.front')
@section('content')
<div class="container py-4">
    <h2>Mes avis reçus</h2>
    @foreach($reviews as $r)
        <div class="card mb-3 p-3">
            <p>{{ $r->comment }} ({{ $r->rating }}/5)</p>
            @if($r->owner_reply)
                <div class="alert alert-light">
                    <strong>Réponse du propriétaire</strong> — {{ $r->owner_reply_at?->format('d/m/Y') }}
                    @if($r->owner_reply_edited_at) <em>(modifiée)</em> @endif
                    <p>{{ $r->owner_reply }}</p>
                </div>
            @endif
            <form method="POST" action="{{ route('owner.reviews.reply', $r) }}">
                @csrf
                <textarea name="reply" class="form-control" rows="3" placeholder="Votre réponse...">{{ $r->owner_reply }}</textarea>
                <button class="btn btn-primary mt-2">Répondre</button>
            </form>
            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="suggestReply({{ $r->id }})">Suggérer une réponse (IA)</button>
            <small id="draft-label-{{ $r->id }}" class="text-muted d-none">Brouillon généré par IA, modifiable</small>
        </div>
    @endforeach
    {{ $reviews->links() }}
</div>
<script>
async function suggestReply(id){
    const res = await fetch(`/proprietaire/avis/${id}/reponse/suggestion`, {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
    const data = await res.json();
    const ta = document.querySelector(`form[action*="${id}/reponse"] textarea`);
    if(ta){ ta.value = data.text; document.getElementById('draft-label-'+id).classList.remove('d-none'); }
}
</script>
@endsection
