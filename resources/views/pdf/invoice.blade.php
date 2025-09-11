<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice['number'] ?? 'N/A' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .company-info {
            flex: 1;
        }
        .company-logo {
            max-width: 200px;
            max-height: 80px;
        }
        .invoice-info {
            text-align: right;
            flex: 1;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .billing-info {
            flex: 1;
            margin-right: 20px;
        }
        .billing-info h3 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            border: 1px solid #ddd;
        }
        .items-table th {
            background-color: #007bff;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #0056b3;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .items-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .totals-section {
            float: right;
            width: 300px;
            margin-top: 20px;
            clear: both;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }
        .totals-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .totals-table td:first-child {
            font-weight: 600;
        }
        .totals-table td:last-child {
            text-align: right;
        }
        .totals-table .total-row {
            font-weight: bold;
            font-size: 14px;
            background-color: #f8f9fa;
        }
        .totals-table .total-row td {
            font-weight: bold;
            border-top: 2px solid #007bff;
        }
        .footer {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 11px;
            color: #666;
        }
        .payment-terms {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            @if($company['logo'])
                <img src="{{ $company['logo'] }}" alt="{{ $company['name'] }}" class="company-logo">
            @endif
            <h2>{{ $company['name'] }}</h2>
            <p>
                {{ $company['address'] }}<br>
                Phone: {{ $company['phone'] }}<br>
                Email: {{ $company['email'] }}<br>
                Website: {{ $company['website'] }}
            </p>
        </div>
        <div class="invoice-info">
            <div class="invoice-title">INVOICE</div>
            <p>
                <strong>Invoice #:</strong> {{ $invoice['number'] ?? 'N/A' }}<br>
                <strong>Date:</strong> {{ $invoice['date'] ?? $generated_at->format('Y-m-d') }}<br>
                <strong>Due Date:</strong> {{ $invoice['due_date'] ?? 'N/A' }}<br>
                <strong>Status:</strong> {{ $invoice['status'] ?? 'Draft' }}
            </p>
        </div>
    </div>

    <div class="billing-section">
        <div class="billing-info">
            <h3>Bill To:</h3>
            <p>
                <strong>{{ $client['name'] ?? 'N/A' }}</strong><br>
                {{ $client['address'] ?? '' }}<br>
                {{ $client['city'] ?? '' }}, {{ $client['state'] ?? '' }} {{ $client['zip'] ?? '' }}<br>
                @if($client['phone'])Phone: {{ $client['phone'] }}<br>@endif
                @if($client['email'])Email: {{ $client['email'] }}@endif
            </p>
        </div>
        <div class="billing-info">
            <h3>Service Period:</h3>
            <p>
                <strong>From:</strong> {{ $invoice['service_from'] ?? 'N/A' }}<br>
                <strong>To:</strong> {{ $invoice['service_to'] ?? 'N/A' }}<br>
                <strong>Terms:</strong> {{ $invoice['payment_terms'] ?? 'Net 30' }}
            </p>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items ?? [] as $item)
            <tr>
                <td>
                    <strong>{{ $item->description ?? 'N/A' }}</strong>
                    @if($item->details ?? false)
                        <br><small>{{ $item->details }}</small>
                    @endif
                </td>
                <td class="text-right">{{ $item->quantity ?? 1 }}</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($item->price ?? 0, 2) }}</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($item->total ?? $item->subtotal ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($items->sum('subtotal') ?? 0, 2) }}</td>
            </tr>
            @if(($invoice->discount_amount ?? 0) > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">-{{ $currency ?? '$' }}{{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if(($items->sum('tax') ?? 0) > 0)
            <tr>
                <td>Tax:</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($items->sum('tax'), 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($invoice->amount ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice['notes'] ?? false)
    <div class="payment-terms">
        <h4>Notes:</h4>
        <p>{{ $invoice['notes'] }}</p>
    </div>
    @endif

    <div class="footer">
        <p>
            <strong>Payment Instructions:</strong><br>
            {{ $invoice['payment_instructions'] ?? 'Please remit payment within 30 days of invoice date.' }}
        </p>
        <p>
            Generated on {{ $generated_at->format('Y-m-d H:i:s') }} by {{ $generated_by }}
        </p>
    </div>
</body>
</html>
