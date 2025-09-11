<div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Activity</h5>
            <ul class="timeline">
                @forelse($activities as $activity)
                    <li>
                        <a href="#">{{ $activity->user->name }}</a>
                        <a href="#" class="float-right">{{ $activity->created_at->diffForHumans() }}</a>
                        <p>{{ $activity->comment }}</p>
                    </li>
                @empty
                    <li>
                        <p>No activities found.</p>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
