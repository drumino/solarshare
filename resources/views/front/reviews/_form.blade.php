@props(['review' => null, 'action' => null, 'buttonLabel' => 'Publier mon avis', 'cancelUrl' => null])

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($review && $review->exists) @method('PUT') @endif

    <x-form.select name="rating" label="Note" :options="[5 => '5 — Excellent', 4 => '4 — Très bien', 3 => '3 — Correct', 2 => '2 — Décevant', 1 => '1 — Mauvais']" :selected="$review?->rating ?? 5" required />

    <x-form.textarea name="comment" label="Commentaire" :value="$review?->comment" rows="5" required help="10 caractères minimum." />

    @if ($review && $review->exists && $review->photos->count())
    <div class="mb-3">
        <label class="form-label">Photos actuelles</label>
        <div class="d-flex gap-2 flex-wrap">
            @foreach ($review->photos as $photo)
            <div style="width:80px;height:80px" class="text-center position-relative">
                <img src="{{ $photo->url }}" alt="Photo de l'avis" loading="lazy"
                    style="width:80px;height:80px;object-fit:cover;border:1px solid #ddd">
                <input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}"
                    id="remove-photo-{{ $photo->id }}" class="mt-1">
                <label for="remove-photo-{{ $photo->id }}" class="small form-text">Retirer</label>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mb-3">
        <label class="form-label">Photos (max 3, 2 Mo chacune)</label>
        <input type="file" name="photos[]" id="photos" accept="image/*" multiple class="form-control">
        <div id="preview" class="d-flex gap-2 mt-2 flex-wrap"></div>
        @error('photos')<div class="text-danger small">{{ $message }}</div>@enderror
        @error('photos.*')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    @if ($review && $review->exists)
    <p class="small text-muted">Modification possible dans les 24h suivant la publication. Maximum 3 photos au total.</p>
    @endif

    <button class="btn btn-sun">{{ $buttonLabel }}</button>
    <a href="{{ $cancelUrl ?? route('front.home') }}" class="btn btn-outline-secondary">Annuler</a>
</form>

<script>
const input = document.getElementById('photos');
const preview = document.getElementById('preview');
if (input) {
    input.addEventListener('change', () => {
        preview.innerHTML = '';
        const files = Array.from(input.files);
        if (files.length > 3) { alert('Maximum 3 photos'); input.value = ''; return; }
        for (const f of files) {
            if (f.size > 2 * 1024 * 1024) { alert('Format accepté : JPG, PNG, WEBP, 2 Mo max'); input.value = ''; preview.innerHTML = ''; return; }
            const img = document.createElement('img');
            img.src = URL.createObjectURL(f);
            img.style.cssText = 'width:80px;height:80px;object-fit:cover';
            preview.appendChild(img);
        }
    });
}
</script>
