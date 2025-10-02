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
        .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .resolution-summary { background: #d1fae5; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981; }
        .button { display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; margin: 10px 5px; }
        .button-secondary { background: #6b7280; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">✅ Ticket Resolved</h1>
        </div>
        
        <div class="content">
            <div class="success-icon">✅</div>
            
            <p>Great news! Your support ticket has been resolved.</p>
            
            <div class="ticket-info">
                <h3 style="margin-top: 0;">{{ $ticket->subject }}</h3>
                <p><strong>Ticket:</strong> #{{ $ticket->number }}</p>
                @if($resolvedBy)
                <p><strong>Resolved By:</strong> {{ $resolvedBy->name }}</p>
                @endif
                <p><strong>Resolved At:</strong> {{ $ticket->resolved_at->format('M j, Y g:i A') }}</p>
            </div>
            
            @if($ticket->resolution_summary)
            <div class="resolution-summary">
                <h4 style="margin-top: 0;">Resolution Summary:</h4>
                <p style="margin-bottom: 0;">{{ $ticket->resolution_summary }}</p>
            </div>
            @endif
            
            <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
                <h3 style="margin-top: 0;">How was your experience?</h3>
                <p>We'd love to hear your feedback about this support interaction.</p>
                <a href="{{ $surveyUrl }}" class="button">Rate Your Experience</a>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $ticketUrl }}" class="button button-secondary">View Ticket Details</a>
            </div>
            
            <div class="footer">
                <p>If you have any additional questions or concerns, feel free to reopen this ticket or create a new one.</p>
                <p>This is an automated notification from your ticketing system.</p>
            </div>
        </div>
    </div>
</body>
</html>
