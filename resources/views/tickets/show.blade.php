@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->number)

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Ticket Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            Ticket #{{ $ticket->number }} - {{ $ticket->subject }}
                        </h3>
                        <div>
                            <span class="badge badge-{{ $ticket->getPriorityColor() }}">{{ $ticket->priority }}</span>
                            <span class="badge badge-{{ $ticket->getStatusColor() }}">{{ $ticket->status }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="ticket-details mb-4">
                        <h5>Details</h5>
                        <div class="bg-light p-3 rounded">
                            {!! nl2br(e($ticket->details)) !!}
                        </div>
                    </div>

                    <!-- Ticket Replies -->
                    <div class="ticket-replies">
                        <h5>Replies</h5>
                        @forelse($ticket->replies as $reply)
                            <div class="card mb-3 {{ $reply->type == 'internal' ? 'border-warning' : '' }}">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $reply->user->name }}</strong>
                                            @if($reply->type == 'internal')
                                                <span class="badge badge-warning">Internal Note</span>
                                            @elseif($reply->type == 'private')
                                                <span class="badge badge-info">Private</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">{{ $reply->created_at->format('M d, Y g:i A') }}</small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    {!! nl2br(e($reply->reply)) !!}
                                    @if($reply->time_worked)
                                        <div class="mt-2">
                                            <small class="text-muted">Time worked: {{ $reply->time_worked }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No replies yet.</p>
                        @endforelse
                    </div>

                    <!-- Add Reply Form -->
                    <div class="mt-4">
                        <h5>Add Reply</h5>
                        <form action="{{ route('tickets.replies.store', $ticket) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <textarea name="reply" class="form-control @error('reply') is-invalid @enderror" 
                                          rows="4" placeholder="Enter your reply..." required></textarea>
                                @error('reply')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type">Reply Type</label>
                                        <select name="type" id="type" class="form-control">
                                            <option value="public">Public</option>
                                            <option value="private">Private</option>
                                            <option value="internal">Internal Note</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="time_worked">Time Worked (HH:MM:SS)</label>
                                        <input type="text" name="time_worked" id="time_worked" 
                                               class="form-control" placeholder="00:00:00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="status">Update Status</label>
                                        <select name="status" id="status" class="form-control">
                                            <option value="">Keep Current</option>
                                            <option value="Open">Open</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="On Hold">On Hold</option>
                                            <option value="Resolved">Resolved</option>
                                            <option value="Closed">Closed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Reply</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Ticket Information</h4>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Client:</dt>
                        <dd class="col-sm-7">
                            <a href="{{ route('clients.show', $ticket->client) }}">{{ $ticket->client->name }}</a>
                        </dd>

                        @if($ticket->contact)
                        <dt class="col-sm-5">Contact:</dt>
                        <dd class="col-sm-7">{{ $ticket->contact->name }}</dd>
                        @endif

                        <dt class="col-sm-5">Created:</dt>
                        <dd class="col-sm-7">{{ $ticket->created_at->format('M d, Y g:i A') }}</dd>

                        <dt class="col-sm-5">Updated:</dt>
                        <dd class="col-sm-7">{{ $ticket->updated_at->format('M d, Y g:i A') }}</dd>

                        @if($ticket->closed_at)
                        <dt class="col-sm-5">Closed:</dt>
                        <dd class="col-sm-7">{{ $ticket->closed_at->format('M d, Y g:i A') }}</dd>
                        @endif

                        <dt class="col-sm-5">Created By:</dt>
                        <dd class="col-sm-7">{{ $ticket->creator->name }}</dd>

                        <dt class="col-sm-5">Assigned To:</dt>
                        <dd class="col-sm-7">
                            @if($ticket->assignee)
                                {{ $ticket->assignee->name }}
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </dd>

                        @if($ticket->asset)
                        <dt class="col-sm-5">Asset:</dt>
                        <dd class="col-sm-7">
                            <a href="{{ route('assets.show', $ticket->asset) }}">{{ $ticket->asset->name }}</a>
                        </dd>
                        @endif

                        @if($ticket->vendor)
                        <dt class="col-sm-5">Vendor:</dt>
                        <dd class="col-sm-7">{{ $ticket->vendor->name }}</dd>
                        @endif

                        @if($ticket->vendor_ticket_number)
                        <dt class="col-sm-5">Vendor Ticket:</dt>
                        <dd class="col-sm-7">{{ $ticket->vendor_ticket_number }}</dd>
                        @endif

                        <dt class="col-sm-5">Billable:</dt>
                        <dd class="col-sm-7">
                            @if($ticket->billable)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </dd>

                        @if($ticket->schedule)
                        <dt class="col-sm-5">Scheduled:</dt>
                        <dd class="col-sm-7">
                            {{ $ticket->schedule->format('M d, Y g:i A') }}
                            @if($ticket->onsite)
                                <span class="badge badge-info">Onsite</span>
                            @endif
                        </dd>
                        @endif

                        <dt class="col-sm-5">Total Time:</dt>
                        <dd class="col-sm-7">{{ $ticket->getTotalTimeWorked() }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <div class="btn-group btn-group-sm w-100">
                        <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @if($ticket->status !== 'Closed')
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#closeTicketModal">
                            <i class="fas fa-check"></i> Close
                        </button>
                        @endif
                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteTicketModal">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Other Viewers -->
            @if(count($otherViewers) > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">Currently Viewing</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @foreach($otherViewers as $viewer)
                        <li>
                            <i class="fas fa-eye text-info"></i> {{ $viewer['name'] }}
                            <small class="text-muted">({{ $viewer['last_viewed'] }})</small>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <button class="btn btn-sm btn-block btn-outline-primary" data-toggle="modal" data-target="#assignModal">
                        <i class="fas fa-user-check"></i> Assign Ticket
                    </button>
                    <button class="btn btn-sm btn-block btn-outline-info" data-toggle="modal" data-target="#scheduleModal">
                        <i class="fas fa-calendar"></i> Schedule Ticket
                    </button>
                    <button class="btn btn-sm btn-block btn-outline-warning" data-toggle="modal" data-target="#mergeModal">
                        <i class="fas fa-code-branch"></i> Merge Ticket
                    </button>
                    <a href="{{ route('tickets.pdf', $ticket) }}" class="btn btn-sm btn-block btn-outline-secondary">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Close Ticket Modal -->
<div class="modal fade" id="closeTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tickets.status.update', $ticket) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="Closed">
                <div class="modal-header">
                    <h5 class="modal-title">Close Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to close this ticket?</p>
                    <div class="form-group">
                        <label for="close_reason">Closing Note (Optional)</label>
                        <textarea name="close_reason" id="close_reason" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Close Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Ticket Modal -->
<div class="modal fade" id="deleteTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tickets.destroy', $ticket) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Delete Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this ticket? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tickets.assign', $ticket) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Assign Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="assigned_to">Assign To</label>
                        <select name="assigned_to" id="assigned_to" class="form-control" required>
                            <option value="">Select User</option>
                            @foreach(\App\Models\User::where('company_id', auth()->user()->company_id)->where('status', 1)->orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}" {{ $ticket->assigned_to == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tickets.schedule', $ticket) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="scheduled_at">Schedule Date/Time</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduled_at" 
                               class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="onsite">Onsite Visit</label>
                        <select name="onsite" id="onsite" class="form-control">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Merge Modal -->
<div class="modal fade" id="mergeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tickets.merge', $ticket) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Merge Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="merge_into_ticket_number">Merge Into Ticket Number</label>
                        <input type="number" name="merge_into_ticket_number" id="merge_into_ticket_number" 
                               class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="merge_comment">Merge Comment</label>
                        <textarea name="merge_comment" id="merge_comment" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Merge Tickets</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh viewer list every 30 seconds
setInterval(function() {
    $.get('{{ route('tickets.viewers', $ticket) }}', function(data) {
        if (data.message) {
            $('#viewer-message').html(data.message);
        }
    });
}, 30000);
</script>
@endpush