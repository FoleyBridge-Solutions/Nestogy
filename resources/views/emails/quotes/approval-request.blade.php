@component('mail::message')
# Quote Approval Required

Hello {{ $approver->name }},

A quote requires your approval before it can be sent to the client.

## Quote Information

- **Quote Number:** {{ $quote->getFullNumber() }}
- **Client:** {{ $client->name }}
- **Total Amount:** {{ $totalAmount }}
- **Created By:** {{ $quote->creator->name ?? 'System' }}
- **Date:** {{ $quote->date->format('F d, Y') }}

## Quote Summary

@if($quote->scope)
**Scope:** {{ $quote->scope }}
@endif

@component('mail::table')
| Description | Amount |
|:------------|-------:|
| Subtotal | ${{ number_format($quote->getSubtotal(), 2) }} |
@if($quote->getDiscountAmount() > 0)
| Discount | -${{ number_format($quote->getDiscountAmount(), 2) }} |
@endif
@if($quote->getTotalTax() > 0)
| Tax | ${{ number_format($quote->getTotalTax(), 2) }} |
@endif
| **Total** | **{{ $totalAmount }}** |
@endcomponent

## Approval Thresholds

Based on the quote amount, this requires:
@if($quote->amount >= 25000)
- Executive approval (Amount exceeds $25,000)
@elseif($quote->amount >= 5000)
- Manager approval (Amount exceeds $5,000)
@else
- Standard approval
@endif

@component('mail::button', ['url' => $approvalUrl, 'color' => 'success'])
Review & Approve Quote
@endcomponent

## Important Notes

- Please review the quote details carefully
- Check pricing and terms are accurate
- Verify client information is correct
- Ensure all required items are included

@if($quote->note)
### Quote Notes
{{ $quote->note }}
@endif

If you have any questions about this quote, please contact {{ $quote->creator->name ?? 'the creator' }} directly.

Thank you for your prompt attention to this matter.

Best regards,  
{{ config('app.name') }} System

@component('mail::subcopy')
This is an automated approval request. You are receiving this because you have approval authority for quotes in this amount range.
@endcomponent
@endcomponent
