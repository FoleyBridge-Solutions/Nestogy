<div>
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Project Details</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Client:</strong> {{ $project->client->name }}</p>
                            <p><strong>Manager:</strong> {{ $project->manager->name }}</p>
                            <p><strong>Start Date:</strong> {{ $project->start_date->format('M d, Y') }}</p>
                            <p><strong>Due Date:</strong> {{ $project->due->format('M d, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Budget:</strong> ${{ number_format($project->budget, 2) }}</p>
                            <p><strong>Actual Cost:</strong> ${{ number_format($project->actual_cost, 2) }}</p>
                            <p><strong>Progress:</strong> {{ $project->progress }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Team Members</h5>
                    <ul class="list-group">
                        @foreach($project->members as $member)
                            <li class="list-group-item">{{ $member->user->name }} ({{ $member->role }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
