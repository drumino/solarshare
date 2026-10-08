<form method="POST" action="{{ $route }}" class="d-inline" onsubmit="return confirm('Supprimer définitivement cet élément ?')">
    @csrf @method('DELETE')
    <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></button>
</form>
