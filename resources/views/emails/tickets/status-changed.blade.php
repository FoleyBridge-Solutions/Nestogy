<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .status-change { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 0 10px; }
        .status-old { background: #fee2e2; color: #991b1b; }
        .status-new { background: #d1fae5; color: #065f46; }
        .arrow { color: #666; font-size: 24px; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">📝 Ticket Status Updated</h1>
        </div>
        
        <div class="content">
            <p>The status of ticket #{{ $ticket->number }} has been updated.</p>
            
            <div class="status-change">
                <span class="status-badge status-old">{{ $oldStatus }}</span>
                <span class="arrow">→</span>
                <span class="status-badge status-new">{{ $newStatus }}</span>
            </div>
            
            <div class="ticket-info">
                <h3 style="margin-top: 0;">{{ $ticket->subject }}</h3>
                <p><strong>Ticket:</strong> #{{ $ticket->number }}</p>
                <p><strong>Priority:</strong> {{ $ticket->priority }}</p>
                @if($ticket->client)
                <p><strong>Client:</strong> {{ $ticket->client->name }}</p>
                @endif
                <p><strong>Updated:</strong> {{ $ticket->updated_at->format('M j, Y g:i A') }}</p>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $ticketUrl }}" class="button">View Ticket Details</a>
            </div>
            
            <div class="footer">
                <p>This is an automated notification from your ticketing system.</p>
            </div>
        </div>
    </div>
</body>
</html>
