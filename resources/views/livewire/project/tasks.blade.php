<div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Tasks</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->tasks as $task)
                        <tr>
                            <td>{{ $task->name }}</td>
                            <td>{{ $task->assignedUser->name ?? 'Unassigned' }}</td>
                            <td>{{ $task->getStatusLabel() }}</td>
                            <td>{{ $task->due_date ? $task->due_date->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
