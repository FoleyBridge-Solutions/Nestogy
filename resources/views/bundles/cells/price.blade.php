@if($item->pricing_type === 'fixed' && $item->fixed_price)
    ${{ number_format($item->fixed_price, 2) }}
@elseif($item->pricing_type === 'percentage_discount' && $item->discount_percentage)
    {{ number_format($item->discount_percentage, 0) }}% off
@else
    <span class="text-gray-500">Calculated</span>
@endif
