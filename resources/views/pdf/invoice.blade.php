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
            margin-bottom: 30px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #007bff;
        }
        .items-table .text-right {
            text-align: right;
        }
        .totals-section {
            float: right;
            width: 300px;
            margin-top: 20px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .totals-table .total-flex flex-wrap {
            font-weight: bold;
            font-size: 14px;
            background-color: #f8f9fa;
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

    <table class="items-min-w-full divide-y divide-gray-200">
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
                    <strong>{{ $item['description'] ?? 'N/A' }}</strong>
                    @if($item['details'] ?? false)
                        <br><small>{{ $item['details'] }}</small>
                    @endif
                </td>
                <td class="text-right">{{ $item['quantity'] ?? 1 }}</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($item['rate'] ?? 0, 2) }}</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format(($item['quantity'] ?? 1) * ($item['rate'] ?? 0), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <table class="totals-min-w-full divide-y divide-gray-200">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($invoice['subtotal'] ?? 0, 2) }}</td>
            </tr>
            @if(($invoice['discount'] ?? 0) > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">-{{ $currency ?? '$' }}{{ number_format($invoice['discount'], 2) }}</td>
            </tr>
            @endif
            @if(($invoice['tax_amount'] ?? 0) > 0)
            <tr>
                <td>Tax ({{ $invoice['tax_rate'] ?? $tax_rate ?? 0 }}%):</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($invoice['tax_amount'], 2) }}</td>
            </tr>
            @endif
            <tr class="total-flex flex-wrap -mx-4">
                <td>Total:</td>
                <td class="text-right">{{ $currency ?? '$' }}{{ number_format($invoice['total'] ?? 0, 2) }}</td>
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
