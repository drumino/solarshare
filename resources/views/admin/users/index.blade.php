@extends('layouts.admin')
@section('title', 'Utilisateurs')
@section('heading', 'Utilisateurs')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2" method="GET">
        <input name="q" class="form-control" placeholder="Nom, e-mail, ville..." value="{{ request('q') }}">
        <select name="role" class="form-select">
            <option value="">Tous les rôles</option>
            @foreach(\App\Models\User::ROLES as $key => $label)<option value="{{ $key }}" @selected(request('role') === $key)>{{ $label }}</option>@endforeach
        </select>
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <a href="{{ route('admin.users.create') }}" class="btn btn-warning"><i class="bi bi-plus-lg"></i> Nouvel utilisateur</a>
</div>
<div class="card card-ad"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Nom</th><th>E-mail</th><th>Téléphone</th><th>Ville</th><th>Rôle</th><th>Inscrit le</th><th></th></tr></thead>
    <tbody>
    @forelse($users as $u)
        <tr><td class="fw-semibold">{{ $u->name }}</td><td>{{ $u->email }}</td>
            <td class="text-muted">{{ $u->phone ?? '—' }}</td><td class="text-muted">{{ $u->city ?? '—' }}</td>
            <td><x-badge :status="$u->role" :labels="\App\Models\User::ROLES" /></td>
            <td>{{ $u->created_at->format('d/m/Y') }}</td>
            <td class="text-end"><a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                @unless($u->is(auth()->user()))@include('partials.delete', ['route' => route('admin.users.destroy', $u)])@endunless</td></tr>
    @empty
        <tr><td colspan="7" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>
    @endforelse
    </tbody></table></div></div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
