<div x-data="{ 
        url: @entangle('deploymentUrl'),
        async copyUrl(text) {
            if (!text) return;
            try {
                await navigator.clipboard.writeText(text);
                console.log('✓ Copied to clipboard:', text);
            } catch (err) {
                console.error('✗ Failed to copy:', err);
                alert('Failed to copy: ' + err.message);
            }
        }
    }" 
    x-effect="if (url) copyUrl(url)">
    <flux:button 
        variant="outline" 
        icon="{{ $loading ? 'arrow-path' : ($copied ? 'check' : ($error ? 'x-mark' : 'link')) }}"
        wire:click="getDeploymentLink"
        wire:loading.attr="disabled">
        @if($loading)
            Loading...
        @elseif($copied)
            Copied!
        @elseif($error)
            Error
        @else
            Copy Agent Download Link
        @endif
    </flux:button>
</div>
