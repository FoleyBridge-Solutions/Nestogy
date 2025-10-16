@php
    $balance = $item->getBalance();
    $balanceClass = $balance <= 0 ? 'text-green-600' : '';
@endphp
<flux:text variant="strong" class="{{ $balanceClass }}">
    ${{ number_format($balance, 2) }}
</flux:text>
