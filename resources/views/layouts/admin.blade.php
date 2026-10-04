<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') — SolarShare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>
@php
    $menu = [
        'Module 1 · Catalogue' => [
            ['admin.categories.*', 'admin.categories.index', 'bi-tags', 'Catégories'],
            ['admin.equipment.*', 'admin.equipment.index', 'bi-lightning-charge', 'Équipements'],
        ],
        'Module 2 · Réservations' => [
            ['admin.reservations.*', 'admin.reservations.index', 'bi-calendar-check', 'Réservations'],
            ['admin.payments.*', 'admin.payments.index', 'bi-credit-card', 'Paiements'],
        ],
        'Module 3 · Avis' => [
            ['admin.reviews.*', 'admin.reviews.index', 'bi-chat-heart', 'Avis'],
            ['admin.review-reports.*', 'admin.review-reports.index', 'bi-flag', 'Signalements'],
        ],
        'Module 4 · Maintenance' => [
            ['admin.incidents.*', 'admin.incidents.index', 'bi-exclamation-octagon', 'Incidents'],
            ['admin.maintenance-tasks.*', 'admin.maintenance-tasks.index', 'bi-tools', 'Tâches de maintenance'],
        ],
    ];
@endphp

<aside class="sidebar">
    <a class="brand" href="{{ route('admin.dashboard') }}"><i class="bi bi-brightness-high-fill"></i> SolarShare <small class="text-secondary fs-6">admin</small></a>
    <ul class="nav flex-column">
        <li><a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Tableau de bord</a></li>
        @foreach($menu as $group => $items)
            <li class="group">{{ $group }}</li>
            @foreach($items as [$pattern, $route, $icon, $label])
                <li><a class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($route) }}"><i class="bi {{ $icon }}"></i> {{ $label }}</a></li>
            @endforeach
        @endforeach
        <li class="group">Site</li>
        <li><a class="nav-link" href="{{ route('front.home') }}"><i class="bi bi-box-arrow-up-right"></i> Voir le front office</a></li>
    </ul>
</aside>

<div class="main">
    <div class="topbar d-flex justify-content-between align-items-center">
        <h1 class="h5 mb-0">@yield('heading', 'Administration')</h1>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted"><i class="bi bi-person-circle"></i> {{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right"></i> Déconnexion</button>
            </form>
        </div>
    </div>
    <div class="p-4">
        @include('partials.flash')
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
