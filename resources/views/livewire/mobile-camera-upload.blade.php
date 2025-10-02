<div>
    <flux:card>
        <flux:heading size="lg" class="mb-4">
            <i class="fas fa-camera mr-2"></i>
            Upload Photos
        </flux:heading>

        <div class="space-y-4">
            <div class="text-center">
                <label for="camera-input" class="block">
                    <div class="w-full p-8 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition">
                        <i class="fas fa-camera text-4xl text-gray-400 dark:text-gray-500 mb-3"></i>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Tap to take a photo or select from gallery
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                            Images will be automatically compressed
                        </p>
                    </div>
                    <input 
                        type="file" 
                        id="camera-input" 
                        wire:model="photo" 
                        accept="image/*" 
                        capture="environment"
                        class="hidden">
                </label>

                @error('photo')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div wire:loading wire:target="photo" class="mt-4">
                    <div class="flex items-center justify-center gap-2 text-blue-600">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Compressing and uploading...</span>
                    </div>
                </div>
            </div>

            @if(count($photos) > 0)
                <div class="mt-6">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Uploaded Photos ({{ count($photos) }})
                    </p>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach($photos as $index => $photo)
                            <div class="relative group">
                                <img 
                                    src="{{ $photo['url'] }}" 
                                    alt="Uploaded photo" 
                                    class="w-full h-40 object-cover rounded-lg">
                                
                                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-lg">
                                    <button 
                                        wire:click="removePhoto({{ $index }})"
                                        wire:confirm="Remove this photo?"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                        <i class="fas fa-trash mr-2"></i>
                                        Remove
                                    </button>
                                </div>

                                <div class="absolute bottom-2 left-2 right-2 bg-black bg-opacity-70 text-white text-xs p-2 rounded">
                                    <p>{{ number_format($photo['size'] / 1024, 0) }} KB</p>
                                    <p>{{ \Carbon\Carbon::parse($photo['uploaded_at'])->format('g:i A') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </flux:card>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('photo-uploaded', (event) => {
                Flux.toast({
                    text: 'Photo uploaded successfully',
                    variant: 'success',
                    duration: 2000
                });
            });

            Livewire.on('photo-removed', () => {
                Flux.toast({
                    text: 'Photo removed',
                    variant: 'success',
                    duration: 2000
                });
            });

            Livewire.on('success', (event) => {
                Flux.toast({
                    text: event.message,
                    variant: 'success',
                    duration: 3000
                });
            });

            Livewire.on('error', (event) => {
                Flux.toast({
                    text: event.message,
                    variant: 'danger',
                    duration: 3000
                });
            });
        });
    </script>
</div>
