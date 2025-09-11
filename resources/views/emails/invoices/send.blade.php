<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice from {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .invoice-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .due-date {
            color: #dc3545;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name') }}</div>
    </div>

    <h2>Invoice #{{ $invoice->invoice_number ?? $invoice->number }}</h2>

    @if($customMessage)
        <div style="margin: 20px 0; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #007bff;">
            {!! nl2br(e($customMessage)) !!}
        </div>
    @else
        <p>Hello {{ $client->name ?? 'Valued Customer' }},</p>
        
        <p>Please find your invoice details below. You can view and download your invoice using the link provided.</p>
    @endif

    <div class="invoice-details">
        <h3>Invoice Details</h3>
        <p><strong>Invoice Number:</strong> #{{ $invoice->invoice_number ?? $invoice->number }}</p>
        <p><strong>Invoice Date:</strong> {{ $invoice->date ? $invoice->date->format('F j, Y') : $invoice->created_at->format('F j, Y') }}</p>
        <p><strong>Due Date:</strong> 
            @if($invoice->due_date)
                <span class="due-date">{{ $invoice->due_date->format('F j, Y') }}</span>
            @else
                <span>Upon receipt</span>
            @endif
        </p>
        <p><strong>Amount Due:</strong> <span class="amount">${{ $totalAmount }}</span></p>
    </div>

    @if($viewUrl)
        <p style="text-align: center;">
            <a href="{{ $viewUrl }}" class="btn">View Invoice Online</a>
        </p>
    @endif

    <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>

    <p>Thank you for your business!</p>

    <div class="footer">
        <p>This is an automated message from {{ config('app.name') }}.</p>
        @if(config('mail.from.address'))
            <p>If you have questions, please reply to this email or contact us at {{ config('mail.from.address') }}.</p>
        @endif
    </div>
</body>
</html>