@extends('layouts.admin')
@section('title', 'Catégories')
@section('heading', 'Catégories')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2" method="GET"><input name="q" class="form-control" placeholder="Rechercher..." value="{{ request('q') }}"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button></form>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouvelle catégorie</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Icône</th><th>Nom</th><th>Description</th><th>Équipements</th><th></th></tr></thead>
    <tbody>
    @forelse($categories as $c)
        <tr><td><i class="bi {{ $c->icon }} fs-4"></i></td><td class="fw-semibold">{{ $c->name }}</td>
            <td class="text-muted">{{ \Illuminate\Support\Str::limit($c->description, 70) }}</td>
            <td><span class="badge text-bg-light border">{{ $c->equipment_count }}</span></td>
            <td class="text-end"><a href="{{ route('admin.categories.edit', $c) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @include('partials.delete', ['route' => route('admin.categories.destroy', $c)])</td></tr>
    @empty
        <tr><td colspan="5" class="text-center text-muted py-4">Aucune catégorie.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $categories->links() }}</div>
@endsection
