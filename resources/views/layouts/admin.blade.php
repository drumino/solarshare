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
        'Avis & signalements' => [
            ['admin.reviews.*', 'admin.reviews.index', 'bi-chat-heart', 'Avis'],
            ['admin.review-reports.*', 'admin.review-reports.index', 'bi-flag', 'Signalements'],
            ['admin.moderation.*', 'admin.moderation.index', 'bi-shield-check', 'Modération'],
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
    <header class="topbar" style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 1rem; background:#fff; border-bottom:1px solid #e5e7eb;">
        <h1 class="h5 mb-0">@yield('heading', 'Administration')</h1>
        <div style="display:flex; align-items:center; gap:0.75rem;">
            @auth
            <div style="position:relative;">
                <button type="button" id="adminBellBtn" class="btn btn-outline-dark btn-sm" style="color:#111;" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    @if(auth()->user()->unreadNotifications()->count() > 0)
                        <span class="badge bg-danger">{{ auth()->user()->unreadNotifications()->count() }}</span>
                    @endif
                </button>
                <div id="adminBellDropdown" style="display:none; position:absolute; right:0; top:100%; width:320px; z-index:1050; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem; box-shadow:0 4px 12px rgba(0,0,0,0.15); margin-top:0.5rem;">
                    <div style="padding:0.5rem 0.75rem; border-bottom:1px solid #e5e7eb; font-weight:600;">Notifications</div>
                    <ul style="list-style:none; margin:0; padding:0; max-height:320px; overflow:auto;">
                        @forelse(auth()->user()->notifications()->latest()->take(5)->get() as $n)
                            <li style="padding:0.5rem 0.75rem; border-bottom:1px solid #f1f5f9;">
                                @if(!empty($n->data['link']))
                                    <a href="{{ notification_relative_url($n->data['link']) }}" class="d-block text-decoration-none">
                                        <div class="small">{{ $n->data['message'] ?? ($n->data['excerpt'] ?? ($n->data['reason'] ?? $n->type)) }}</div>
                                    </a>
                                @else
                                    <div class="small">{{ $n->data['message'] ?? ($n->data['excerpt'] ?? ($n->data['reason'] ?? $n->type)) }}</div>
                                @endif
                                <div class="text-muted" style="font-size:0.75rem;">{{ $n->created_at->diffForHumans() }}</div>
                            </li>
                        @empty
                            <li style="padding:0.75rem;" class="text-muted small">Aucune notification.</li>
                        @endforelse
                    </ul>
                    <div style="padding:0.5rem 0.75rem;">
                        <a href="{{ route('notifications.index') }}" class="small">Tout voir</a>
                    </div>
                </div>
            </div>
            @endauth
            <span class="text-muted"><i class="bi bi-person-circle"></i> {{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right"></i> Déconnexion</button>
            </form>
        </div>
    </header>
    <main style="padding:1rem;">
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('adminBellBtn');
    var dd = document.getElementById('adminBellDropdown');
    if (btn && dd) {
        btn.addEventListener('click', function (e) { e.stopPropagation(); dd.style.display = dd.style.display === 'block' ? 'none' : 'block'; });
        document.addEventListener('click', function (e) { if (!dd.contains(e.target) && e.target !== btn) dd.style.display = 'none'; });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
@include('admin.partials.help-chat')
</body>
</html>
