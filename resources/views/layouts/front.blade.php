<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SolarShare') — Partage d'énergie renouvelable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/front.css') }}" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark navbar-ss sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('front.home') }}"><i class="bi bi-brightness-high-fill"></i> SolarShare</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.equipment.index', 'front.equipment.show') ? 'active' : '' }}" href="{{ route('front.equipment.index') }}">Catalogue</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.advisor*') ? 'active' : '' }}" href="{{ route('front.advisor') }}"><i class="bi bi-stars"></i> Assistant énergie</a></li>
                @auth
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.reservations.*') ? 'active' : '' }}" href="{{ route('front.reservations.index') }}">Mes réservations</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('front.incidents.*') ? 'active' : '' }}" href="{{ route('front.incidents.index') }}">Mes incidents</a></li>
                @endauth
            </ul>
            <div class="d-flex align-items-center gap-2">
                @auth
                    <a href="{{ route('front.equipment.create') }}" class="btn btn-sun btn-sm"><i class="bi bi-plus-lg"></i> Proposer un équipement</a>
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-person-circle"></i> {{ auth()->user()->name }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if(auth()->user()->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Back office</a></li>
                                <li><hr class="dropdown-divider"></li>
                            @endif
                            <li>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Déconnexion</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Connexion</a>
                    <a href="{{ route('register') }}" class="btn btn-sun btn-sm">Inscription</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    @hasSection('hero')
        @yield('hero')
    @endif
    <div class="container py-4">
        @include('partials.flash')
        @yield('content')
    </div>
</main>

<footer class="footer-ss py-4 mt-4">
    <div class="container d-flex flex-wrap justify-content-between">
        <span><i class="bi bi-brightness-high-fill text-warning"></i> SolarShare — location et partage d'équipements d'énergie renouvelable</span>
        <span>Projet Applications Web Avancées 2026-2027 · 5 TWIN</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
