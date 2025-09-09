<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $ticket->ticket_number ?? $ticket->number }} - {{ $ticket->subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .ticket-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .info-section h3 {
            margin-top: 0;
            color: #495057;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        .badge-primary { background: #007bff; color: white; }
        .badge-dark { background: #343a40; color: white; }
        .replies {
            margin-top: 30px;
        }
        .reply {
            border-left: 3px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        .reply-header {
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }
        .time-entries {
            margin-top: 30px;
        }
        .time-entry {
            border-left: 3px solid #28a745;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ticket #{{ $ticket->ticket_number ?? $ticket->number }}</h1>
        <h2>{{ $ticket->subject }}</h2>
    </div>

    <div class="ticket-info">
        <div class="info-section">
            <h3>Ticket Information</h3>
            <p><strong>Status:</strong> <span class="badge badge-{{ $ticket->getStatusColor() }}">{{ $ticket->status }}</span></p>
            <p><strong>Priority:</strong> <span class="badge badge-{{ $ticket->getPriorityColor() }}">{{ $ticket->priority }}</span></p>
            <p><strong>Category:</strong> {{ $ticket->category }}</p>
            <p><strong>Source:</strong> {{ $ticket->source }}</p>
            <p><strong>Created:</strong> {{ $ticket->created_at->format('M j, Y g:i A') }}</p>
            @if($ticket->closed_at)
                <p><strong>Closed:</strong> {{ $ticket->closed_at->format('M j, Y g:i A') }}</p>
            @endif
        </div>

        <div class="info-section">
            <h3>Client & Assignment</h3>
            <p><strong>Client:</strong> {{ $ticket->client->name ?? 'N/A' }}</p>
            @if($ticket->contact)
                <p><strong>Contact:</strong> {{ $ticket->contact->name }}</p>
            @endif
            <p><strong>Created by:</strong> {{ $ticket->creator->name ?? 'N/A' }}</p>
            <p><strong>Assigned to:</strong> {{ $ticket->assignee->name ?? 'Unassigned' }}</p>
            @if($ticket->asset)
                <p><strong>Asset:</strong> {{ $ticket->asset->name }}</p>
            @endif
        </div>
    </div>

    <div class="info-section">
        <h3>Description</h3>
        <p>{!! nl2br(e($ticket->details)) !!}</p>
    </div>

    @if($totalTimeWorked > 0)
    <div class="info-section">
        <h3>Time Summary</h3>
        <p><strong>Total Time Worked:</strong> {{ number_format($totalTimeWorked, 2) }} hours</p>
        <p><strong>Billable Time:</strong> {{ number_format($billableTimeWorked, 2) }} hours</p>
    </div>
    @endif

    @if($ticket->replies->count() > 0)
    <div class="replies">
        <h3>Replies & Updates</h3>
        @foreach($ticket->replies as $reply)
        <div class="reply">
            <div class="reply-header">
                {{ $reply->user->name ?? 'Unknown User' }} - {{ $reply->created_at->format('M j, Y g:i A') }}
                @if($reply->time_worked)
                    <span style="float: right;">⏱️ {{ $reply->getFormattedTimeWorked() }}</span>
                @endif
            </div>
            <div class="reply-content">
                {!! nl2br(e($reply->reply)) !!}
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($ticket->timeEntries->count() > 0)
    <div class="time-entries">
        <h3>Time Entries</h3>
        @foreach($ticket->timeEntries as $entry)
        <div class="time-entry">
            <strong>{{ $entry->user->name ?? 'Unknown User' }}</strong> - {{ $entry->work_date }} - {{ $entry->hours_worked }}h
            @if($entry->description)
                <br>{{ $entry->description }}
            @endif
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('M j, Y g:i A') }}</p>
        <p>This is a system-generated report for Ticket #{{ $ticket->ticket_number ?? $ticket->number }}</p>
    </div>
</body>
</html>