<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .success-icon {
            text-align: center;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .payment-details {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #6b7280;
            font-weight: 500;
        }
        .detail-value {
            font-weight: 600;
            color: #111827;
        }
        .amount {
            font-size: 24px;
            color: #10b981;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>✓ Payment Received</h1>
        <p>Thank you for your payment</p>
    </div>

    <div class="content">
        <div class="success-icon">✓</div>
        
        <p>Dear {{ $client->name }},</p>
        
        <p>We have successfully received your payment. Here are the details of your transaction:</p>

        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Amount Paid:</span>
                <span class="detail-value amount">${{ number_format($payment->amount, 2) }}</span>
            </div>
            
            @if($invoice)
            <div class="detail-row">
                <span class="detail-label">Invoice Number:</span>
                <span class="detail-value">#{{ $invoice->number }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Invoice Date:</span>
                <span class="detail-value">{{ \Carbon\Carbon::parse($invoice->date)->format('M d, Y') }}</span>
            </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Payment Reference:</span>
                <span class="detail-value">{{ $payment->payment_reference }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value" style="font-size: 12px;">{{ $payment->gateway_transaction_id }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Payment Date:</span>
                <span class="detail-value">{{ $payment->payment_date->format('M d, Y g:i A') }}</span>
            </div>
        </div>

        @if($invoice && $invoice->getBalance() > 0)
        <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #92400e;">
                <strong>Remaining Balance:</strong> ${{ number_format($invoice->getBalance(), 2) }}
            </p>
        </div>
        @elseif($invoice)
        <div style="background: #d1fae5; border: 1px solid #10b981; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #065f46;">
                <strong>✓ Invoice Paid in Full</strong>
            </p>
        </div>
        @endif

        <div class="divider"></div>

        <p style="text-align: center;">
            <a href="{{ route('client.invoices.show', $invoice ?? $payment->id) }}" class="button">
                View Invoice
            </a>
        </p>

        <p style="color: #6b7280; font-size: 14px;">
            If you have any questions about this payment, please don't hesitate to contact us.
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
