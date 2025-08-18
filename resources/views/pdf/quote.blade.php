<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote #{{ $quote->getFullNumber() }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .company-info {
            float: left;
            width: 50%;
        }
        
        .quote-info {
            float: right;
            width: 45%;
            text-align: right;
        }
        
        .company-logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        
        .quote-title {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        
        .quote-number {
            font-size: 18px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .quote-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        .status-draft { background: #e5e7eb; color: #6b7280; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-viewed { background: #fef3c7; color: #92400e; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-declined { background: #fee2e2; color: #991b1b; }
        .status-expired { background: #f3f4f6; color: #4b5563; }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .addresses {
            margin: 30px 0;
        }
        
        .address-block {
            float: left;
            width: 45%;
        }
        
        .address-block:last-child {
            float: right;
        }
        
        .address-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .address-content {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            min-height: 100px;
        }
        
        .address-content strong {
            font-size: 14px;
            color: #111827;
            display: block;
            margin-bottom: 5px;
        }
        
        .items-table {
            width: 100%;
            margin: 30px 0;
            border-collapse: collapse;
        }
        
        .items-table thead {
            background: #f3f4f6;
        }
        
        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .items-table th.text-right,
        .items-table td.text-right {
            text-align: right;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .items-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .items-table td {
            padding: 15px 12px;
            vertical-align: top;
        }
        
        .item-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 3px;
        }
        
        .item-description {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.4;
        }
        
        .totals-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .totals-table {
            width: 350px;
            margin-left: auto;
        }
        
        .totals-table tr {
            line-height: 2;
        }
        
        .totals-table td {
            padding: 5px 10px;
        }
        
        .totals-table .label {
            text-align: right;
            color: #6b7280;
            font-size: 13px;
        }
        
        .totals-table .value {
            text-align: right;
            font-weight: 600;
            font-size: 13px;
            color: #111827;
        }
        
        .totals-table .total-row {
            border-top: 2px solid #2563eb;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .totals-table .total-row .label {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
        
        .totals-table .total-row .value {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .notes-section {
            margin-top: 40px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .notes-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 10px;
        }
        
        .notes-content {
            color: #4b5563;
            white-space: pre-wrap;
        }
        
        .terms-section {
            margin-top: 30px;
            padding: 20px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 4px;
        }
        
        .terms-title {
            font-size: 14px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 10px;
        }
        
        .terms-content {
            color: #78350f;
            font-size: 11px;
            line-height: 1.6;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        
        .validity-notice {
            margin: 20px 0;
            padding: 15px;
            background: #dbeafe;
            border-radius: 8px;
            text-align: center;
        }
        
        .validity-notice strong {
            color: #1e40af;
            font-size: 14px;
        }
        
        .approval-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background: #10b981;
            color: white;
            font-weight: bold;
            border-radius: 20px;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        @media print {
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div class="company-info">
            @if($quote->company->logo ?? false)
                <img src="{{ $quote->company->logo }}" alt="{{ $quote->company->name }}" class="company-logo">
            @else
                <div class="company-name">{{ $quote->company->name ?? config('app.name') }}</div>
            @endif
            <div>{{ $quote->company->address ?? '' }}</div>
            <div>{{ $quote->company->city ?? '' }}, {{ $quote->company->state ?? '' }} {{ $quote->company->zip ?? '' }}</div>
            <div>{{ $quote->company->phone ?? '' }}</div>
            <div>{{ $quote->company->email ?? '' }}</div>
        </div>
        
        <div class="quote-info">
            <div class="quote-title">QUOTE</div>
            <div class="quote-number">#{{ $quote->getFullNumber() }}</div>
            <div class="quote-status status-{{ strtolower($quote->status) }}">
                {{ $quote->status }}
            </div>
            
            <table style="margin-top: 15px; font-size: 12px;">
                <tr>
                    <td style="text-align: right; padding: 3px 10px; color: #6b7280;">Date:</td>
                    <td style="text-align: right; font-weight: 600;">{{ $quote->date->format('M d, Y') }}</td>
                </tr>
                @if($quote->expire_date || $quote->valid_until)
                <tr>
                    <td style="text-align: right; padding: 3px 10px; color: #6b7280;">Valid Until:</td>
                    <td style="text-align: right; font-weight: 600;">
                        {{ ($quote->valid_until ?? $quote->expire_date)->format('M d, Y') }}
                    </td>
                </tr>
                @endif
            </table>
        </div>
    </div>
    
    @if($quote->approval_status === 'executive_approved' || $quote->approval_status === 'manager_approved')
    <div class="approval-badge">APPROVED</div>
    @endif
    
    <div class="addresses clearfix">
        <div class="address-block">
            <div class="address-label">Bill To</div>
            <div class="address-content">
                <strong>{{ $quote->client->name }}</strong>
                @if($quote->client->contact_name)
                    <div>Attn: {{ $quote->client->contact_name }}</div>
                @endif
                <div>{{ $quote->client->address }}</div>
                <div>{{ $quote->client->city }}, {{ $quote->client->state }} {{ $quote->client->zip_code }}</div>
                @if($quote->client->phone)
                    <div>{{ $quote->client->phone }}</div>
                @endif
                @if($quote->client->email)
                    <div>{{ $quote->client->email }}</div>
                @endif
            </div>
        </div>
        
        @if($quote->client->shipping_address ?? false)
        <div class="address-block">
            <div class="address-label">Ship To</div>
            <div class="address-content">
                <strong>{{ $quote->client->name }}</strong>
                <div>{{ $quote->client->shipping_address }}</div>
                <div>{{ $quote->client->shipping_city }}, {{ $quote->client->shipping_state }} {{ $quote->client->shipping_zip }}</div>
            </div>
        </div>
        @endif
    </div>
    
    @if($quote->expire_date || $quote->valid_until)
    <div class="validity-notice">
        <strong>This quote is valid until {{ ($quote->valid_until ?? $quote->expire_date)->format('F d, Y') }}</strong>
        <div style="font-size: 11px; color: #4b5563; margin-top: 5px;">
            Prices and availability subject to change after this date
        </div>
    </div>
    @endif
    
    <table class="items-min-w-full divide-y divide-gray-200">
        <thead>
            <tr>
                <th style="width: 40%;">Item</th>
                <th class="text-right" style="width: 12%;">Qty</th>
                <th class="text-right" style="width: 15%;">Unit Price</th>
                <th class="text-right" style="width: 13%;">Discount</th>
                <th class="text-right" style="width: 10%;">Tax</th>
                <th class="text-right" style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quote->items as $item)
            <tr>
                <td>
                    <div class="item-name">{{ $item->name }}</div>
                    @if($item->description)
                    <div class="item-description">{{ $item->description }}</div>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">${{ number_format($item->price, 2) }}</td>
                <td class="text-right">
                    @if($item->discount > 0)
                        -${{ number_format($item->discount, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right">
                    @if($item->tax > 0)
                        ${{ number_format($item->tax, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-right" style="font-weight: 600;">
                    ${{ number_format($item->total, 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 30px; color: #6b7280;">
                    No items added to this quote
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="totals-section">
        <table class="totals-min-w-full divide-y divide-gray-200">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">${{ number_format($quote->getSubtotal(), 2) }}</td>
            </tr>
            
            @if($quote->getDiscountAmount() > 0)
            <tr>
                <td class="label">
                    Discount
                    @if($quote->discount_type === 'percentage')
                        ({{ $quote->discount_amount }}%)
                    @endif
                    :
                </td>
                <td class="value">-${{ number_format($quote->getDiscountAmount(), 2) }}</td>
            </tr>
            @endif
            
            @if($quote->getTotalTax() > 0)
            <tr>
                <td class="label">Tax:</td>
                <td class="value">${{ number_format($quote->getTotalTax(), 2) }}</td>
            </tr>
            @endif
            
            <tr class="total-flex flex-wrap -mx-4">
                <td class="label">Total:</td>
                <td class="value">${{ number_format($quote->amount, 2) }}</td>
            </tr>
        </table>
    </div>
    
    @if($quote->note)
    <div class="notes-section">
        <div class="notes-title">Notes</div>
        <div class="notes-content">{{ $quote->note }}</div>
    </div>
    @endif
    
    @if($quote->terms_conditions)
    <div class="terms-section">
        <div class="terms-title">Terms & Conditions</div>
        <div class="terms-content">{{ $quote->terms_conditions }}</div>
    </div>
    @endif
    
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>
            {{ $quote->company->name ?? config('app.name') }} | 
            {{ $quote->company->phone ?? '' }} | 
            {{ $quote->company->email ?? '' }} | 
            {{ $quote->company->website ?? '' }}
        </p>
        <p style="margin-top: 10px; font-size: 9px; color: #9ca3af;">
            Generated on {{ now()->format('F d, Y \a\t g:i A') }}
        </p>
    </div>
</body>
</html>