@extends('layouts.front')
@section('title', 'Proposer un équipement')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <div class="card form-card p-4">
        <h1 class="h4 mb-1">Proposer un équipement à la location</h1>
        <p class="text-muted">Votre annonce sera publiée après validation par un administrateur.</p>
        <form id="equipment-form" method="POST" action="{{ route('front.equipment.store') }}" enctype="multipart/form-data" novalidate
              data-desc-url="{{ route('front.ai.description') }}" data-price-url="{{ route('front.ai.price') }}">
            @csrf
            @include('partials.equipment-fields')
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-sun">Soumettre l'annonce</button>
                <a href="{{ route('front.equipment.index') }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div></div>
@endsection
@push('scripts')<script src="{{ asset('js/equipment-form.js') }}"></script>@endpush
