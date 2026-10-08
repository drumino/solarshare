{{-- Champs communs au formulaire équipement (front « Proposer » + back office). Variables : $equipment, $categories, $owners (optionnel) --}}
@isset($owners)
    <div class="row">
        <div class="col-md-6"><x-form.select name="owner_id" label="Propriétaire" :options="$owners->pluck('name', 'id')" :selected="$equipment->owner_id" placeholder="— Choisir —" required /></div>
        <div class="col-md-6"><x-form.select name="status" label="Statut" :options="\App\Models\Equipment::STATUSES" :selected="$equipment->status" required /></div>
    </div>
@endisset

<div class="row">
    <div class="col-md-6"><x-form.select name="category_id" label="Catégorie" :options="$categories->pluck('name', 'id')" :selected="$equipment->category_id" placeholder="— Choisir —" required /></div>
    <div class="col-md-6"><x-form.select name="type" label="Type d'équipement" :options="\App\Models\Equipment::TYPES" :selected="$equipment->type" placeholder="— Choisir —" required /></div>
</div>

<x-form.input name="title" label="Titre de l'annonce" :value="$equipment->title" required />

<div class="row">
    <div class="col-md-4"><x-form.input name="power_watts" label="Puissance (W)" type="number" min="1" :value="$equipment->power_watts" /></div>
    <div class="col-md-4"><x-form.input name="capacity_wh" label="Capacité (Wh)" type="number" min="1" :value="$equipment->capacity_wh" /></div>
    <div class="col-md-4"><x-form.select name="condition" label="État" :options="\App\Models\Equipment::CONDITIONS" :selected="$equipment->condition" required /></div>
</div>

<div class="row">
    <div class="col-md-6"><x-form.input name="city" label="Ville" :value="$equipment->city" required /></div>
    <div class="col-md-6"><x-form.input name="image" label="Photo" type="file" accept="image/*" help="JPG, PNG ou WebP, 2 Mo max, 200×200 px minimum." /></div>
</div>

<div class="mb-1 d-flex justify-content-between align-items-center">
    <label for="description" class="form-label mb-0">Description</label>
    <button type="button" id="btn-ai-desc" class="btn btn-sm btn-outline-secondary"><i class="bi bi-stars"></i> Générer avec l'IA</button>
</div>
<textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $equipment->description) }}</textarea>
@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
<div class="form-text mb-3"><span id="ai-desc-source"></span></div>

<div class="d-flex justify-content-between align-items-center mb-1">
    <span class="form-label mb-0">Tarification</span>
    <button type="button" id="btn-ai-price" class="btn btn-sm btn-outline-secondary"><i class="bi bi-stars"></i> Suggérer un prix</button>
</div>
<div id="ai-price-box" class="alert alert-light border d-none"></div>
<div class="row">
    <div class="col-md-6"><x-form.input name="price_per_day" label="Prix par jour (DT)" type="number" step="0.5" min="1" :value="$equipment->price_per_day" required /></div>
    <div class="col-md-6"><x-form.input name="deposit" label="Caution (DT)" type="number" step="1" min="0" :value="$equipment->deposit" /></div>
</div>
