<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $ticket['id'] ?? 'N/A' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
        }
        .company-info {
            flex: 1;
        }
        .company-logo {
            max-width: 200px;
            max-height: 80px;
        }
        .ticket-info {
            text-align: right;
            flex: 1;
        }
        .ticket-title {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-open { background-color: #dc3545; color: white; }
        .status-in-progress { background-color: #ffc107; color: black; }
        .status-resolved { background-color: #28a745; color: white; }
        .status-closed { background-color: #6c757d; color: white; }
        .priority-low { color: #28a745; }
        .priority-medium { color: #ffc107; }
        .priority-high { color: #fd7e14; }
        .priority-critical { color: #dc3545; }
        .ticket-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .detail-section {
            flex: 1;
            margin-right: 20px;
        }
        .detail-section h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #28a745;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        .description-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
        }
        .description-section h3 {
            margin-top: 0;
            color: #28a745;
        }
        .timeline-section {
            margin-bottom: 30px;
        }
        .timeline-item {
            border-left: 3px solid #dee2e6;
            padding-left: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 5px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background-color: #28a745;
        }
        .timeline-date {
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .timeline-content {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
        .attachments-section {
            margin-bottom: 30px;
        }
        .attachment-item {
            display: inline-block;
            margin: 5px 10px 5px 0;
            padding: 5px 10px;
            background-color: #e9ecef;
            border-radius: 4px;
            font-size: 11px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        .time-tracking {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .time-entry {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .time-entry:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            @if($company['logo'])
                <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" class="company-logo">
            @endif
            <h2>{{ $company['name'] }}</h2>
            <p>
                {{ $company['address'] }}<br>
                Phone: {{ $company['phone'] }}<br>
                Email: {{ $company['email'] }}
            </p>
        </div>
        <div class="ticket-info">
            <div class="ticket-title">TICKET</div>
            <p>
                <strong>Ticket #:</strong> {{ $ticket['id'] ?? 'N/A' }}<br>
                <strong>Created:</strong> {{ $ticket['created_at'] ?? $generated_at->format('Y-m-d H:i') }}<br>
                <strong>Updated:</strong> {{ $ticket['updated_at'] ?? 'N/A' }}<br>
                <strong>Status:</strong> <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $ticket['status'] ?? 'open')) }}">{{ $ticket['status'] ?? 'Open' }}</span>
            </p>
        </div>
    </div>

    <div class="ticket-details">
        <div class="detail-section">
            <h3>Client Information</h3>
            <p>
                <strong>{{ $client['name'] ?? 'N/A' }}</strong><br>
                {{ $client['company'] ?? '' }}<br>
                {{ $client['email'] ?? '' }}<br>
                {{ $client['phone'] ?? '' }}
            </p>
        </div>
        <div class="detail-section">
            <h3>Ticket Details</h3>
            <p>
                <strong>Priority:</strong> <span class="priority-{{ strtolower($ticket['priority'] ?? 'medium') }}">{{ ucfirst($ticket['priority'] ?? 'Medium') }}</span><br>
                <strong>Category:</strong> {{ $ticket['category'] ?? 'General' }}<br>
                <strong>Assigned To:</strong> {{ $ticket['assigned_to'] ?? 'Unassigned' }}<br>
                <strong>Due Date:</strong> {{ $ticket['due_date'] ?? 'Not set' }}
            </p>
        </div>
        <div class="detail-section">
            <h3>Contact Information</h3>
            <p>
                <strong>Location:</strong> {{ $ticket['location'] ?? 'N/A' }}<br>
                <strong>Asset:</strong> {{ $ticket['asset'] ?? 'N/A' }}<br>
                <strong>Source:</strong> {{ $ticket['source'] ?? 'Manual' }}<br>
                <strong>Type:</strong> {{ $ticket['type'] ?? 'Support' }}
            </p>
        </div>
    </div>

    <div class="description-section">
        <h3>Subject: {{ $ticket['subject'] ?? 'No Subject' }}</h3>
        <div>
            {!! nl2br(e($ticket['description'] ?? 'No description provided.')) !!}
        </div>
    </div>

    @if($time_entries ?? false)
    <div class="time-tracking">
        <h3>Time Tracking</h3>
        @foreach($time_entries as $entry)
        <div class="time-entry">
            <div>
                <strong>{{ $entry['user'] ?? 'Unknown' }}</strong> - {{ $entry['description'] ?? 'No description' }}<br>
                <small>{{ $entry['date'] ?? 'N/A' }}</small>
            </div>
            <div>
                <strong>{{ $entry['hours'] ?? '0.00' }}h</strong>
            </div>
        </div>
        @endforeach
        <div class="time-entry" style="font-weight: bold; background-color: #e9ecef;">
            <div>Total Time:</div>
            <div>{{ $total_hours ?? '0.00' }}h</div>
        </div>
    </div>
    @endif

    @if($timeline ?? false)
    <div class="timeline-section">
        <h3>Activity Timeline</h3>
        @foreach($timeline as $item)
        <div class="timeline-item">
            <div class="timeline-date">{{ $item['date'] ?? 'N/A' }} by {{ $item['user'] ?? 'System' }}</div>
            <div class="timeline-content">
                <strong>{{ $item['action'] ?? 'Update' }}</strong><br>
                {{ $item['description'] ?? 'No description' }}
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($attachments ?? false)
    <div class="attachments-section">
        <h3>Attachments</h3>
        @foreach($attachments as $attachment)
        <div class="attachment-item">
            📎 {{ $attachment['name'] ?? 'Unknown file' }} ({{ $attachment['size'] ?? 'Unknown size' }})
        </div>
        @endforeach
    </div>
    @endif

    @if($resolution ?? false)
    <div class="description-section">
        <h3>Resolution</h3>
        <div>
            {!! nl2br(e($resolution)) !!}
        </div>
        @if($resolved_at ?? false)
        <p><small><strong>Resolved on:</strong> {{ $resolved_at }}</small></p>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>
            This ticket report was generated on {{ $generated_at->format('Y-m-d H:i:s') }} by {{ $generated_by }}.
        </p>
    </div>
</body>
</html>