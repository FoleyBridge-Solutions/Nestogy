<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
        .info-row { margin: 10px 0; }
        .label { font-weight: bold; color: #666; }
        .value { color: #333; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-priority-critical { background: #fee2e2; color: #991b1b; }
        .badge-priority-high { background: #fed7aa; color: #9a3412; }
        .badge-priority-medium { background: #fef3c7; color: #92400e; }
        .badge-priority-low { background: #d1fae5; color: #065f46; }
        .button { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">🎫 New Support Ticket Created</h1>
        </div>
        
        <div class="content">
            <p>A new support ticket has been created and is awaiting attention.</p>
            
            <div class="ticket-info">
                <div class="info-row">
                    <span class="label">Ticket Number:</span>
                    <span class="value">#{{ $ticket->number }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Subject:</span>
                    <span class="value">{{ $ticket->subject }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Priority:</span>
                    <span class="badge badge-priority-{{ strtolower($ticket->priority) }}">{{ $ticket->priority }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Status:</span>
                    <span class="value">{{ $ticket->status }}</span>
                </div>
                
                @if($ticket->client)
                <div class="info-row">
                    <span class="label">Client:</span>
                    <span class="value">{{ $ticket->client->name }}</span>
                </div>
                @endif
                
                @if($ticket->contact)
                <div class="info-row">
                    <span class="label">Contact:</span>
                    <span class="value">{{ $ticket->contact->name }} ({{ $ticket->contact->email }})</span>
                </div>
                @endif
                
                <div class="info-row">
                    <span class="label">Created:</span>
                    <span class="value">{{ $ticket->created_at->format('M j, Y g:i A') }}</span>
                </div>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Details:</h3>
                <p>{{ $ticket->details }}</p>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $ticketUrl }}" class="button">View Ticket</a>
            </div>
            
            <div class="footer">
                <p>This is an automated notification from your ticketing system.</p>
                <p>Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
