<div>
    @if (session()->has('message'))
        <flux:toast>{{ session('message') }}</flux:toast>
    @endif

    @include('livewire.base-index')
</div>
