<div>
    @if (session()->has('success'))
        <flux:toast>{{ session('success') }}</flux:toast>
    @endif

    @include('livewire.base-index')
</div>
