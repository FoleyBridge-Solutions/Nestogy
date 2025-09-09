<div x-data="customItemModal()" @open-custom-item-modal.window="openModal($event.detail)">
    <!-- Modal Backdrop -->
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 z-40"
         @click="closeModal()"
         style="display: none;">
    </div>

    <!-- Modal Content -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div @click.stop class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold leading-6 text-gray-900">
                                <span x-text="editingIndex !== null ? 'Edit' : 'Add'"></span> Custom Item
                            </h3>
                            <p class="mt-2 text-sm text-gray-500">Enter the details for your custom item</p>
                        </div>
                        
                        <!-- Item Name -->
                        <div>
                            <label for="item-name" class="block text-sm font-medium text-gray-700">Item Name</label>
                            <input type="text" 
                                   id="item-name"
                                   x-model="itemName"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                   placeholder="Enter item name"
                                   required>
                        </div>
                        
                        <!-- Description -->
                        <div>
                            <label for="item-description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="item-description"
                                      x-model="itemDescription"
                                      rows="2"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                      placeholder="Enter description (optional)"></textarea>
                        </div>
                        
                        <!-- Quantity and Unit Price -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="item-quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                                <input type="number"
                                       id="item-quantity"
                                       x-model.number="itemQuantity"
                                       min="0.01"
                                       step="0.01"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="1">
                            </div>
                            
                            <div>
                                <label for="item-price" class="block text-sm font-medium text-gray-700">Unit Price</label>
                                <input type="number"
                                       id="item-price"
                                       x-model.number="itemUnitPrice"
                                       min="0"
                                       step="0.01"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- Tax Rate -->
                        <div>
                            <label for="item-tax" class="block text-sm font-medium text-gray-700">Tax Rate (%)</label>
                            <input type="number"
                                   id="item-tax"
                                   x-model.number="itemTaxRate"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                   placeholder="0">
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button"
                            @click="submitForm()"
                            class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
                        <span x-text="editingIndex !== null ? 'Update' : 'Add'"></span> Item
                    </button>
                    <button type="button"
                            @click="closeModal()"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function customItemModal() {
    return {
        showModal: false,
        editingIndex: null,
        itemName: '',
        itemDescription: '',
        itemQuantity: 1,
        itemUnitPrice: 0,
        itemTaxRate: 0,
        
        openModal(detail) {
            this.editingIndex = detail?.editingIndex || null;
            
            if (detail?.itemData) {
                this.itemName = detail.itemData.name || '';
                this.itemDescription = detail.itemData.description || '';
                this.itemQuantity = detail.itemData.quantity || 1;
                this.itemUnitPrice = detail.itemData.unit_price || 0;
                this.itemTaxRate = detail.itemData.tax_rate || 0;
            } else {
                this.resetForm();
            }
            
            this.showModal = true;
        },
        
        closeModal() {
            this.showModal = false;
            this.resetForm();
        },
        
        resetForm() {
            this.editingIndex = null;
            this.itemName = '';
            this.itemDescription = '';
            this.itemQuantity = 1;
            this.itemUnitPrice = 0;
            this.itemTaxRate = 0;
        },
        
        submitForm() {
            // Validate
            if (!this.itemName) {
                alert('Please enter an item name');
                return;
            }
            
            const itemData = {
                product_id: null,
                name: this.itemName,
                description: this.itemDescription,
                quantity: parseFloat(this.itemQuantity),
                unit_price: parseFloat(this.itemUnitPrice),
                tax_rate: parseFloat(this.itemTaxRate)
            };
            
            // Dispatch event to parent Livewire component
            if (this.editingIndex !== null) {
                @this.call('updateItem', this.editingIndex, itemData);
            } else {
                @this.call('addItem', itemData);
            }
            
            this.closeModal();
        }
    }
}
</script>