<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
        .ticket-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .comment-box { background: #faf5ff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #8b5cf6; }
        .author-info { display: flex; align-items: center; margin-bottom: 15px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: #8b5cf6; color: white; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-weight: bold; }
        .button { display: inline-block; padding: 12px 24px; background: #8b5cf6; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">💬 New Comment Added</h1>
        </div>
        
        <div class="content">
            <p>A new comment has been added to ticket #{{ $ticket->number }}.</p>
            
            <div class="ticket-info">
                <h3 style="margin-top: 0;">{{ $ticket->subject }}</h3>
                <p><strong>Ticket:</strong> #{{ $ticket->number }}</p>
                <p><strong>Status:</strong> {{ $ticket->status }}</p>
            </div>
            
            <div class="comment-box">
                <div class="author-info">
                    <div class="avatar">
                        {{ strtoupper(substr($comment->author->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <strong>{{ $comment->author->name ?? 'Unknown' }}</strong>
                        <div style="font-size: 12px; color: #666;">{{ $comment->created_at->format('M j, Y g:i A') }}</div>
                    </div>
                </div>
                <div style="background: white; padding: 15px; border-radius: 6px;">
                    {{ $comment->content }}
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $ticketUrl }}" class="button">View Full Conversation</a>
            </div>
            
            <div class="footer">
                <p>This is an automated notification from your ticketing system.</p>
            </div>
        </div>
    </div>
</body>
</html>
