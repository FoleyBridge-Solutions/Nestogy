@props([
    'tabs' => [],
    'activeTab' => null
])

<flux:tabs value="{{ $activeTab }}">
    <flux:tabs.list>
        @foreach($tabs as $key => $label)
            <flux:tabs.item value="{{ $key }}">
                {{ $label }}
            </flux:tabs.item>
        @endforeach
    </flux:tabs.list>
</flux:tabs>