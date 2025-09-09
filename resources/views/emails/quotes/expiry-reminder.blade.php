@component('mail::message')
# Quote Expiring Soon

Dear {{ $client->name }},

This is a friendly reminder that your quote is expiring soon.

## Quote Details

- **Quote Number:** {{ $quote->getFullNumber() }}
- **Total Amount:** {{ $quote->getFormattedAmount() }}
- **Expires:** {{ ($quote->expire_date ?? $quote->valid_until)->format('F d, Y') }}

@if($daysUntilExpiry === 1)
@component('mail::panel')
⚠️ **This quote expires tomorrow!**

After expiration, pricing and availability may change. To secure these rates, please accept the quote today.
@endcomponent
@else
@component('mail::panel')
📅 **This quote expires in {{ $daysUntilExpiry }} days**

To ensure you receive the quoted pricing and terms, please review and accept the quote before the expiration date.
@endcomponent
@endif

## Quote Summary

@component('mail::table')
| Item | Quantity | Total |
|:-----|:--------:|------:|
@foreach($quote->items->take(3) as $item)
| {{ Str::limit($item->name, 40) }} | {{ number_format($item->quantity, 2) }} | ${{ number_format($item->total, 2) }} |
@endforeach
@if($quote->items->count() > 3)
| *... and {{ $quote->items->count() - 3 }} more items* | | |
@endif
| | **Total:** | **{{ $quote->getFormattedAmount() }}** |
@endcomponent

@component('mail::button', ['url' => $viewUrl, 'color' => 'primary'])
View & Accept Quote
@endcomponent

## Why Accept Now?

- **Price Protection:** Lock in current pricing before any increases
- **Priority Service:** Accepted quotes receive priority scheduling
- **Peace of Mind:** Secure your project timeline

## Need Changes?

If you need any modifications to this quote, please contact us immediately. We're happy to adjust the quote to better meet your needs.

## Contact Us

Have questions? We're here to help!

- Reply to this email
- Call us at {{ $quote->company->phone ?? config('company.phone') }}
- Visit our website to chat with support

Don't let this opportunity expire!

Best regards,  
{{ $quote->company->name ?? config('app.name') }} Sales Team

@component('mail::subcopy')
This is an automated reminder. You're receiving this because you have an expiring quote with us. 
To stop receiving these reminders, please let us know if you're no longer interested.
@endcomponent
@endcomponent
