<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3b82f6; }
        .info-row { margin: 10px 0; }
        .label { font-weight: bold; color: #666; }
        .value { color: #333; }
        .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">👤 Ticket Assigned to You</h1>
        </div>
        
        <div class="content">
            <p>Hi {{ $assignedTo->name }},</p>
            
            <p>You have been assigned to the following support ticket:</p>
            
            <div class="ticket-info">
                <div class="info-row">
                    <span class="label">Ticket:</span>
                    <span class="value">#{{ $ticket->number }} - {{ $ticket->subject }}</span>
                </div>
                
                <div class="info-row">
                    <span class="label">Priority:</span>
                    <span class="value">{{ $ticket->priority }}</span>
                </div>
                
                @if($ticket->client)
                <div class="info-row">
                    <span class="label">Client:</span>
                    <span class="value">{{ $ticket->client->name }}</span>
                </div>
                @endif
                
                @if($assignedBy)
                <div class="info-row">
                    <span class="label">Assigned By:</span>
                    <span class="value">{{ $assignedBy->name }}</span>
                </div>
                @endif
                
                @if($ticket->estimated_resolution_at)
                <div class="info-row">
                    <span class="label">Est. Resolution:</span>
                    <span class="value">{{ $ticket->estimated_resolution_at->format('M j, Y g:i A') }}</span>
                </div>
                @endif
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $ticketUrl }}" class="button">View & Work on Ticket</a>
            </div>
            
            <div class="footer">
                <p>This is an automated notification from your ticketing system.</p>
            </div>
        </div>
    </div>
</body>
</html>
