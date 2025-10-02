<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .alert-box { background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626; text-align: center; }
        .alert-icon { font-size: 48px; margin-bottom: 10px; }
        .overdue { font-size: 32px; font-weight: bold; color: #dc2626; margin: 20px 0; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">🚨 SLA BREACHED - IMMEDIATE ACTION REQUIRED</h1>
        </div>
        
        <div class="content">
            <div class="alert-box">
                <div class="alert-icon">🚨</div>
                <h2 style="margin: 10px 0; color: #dc2626;">CRITICAL: SLA Breach Detected!</h2>
                <p style="margin: 10px 0; font-weight: bold;">Ticket #{{ $ticket->number }} has exceeded its SLA deadline.</p>
                <div class="overdue">{{ $hoursOverdue }} hours overdue</div>
            </div>
            
            <div class="ticket-info">
                <h3 style="margin-top: 0; color: #dc2626;">{{ $ticket->subject }}</h3>
                <p><strong>Ticket:</strong> #{{ $ticket->number }}</p>
                <p><strong>Priority:</strong> <span style="color: #dc2626;">{{ $ticket->priority }}</span></p>
                <p><strong>Status:</strong> {{ $ticket->status }}</p>
                @if($ticket->assignee)
                <p><strong>Assigned To:</strong> {{ $ticket->assignee->name }}</p>
                @else
                <p style="color: #dc2626;"><strong>⚠️ Status:</strong> UNASSIGNED - REQUIRES IMMEDIATE ASSIGNMENT</p>
                @endif
                @if($ticket->client)
                <p><strong>Client:</strong> {{ $ticket->client->name }}</p>
                @endif
                <p><strong>SLA Deadline Was:</strong> {{ $slaDeadline->format('M j, Y g:i A') }}</p>
                <p style="color: #dc2626;"><strong>Time Overdue:</strong> {{ $slaDeadline->diffForHumans() }}</p>
                <p><strong>Created:</strong> {{ $ticket->created_at->format('M j, Y g:i A') }}</p>
            </div>
            
            <div style="background: #fef2f2; padding: 20px; border-radius: 8px; border: 2px solid #dc2626; margin: 20px 0;">
                <h3 style="margin-top: 0; color: #dc2626;">📋 Required Actions:</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Review ticket immediately</li>
                    <li>Assign to appropriate technician if unassigned</li>
                    <li>Update client with status and ETA</li>
                    <li>Escalate to management if necessary</li>
                    <li>Document reason for SLA breach</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $ticketUrl }}" class="button">RESPOND IMMEDIATELY</a>
            </div>
            
            <div class="footer">
                <p style="color: #dc2626; font-weight: bold;">This is a CRITICAL automated alert from your ticketing system.</p>
                <p>Management and stakeholders have been notified.</p>
            </div>
        </div>
    </div>
</body>
</html>
