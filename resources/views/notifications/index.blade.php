@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.front')
@section('content')
<div class="container py-4">
    <h2>Notifications</h2>
    <form method="POST" action="{{ route('notifications.readAll') }}" class="mb-3">
        @csrf
        <button class="btn btn-sm btn-outline-secondary">Tout marquer comme lu</button>
    </form>
    @forelse($notifications as $n)
        <div class="card mb-2 {{ $n->read_at ? '' : 'border-primary' }}">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-0">{{ $n->data['excerpt'] ?? ($n->data['reason'] ?? 'Notification') }}</p>
                    <small class="text-muted">{{ $n->created_at->diffForHumans() }}</small>
                </div>
                @unless($n->read_at)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary">Marquer comme lu</button>
                    </form>
                @endunless
                <a href="{{ notification_relative_url($n->data['link']) ?: route('notifications.read', $n->id) }}" class="btn btn-sm btn-link">Voir</a>
            </div>
        </div>
    @empty
        <p class="text-muted">Aucune notification</p>
    @endforelse
    {{ $notifications->links() }}
</div>
@endsection
