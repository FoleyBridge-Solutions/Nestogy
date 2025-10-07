<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Digest</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">Daily Ticket Digest</h1>
        <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0;">{{ $data['generated_at']->format('l, F j, Y') }}</p>
    </div>

    <div style="background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;">
        <!-- Summary Stats -->
        <h2 style="color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px;">📊 Summary</h2>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
                <div style="font-size: 24px; font-weight: bold; color: #667eea;">{{ $data['open_tickets'] }}</div>
                <div style="color: #666; font-size: 14px;">Open Tickets</div>
            </div>
            
            <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <div style="font-size: 24px; font-weight: bold; color: #f59e0b;">{{ $data['unassigned_tickets'] }}</div>
                <div style="color: #666; font-size: 14px;">Unassigned</div>
            </div>
            
            <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                <div style="font-size: 24px; font-weight: bold; color: #10b981;">{{ $data['completed_yesterday']->count() }}</div>
                <div style="color: #666; font-size: 14px;">Completed Yesterday</div>
            </div>
            
            <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                <div style="font-size: 24px; font-weight: bold; color: #3b82f6;">{{ $data['new_tickets_yesterday'] }}</div>
                <div style="color: #666; font-size: 14px;">New Yesterday</div>
            </div>
        </div>

        <!-- Priority Tickets -->
        @if($data['critical_tickets'] > 0 || $data['high_priority_tickets'] > 0)
            <h2 style="color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;">⚠️ Priority Attention</h2>
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                @if($data['critical_tickets'] > 0)
                    <div style="padding: 10px; background: #fee2e2; border-left: 4px solid #dc2626; margin-bottom: 10px; border-radius: 4px;">
                        <strong style="color: #dc2626;">{{ $data['critical_tickets'] }} Critical Tickets</strong> require immediate attention
                    </div>
                @endif
                @if($data['high_priority_tickets'] > 0)
                    <div style="padding: 10px; background: #fed7aa; border-left: 4px solid #ea580c; border-radius: 4px;">
                        <strong style="color: #ea580c;">{{ $data['high_priority_tickets'] }} High Priority Tickets</strong>
                    </div>
                @endif
            </div>
        @endif

        <!-- SLA Breaches -->
        @if($data['sla_breaches']->count() > 0)
            <h2 style="color: #dc2626; border-bottom: 2px solid #dc2626; padding-bottom: 10px;">🚨 SLA Breaches</h2>
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                <p style="color: #dc2626; font-weight: bold;">{{ $data['sla_breaches']->count() }} tickets have breached their SLA</p>
                <ul style="list-style: none; padding: 0;">
                    @foreach($data['sla_breaches']->take(5) as $ticket)
                        <li style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">
                            <strong>#{{ $ticket->number }}</strong> - {{ $ticket->subject }}
                            <br>
                            <small style="color: #666;">{{ $ticket->created_at->diffForHumans() }}</small>
                        </li>
                    @endforeach
                </ul>
                @if($data['sla_breaches']->count() > 5)
                    <p style="color: #666; font-size: 14px; margin-top: 10px;">...and {{ $data['sla_breaches']->count() - 5 }} more</p>
                @endif
            </div>
        @endif

        <!-- Top Performers -->
        @if($data['top_performers']->count() > 0)
            <h2 style="color: #10b981; border-bottom: 2px solid #10b981; padding-bottom: 10px;">🏆 Top Performers Yesterday</h2>
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                <ol style="margin: 0; padding-left: 20px;">
                    @foreach($data['top_performers'] as $performer)
                        <li style="padding: 5px 0; color: #333;">
                            <strong>{{ $performer->name }}</strong> - {{ $performer->resolved_yesterday }} tickets resolved
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        <!-- Satisfaction Score -->
        @if($data['avg_satisfaction'])
            <h2 style="color: #8b5cf6; border-bottom: 2px solid #8b5cf6; padding-bottom: 10px;">😊 Customer Satisfaction (7 days)</h2>
            <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 30px; text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: #8b5cf6;">{{ number_format($data['avg_satisfaction'], 1) }}/5</div>
                <div style="color: #666;">Average Rating</div>
            </div>
        @endif

        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
            <p style="color: #666; font-size: 14px; margin: 10px 0;">
                <a href="{{ route('dashboard.team') }}" style="color: #667eea; text-decoration: none;">View Team Dashboard</a>
            </p>
            <p style="color: #999; font-size: 12px;">
                This is an automated digest. To manage your notification preferences,
                <a href="{{ route('settings.notifications') }}" style="color: #667eea;">click here</a>.
            </p>
        </div>
    </div>
</body>
</html>
