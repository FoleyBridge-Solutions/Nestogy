<div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Files</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Uploaded At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $file)
                        <tr>
                            <td>{{ $file->name }}</td>
                            <td>{{ Illuminate\Support\Number::fileSize($file->size) }}</td>
                            <td>{{ $file->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">No files found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
