<div class="btn-group btn-group-sm" role="group">
    <a href="{{ route('clients.show', $client) }}" class="btn btn-info" title="View">
        <i class="fas fa-eye"></i>
    </a>
    <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary" title="Edit">
        <i class="fas fa-edit"></i>
    </a>
    @if($client->archived_at)
        <form action="{{ route('clients.restore', $client) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-success" title="Restore" onclick="return confirm('Are you sure you want to restore this client?')">
                <i class="fas fa-undo"></i>
            </button>
        </form>
    @else
        <form action="{{ route('clients.archive', $client) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-warning" title="Archive" onclick="return confirm('Are you sure you want to archive this client?')">
                <i class="fas fa-archive"></i>
            </button>
        </form>
    @endif
    <form action="{{ route('clients.destroy', $client) }}" method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this client?')">
            <i class="fas fa-trash"></i>
        </button>
    </form>
</div>