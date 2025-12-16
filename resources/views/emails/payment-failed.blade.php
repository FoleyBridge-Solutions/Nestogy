<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .error-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .payment-details {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #fca5a5;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #7f1d1d;
            font-weight: 500;
        }
        .detail-value {
            font-weight: 600;
            color: #991b1b;
        }
        .amount {
            font-size: 24px;
            color: #dc2626;
        }
        .error-message {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .button {
            display: inline-block;
            background: #ef4444;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button-secondary {
            background: #6b7280;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 20px 0;
        }
        .help-box {
            background: #f3f4f6;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .help-box h3 {
            margin-top: 0;
            color: #111827;
        }
        .help-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .help-box li {
            margin: 8px 0;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✗ Payment Failed</h1>
        <p>We were unable to process your payment</p>
    </div>

    <div class="content">
        <div class="error-icon">✗</div>
        
        <p>Dear {{ $client->name }},</p>
        
        <p>We encountered an issue while processing your payment. Please review the details below and try again.</p>

        <div class="error-message">
            <strong>Error:</strong> {{ $errorMessage }}
        </div>

        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Attempted Amount:</span>
                <span class="detail-value amount">${{ number_format($attemptedAmount, 2) }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Invoice Number:</span>
                <span class="detail-value">#{{ $invoice->number }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Invoice Due Date:</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Attempt Time:</span>
                <span class="detail-value">{{ now()->format('M d, Y g:i A') }}</span>
            </div>
        </div>

        <div class="help-box">
            <h3>What Should I Do?</h3>
            <p>Here are some common reasons why payments fail and how to resolve them:</p>
            <ul>
                <li><strong>Insufficient Funds:</strong> Ensure your account has enough funds to cover the payment.</li>
                <li><strong>Incorrect Card Information:</strong> Double-check your card number, expiration date, and security code.</li>
                <li><strong>Expired Card:</strong> Make sure your payment method hasn't expired.</li>
                <li><strong>Billing Address Mismatch:</strong> Verify that your billing address matches what's on file with your bank.</li>
                <li><strong>Bank Declined:</strong> Contact your bank if the issue persists.</li>
            </ul>
        </div>

        <div class="divider"></div>

        <p style="text-align: center;">
            <a href="{{ route('client.invoices.pay', $invoice) }}" class="button">
                Try Payment Again
            </a>
            <br>
            <a href="{{ route('client.invoices.show', $invoice) }}" class="button button-secondary" style="margin-top: 10px;">
                View Invoice
            </a>
        </p>

        <p style="background: #eff6ff; border: 1px solid #3b82f6; border-radius: 6px; padding: 15px; color: #1e40af;">
            <strong>Need Help?</strong> If you continue to experience issues, please contact our support team. We're here to help!
        </p>

        <p>
            Best regards,<br>
            <strong>{{ config('app.name') }}</strong>
        </p>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
