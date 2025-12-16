<div>
    @if($hasPendingSignature)
        <!-- Signature Required Alert -->
        <flux:card class="mb-6 border-yellow-500">
            <div class="flex items-center gap-4">
                <i class="fas fa-signature fa-2x text-yellow-600"></i>
                <div class="flex-1">
                    <flux:heading size="lg">Signature Required</flux:heading>
                    <p class="text-gray-600 dark:text-gray-400">This contract requires your signature to be completed.</p>
                </div>
                <flux:modal.trigger name="signature-modal">
                    <flux:button variant="primary" icon="pencil">
                        Sign Now
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </flux:card>

        <!-- Signature Modal -->
        <flux:modal name="signature-modal" class="max-w-2xl">
            <form wire:submit="submitSignature">
                <div class="p-6 space-y-6">
                    <flux:heading size="lg">Sign Contract</flux:heading>
                    
                    <!-- Signature Method Selection -->
                    <div>
                        <flux:label>Signature Method</flux:label>
                        <flux:radio.group wire:model.live="signatureMethod">
                            <flux:radio value="draw" label="Draw Signature" />
                            <flux:radio value="type" label="Type Name" />
                        </flux:radio.group>
                    </div>

                    <!-- Draw Signature -->
                    @if($signatureMethod === 'draw')
                        <div>
                            <flux:label>Draw your signature below</flux:label>
                            <div class="mt-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                <canvas 
                                    x-data="signaturePad"
                                    x-ref="canvas"
                                    @mousedown="startDrawing"
                                    @mousemove="draw"
                                    @mouseup="stopDrawing"
                                    @mouseleave="stopDrawing"
                                    @touchstart.prevent="startDrawing"
                                    @touchmove.prevent="draw"
                                    @touchend.prevent="stopDrawing"
                                    width="600" 
                                    height="200"
                                    class="w-full cursor-crosshair"
                                ></canvas>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <flux:button 
                                    type="button" 
                                    variant="ghost" 
                                    size="sm"
                                    x-data
                                    @click="$wire.signatureData = ''; $dispatch('clear-signature')"
                                >
                                    Clear Signature
                                </flux:button>
                            </div>
                        </div>
                    @endif

                    <!-- Type Signature -->
                    @if($signatureMethod === 'type')
                        <div>
                            <flux:label>Type your full name</flux:label>
                            <flux:input 
                                wire:model.live="typedName"
                                type="text" 
                                placeholder="John Doe"
                            />
                            @if($typedName)
                                <div class="mt-4 p-4 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                                    <p class="text-4xl text-center" style="font-family: 'Brush Script MT', cursive;">{{ $typedName }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Terms Acceptance -->
                    <div>
                        <flux:checkbox wire:model.live="termsAccepted" label="I have read and agree to the terms and conditions of this contract. By signing, I acknowledge that this electronic signature is legally binding." />
                    </div>

                    <!-- Error Message -->
                    @if($errorMessage)
                        <flux:callout variant="danger" icon="exclamation-circle" :heading="$errorMessage" />
                    @endif

                    <!-- Success Message -->
                    @if($successMessage)
                        <flux:callout variant="success" icon="check-circle" :heading="$successMessage" />
                    @endif
                </div>

                <div class="flex gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                    <flux:modal.trigger name="signature-modal">
                        <flux:button type="button" variant="ghost">
                            Cancel
                        </flux:button>
                    </flux:modal.trigger>
                    <flux:button 
                        type="submit" 
                        variant="primary"
                        :disabled="!$termsAccepted || $processing"
                    >
                        <span wire:loading.remove wire:target="submitSignature">
                            Sign Contract
                        </span>
                        <span wire:loading wire:target="submitSignature">
                            <flux:icon.loading />
                            Signing...
                        </span>
                    </flux:button>
                </div>
            </form>
        </flux:modal>

        @script
        <script>
            Alpine.data('signaturePad', () => ({
                canvas: null,
                ctx: null,
                isDrawing: false,

                init() {
                    this.canvas = this.$refs.canvas;
                    this.ctx = this.canvas.getContext('2d');
                    this.ctx.strokeStyle = '#000000';
                    this.ctx.lineWidth = 2;
                    this.ctx.lineCap = 'round';
                    this.ctx.lineJoin = 'round';

                    this.$watch('$wire.signatureData', (value) => {
                        if (!value) {
                            this.clearCanvas();
                        }
                    });

                    window.addEventListener('clear-signature', () => {
                        this.clearCanvas();
                    });
                },

                getMousePos(e) {
                    const rect = this.canvas.getBoundingClientRect();
                    const scaleX = this.canvas.width / rect.width;
                    const scaleY = this.canvas.height / rect.height;
                    
                    return {
                        x: (e.clientX - rect.left) * scaleX,
                        y: (e.clientY - rect.top) * scaleY
                    };
                },

                getTouchPos(e) {
                    const rect = this.canvas.getBoundingClientRect();
                    const scaleX = this.canvas.width / rect.width;
                    const scaleY = this.canvas.height / rect.height;
                    const touch = e.touches[0];
                    
                    return {
                        x: (touch.clientX - rect.left) * scaleX,
                        y: (touch.clientY - rect.top) * scaleY
                    };
                },

                startDrawing(e) {
                    this.isDrawing = true;
                    const pos = e.touches ? this.getTouchPos(e) : this.getMousePos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(pos.x, pos.y);
                },

                draw(e) {
                    if (!this.isDrawing) return;
                    const pos = e.touches ? this.getTouchPos(e) : this.getMousePos(e);
                    this.ctx.lineTo(pos.x, pos.y);
                    this.ctx.stroke();
                    
                    // Update Livewire with canvas data
                    this.$wire.signatureData = this.canvas.toDataURL('image/png');
                },

                stopDrawing() {
                    if (this.isDrawing) {
                        this.isDrawing = false;
                        // Save final state to Livewire
                        this.$wire.signatureData = this.canvas.toDataURL('image/png');
                    }
                },

                clearCanvas() {
                    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                }
            }));
        </script>
        @endscript
    @endif
</div>
