<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .warning-box { background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b; text-align: center; }
        .warning-icon { font-size: 48px; margin-bottom: 10px; }
        .countdown { font-size: 32px; font-weight: bold; color: #d97706; margin: 20px 0; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background: #f59e0b; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">⚠️ SLA Breach Warning</h1>
        </div>
        
        <div class="content">
            <div class="warning-box">
                <div class="warning-icon">⚠️</div>
                <h2 style="margin: 10px 0; color: #d97706;">Urgent Attention Required!</h2>
                <p style="margin: 10px 0;">Ticket #{{ $ticket->number }} is approaching its SLA deadline.</p>
                <div class="countdown">{{ $hoursRemaining }} hours remaining</div>
            </div>
            
            <div class="ticket-info">
                <h3 style="margin-top: 0;">{{ $ticket->subject }}</h3>
                <p><strong>Ticket:</strong> #{{ $ticket->number }}</p>
                <p><strong>Priority:</strong> {{ $ticket->priority }}</p>
                <p><strong>Status:</strong> {{ $ticket->status }}</p>
                @if($ticket->assignee)
                <p><strong>Assigned To:</strong> {{ $ticket->assignee->name }}</p>
                @else
                <p style="color: #dc2626;"><strong>⚠️ Status:</strong> UNASSIGNED</p>
                @endif
                @if($ticket->client)
                <p><strong>Client:</strong> {{ $ticket->client->name }}</p>
                @endif
                <p><strong>SLA Deadline:</strong> {{ $slaDeadline->format('M j, Y g:i A') }}</p>
                <p><strong>Time Remaining:</strong> {{ $slaDeadline->diffForHumans() }}</p>
            </div>
            
            <div style="background: #fee2e2; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <p style="margin: 0; color: #991b1b;"><strong>⏰ Action Required:</strong> Please review and respond to this ticket immediately to avoid SLA breach.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $ticketUrl }}" class="button">View & Respond Now</a>
            </div>
            
            <div class="footer">
                <p>This is an automated SLA warning from your ticketing system.</p>
            </div>
        </div>
    </div>
</body>
</html>
