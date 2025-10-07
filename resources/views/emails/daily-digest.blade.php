<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f3f4f6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: white; padding: 30px; }
        .footer-section { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; text-align: center; }
        .section { margin: 30px 0; }
        .section-title { font-size: 18px; font-weight: bold; color: #1f2937; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
        .ticket-item { background: #f9fafb; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; border-radius: 4px; }
        .ticket-number { font-weight: bold; color: #667eea; }
        .priority-critical { border-left-color: #dc2626; }
        .priority-high { border-left-color: #f59e0b; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        .footer-text { color: #6b7280; font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">📊 Daily Digest</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ now()->format('l, F j, Y') }}</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $user->name }},</p>
            <p>Here's your daily summary of ticket activity and important updates.</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ count($newTickets) }}</div>
                    <div class="stat-label">New Tickets</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="stat-number">{{ count($resolvedTickets) }}</div>
                    <div class="stat-label">Resolved Tickets</div>
                </div>
            </div>

            @if(count($overdueTickets) > 0)
                <div class="section">
                    <div class="section-title" style="color: #dc2626;">🚨 Overdue Tickets ({{ count($overdueTickets) }})</div>
                    @foreach($overdueTickets as $ticket)
                        <div class="ticket-item priority-critical">
                            <div class="ticket-number">#{{ $ticket->number }} - {{ $ticket->priority }}</div>
                            <div>{{ $ticket->subject }}</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                                Created {{ $ticket->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($highPriorityTickets) > 0)
                <div class="section">
                    <div class="section-title" style="color: #f59e0b;">⚠️ High Priority Tickets ({{ count($highPriorityTickets) }})</div>
                    @foreach($highPriorityTickets as $ticket)
                        <div class="ticket-item priority-high">
                            <div class="ticket-number">#{{ $ticket->number }} - {{ $ticket->priority }}</div>
                            <div>{{ $ticket->subject }}</div>
                            @if($ticket->assignee)
                                <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                                    Assigned to {{ $ticket->assignee->name }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($newTickets) > 0)
                <div class="section">
                    <div class="section-title">🆕 New Tickets ({{ count($newTickets) }})</div>
                    @foreach($newTickets as $ticket)
                        <div class="ticket-item">
                            <div class="ticket-number">#{{ $ticket->number }} - {{ $ticket->priority }}</div>
                            <div>{{ $ticket->subject }}</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                                {{ $ticket->created_at->format('g:i A') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($assignedTickets) > 0)
                <div class="section">
                    <div class="section-title">👤 Your Assigned Tickets ({{ count($assignedTickets) }})</div>
                    @foreach($assignedTickets as $ticket)
                        <div class="ticket-item">
                            <div class="ticket-number">#{{ $ticket->number }} - {{ $ticket->status }}</div>
                            <div>{{ $ticket->subject }}</div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                                Updated {{ $ticket->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($resolvedTickets) > 0)
                <div class="section">
                    <div class="section-title" style="color: #10b981;">✅ Recently Resolved ({{ count($resolvedTickets) }})</div>
                    @foreach($resolvedTickets as $ticket)
                        <div class="ticket-item" style="border-left-color: #10b981;">
                            <div class="ticket-number">#{{ $ticket->number }}</div>
                            <div>{{ $ticket->subject }}</div>
                            @if($ticket->resolver)
                                <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">
                                    Resolved by {{ $ticket->resolver->name }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="footer-section">
            <p class="footer-text">
                This is your automated daily digest. To change your notification preferences, 
                visit your <a href="{{ route('settings.notifications') }}" style="color: #667eea;">notification settings</a>.
            </p>
            <p class="footer-text" style="margin-top: 15px;">
                {{ config('app.name') }} &copy; {{ now()->year }}
            </p>
        </div>
    </div>
</body>
</html>
