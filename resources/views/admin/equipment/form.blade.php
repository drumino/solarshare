@extends('layouts.admin')
@section('title', $equipment->exists ? 'Modifier l\'équipement' : 'Nouvel équipement')
@section('heading', $equipment->exists ? 'Modifier l\'équipement' : 'Nouvel équipement')
@section('content')
<div class="card card-ad p-4" style="max-width:820px">
    <form id="equipment-form" method="POST" enctype="multipart/form-data" novalidate
          action="{{ $equipment->exists ? route('admin.equipment.update', $equipment) : route('admin.equipment.store') }}"
          data-desc-url="{{ route('front.ai.description') }}" data-price-url="{{ route('front.ai.price') }}">
        @csrf @if($equipment->exists) @method('PUT') @endif
        @include('partials.equipment-fields', ['owners' => $owners])
        @if($equipment->image_url)<img src="{{ $equipment->image_url }}" class="img-thumbnail mb-3" style="max-height:120px" alt="">@endif
        <button class="btn btn-warning">Enregistrer</button>
        <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">Annuler</a>
    </form>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/equipment-form.js') }}"></script>@endpush
