@component('mail::message')
# Quote #{{ $quote->getFullNumber() }}

Dear {{ $client->name }},

We are pleased to provide you with the following quote for your review.

## Quote Details

- **Quote Number:** {{ $quote->getFullNumber() }}
- **Date:** {{ $quote->date->format('F d, Y') }}
- **Valid Until:** {{ ($quote->valid_until ?? $quote->expire_date)->format('F d, Y') }}
- **Total Amount:** {{ $quote->getFormattedAmount() }}

@component('mail::panel')
This quote is valid until **{{ ($quote->valid_until ?? $quote->expire_date)->format('F d, Y') }}**. 
Prices and availability are subject to change after this date.
@endcomponent

## Quote Summary

@if($quote->scope)
**Scope of Work:** {{ $quote->scope }}
@endif

@component('mail::table')
| Item | Quantity | Price | Total |
|:-----|:--------:|------:|------:|
@foreach($quote->items->take(5) as $item)
| {{ Str::limit($item->name, 30) }} | {{ number_format($item->quantity, 2) }} | ${{ number_format($item->price, 2) }} | ${{ number_format($item->total, 2) }} |
@endforeach
@if($quote->items->count() > 5)
| *... and {{ $quote->items->count() - 5 }} more items* | | | |
@endif
@endcomponent

@component('mail::button', ['url' => $viewUrl, 'color' => 'primary'])
View Full Quote
@endcomponent

## Next Steps

1. Review the quote details and pricing
2. Contact us if you have any questions or need modifications
3. Accept the quote online or reply to this email

@if($quote->note)
## Additional Notes
{{ $quote->note }}
@endif

If you have any questions or would like to discuss this quote, please don't hesitate to contact us.

Thank you for considering our services!

Best regards,  
{{ $quote->company->name ?? config('app.name') }}  
{{ $quote->company->phone ?? '' }}  
{{ $quote->company->email ?? config('mail.from.address') }}

@component('mail::subcopy')
This quote was generated on {{ now()->format('F d, Y') }} and is confidential. 
If you received this email in error, please notify us immediately.
@endcomponent
@endcomponent