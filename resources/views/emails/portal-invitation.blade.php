<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal Invitation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0 0 10px 0;
        }
        .subtitle {
            color: #7f8c8d;
            font-size: 16px;
            margin: 0;
        }
        .content {
            margin-bottom: 30px;
        }
        .button-container {
            text-align: center;
            margin: 40px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #3b82f6;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .info-box p {
            margin: 5px 0;
            color: #555;
        }
        .expiration-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-notice {
            font-size: 14px;
            color: #6c757d;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 20px;
            }
            h1 {
                font-size: 20px;
            }
            .button {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Your Client Portal</h1>
            <p class="subtitle">{{ $companyName }}</p>
        </div>

        <div class="content">
            <p>Hi {{ $contactName }},</p>
            
            <p>You've been invited to access the {{ $clientName }} client portal. This secure portal will give you access to:</p>
            
            <ul>
                <li>View and manage your contracts</li>
                <li>Access invoices and billing information</li>
                <li>Track support tickets and requests</li>
                <li>Monitor your assets and services</li>
                <li>View important documents and reports</li>
            </ul>

            <p>To get started, simply click the button below to set up your password:</p>
        </div>

        <div class="button-container">
            <a href="{{ $invitationUrl }}" class="button">Set Up Your Password</a>
        </div>

        <div class="expiration-notice">
            <h3>⏱️ Time Sensitive</h3>
            <p>This invitation will expire in <strong>{{ $expiresInHours }} hours</strong> ({{ $expiresAt->format('F j, Y \a\t g:i A') }}).</p>
            <p>Please set up your password before the expiration time.</p>
        </div>

        <div class="info-box">
            <h3>📋 What You'll Need</h3>
            <p><strong>Email:</strong> {{ $contact->email }}</p>
            <p><strong>Time Required:</strong> Less than 2 minutes</p>
            <p><strong>Password Requirements:</strong> Minimum 8 characters with uppercase, lowercase, and numbers</p>
        </div>

        <div class="security-notice">
            <p><strong>🔒 Security Notice:</strong> This invitation link is unique to you and can only be used once. If you did not expect this invitation or have any concerns, please contact your account manager immediately.</p>
            
            <p>If you're unable to click the button above, copy and paste this link into your browser:</p>
            <p style="word-break: break-all; color: #3b82f6;">{{ $invitationUrl }}</p>
        </div>

        <div class="footer">
            <p>Need help? Contact our support team at <a href="mailto:support@{{ request()->getHost() }}">support@{{ request()->getHost() }}</a></p>
            <p>&copy; {{ date('Y') }} {{ $companyName }}. All rights reserved.</p>
            <p style="font-size: 12px; margin-top: 10px;">
                This email was sent to {{ $contact->email }} because you were invited to access the client portal.
            </p>
        </div>
    </div>
</body>
</html>