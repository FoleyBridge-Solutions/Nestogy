<div>
    <flux:modal wire:model="show" name="send-physical-mail" class="max-w-4xl">
        <div class="space-y-2">
            <flux:heading size="lg">Send Physical Mail</flux:heading>
        </div>
        
        <div class="space-y-6">
            @if ($invoice)
                <flux:callout type="info">
                    Sending Invoice #{{ $invoice->invoice_number }} for ${{ number_format($invoice->total, 2) }}
                </flux:callout>
            @endif
            
            <flux:tabs>
                <flux:tabs>
                    <flux:tab name="recipient">Recipient</flux:tab>
                    <flux:tab name="content">Content</flux:tab>
                    <flux:tab name="options">Mail Options</flux:tab>
                </flux:tabs>
                
                <flux:tab.panel name="recipient" class="space-y-4">
                    <flux:fieldset>
                        <flux:legend>Recipient Information</flux:legend>
                        
                        <flux:field>
                            <flux:label for="recipient_name">Name *</flux:label>
                            <flux:input wire:model="recipientName" id="recipient_name" />
                            <flux:error name="recipientName" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label for="recipient_title">Title</flux:label>
                            <flux:input wire:model="recipientTitle" id="recipient_title" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label for="recipient_company">Company</flux:label>
                            <flux:input wire:model="recipientCompany" id="recipient_company" />
                            <flux:error name="recipientCompany" />
                        </flux:field>
                    </flux:fieldset>
                    
                    <flux:fieldset>
                        <flux:legend>Mailing Address</flux:legend>
                        
                        <flux:field>
                            <flux:label for="address_line1">Address Line 1 *</flux:label>
                            <flux:input wire:model="recipientAddressLine1" id="address_line1" />
                            <flux:error name="recipientAddressLine1" />
                        </flux:field>
                        
                        <flux:field>
                            <flux:label for="address_line2">Address Line 2</flux:label>
                            <flux:input wire:model="recipientAddressLine2" id="address_line2" />
                        </flux:field>
                        
                        <div class="grid grid-cols-3 gap-4">
                            <flux:field>
                                <flux:label for="city">City *</flux:label>
                                <flux:input wire:model="recipientCity" id="city" />
                                <flux:error name="recipientCity" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label for="state">State *</flux:label>
                                <flux:input wire:model="recipientState" id="state" maxlength="2" />
                                <flux:error name="recipientState" />
                            </flux:field>
                            
                            <flux:field>
                                <flux:label for="postal_code">ZIP Code *</flux:label>
                                <flux:input wire:model="recipientPostalCode" id="postal_code" />
                                <flux:error name="recipientPostalCode" />
                            </flux:field>
                        </div>
                    </flux:fieldset>
                </flux:tab.panel>
                
                <flux:tab.panel name="content" class="space-y-4">
                    @if ($invoice)
                        <flux:fieldset>
                            <flux:legend>Invoice Cover Letter</flux:legend>
                            
                            <flux:checkbox wire:model="includeCoverLetter">
                                Include cover letter with invoice
                            </flux:checkbox>
                            
                            @if ($includeCoverLetter)
                                <flux:field>
                                    <flux:label for="cover_letter">Cover Letter Message</flux:label>
                                    <flux:textarea 
                                        wire:model="coverLetterMessage" 
                                        id="cover_letter" 
                                        rows="4"
                                    />
                                    <flux:description>
                                        This message will appear on the first page before the invoice.
                                    </flux:description>
                                </flux:field>
                            @endif
                        </flux:fieldset>
                    @else
                        <flux:fieldset>
                            <flux:legend>Letter Content</flux:legend>
                            
                            <flux:checkbox wire:model.live="useTemplate">
                                Use template
                            </flux:checkbox>
                            
                            @if ($useTemplate)
                                <flux:field>
                                    <flux:label for="template">Select Template</flux:label>
                                    <flux:select wire:model="templateId" id="template">
                                        <flux:select.option value="">Choose a template...</flux:select.option>
                                        @foreach ($templates as $template)
                                            <flux:select.option value="{{ $template->id }}">
                                                {{ $template->name }}
                                                @if ($template->description)
                                                    - {{ $template->description }}
                                                @endif
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                            @else
                                <flux:field>
                                    <flux:label for="custom_content">Custom Content</flux:label>
                                    <flux:textarea 
                                        wire:model="customContent" 
                                        id="custom_content" 
                                        rows="8"
                                        placeholder="Enter your letter content here..."
                                    />
                                    <flux:description>
                                        Enter plain text or HTML content. The system will automatically format it for printing.
                                    </flux:description>
                                </flux:field>
                            @endif
                        </flux:fieldset>
                    @endif
                </flux:tab.panel>
                
                <flux:tab.panel name="options" class="space-y-4">
                    <flux:fieldset>
                        <flux:legend>Delivery Options</flux:legend>
                        
                        <flux:checkbox wire:model="expressDelivery">
                            Express Delivery (USPS First Class)
                        </flux:checkbox>
                        
                        <flux:checkbox wire:model="certifiedMail">
                            Certified Mail (requires signature)
                        </flux:checkbox>
                        
                        <flux:checkbox wire:model="returnReceipt">
                            Return Receipt (proof of delivery)
                        </flux:checkbox>
                    </flux:fieldset>
                    
                    <flux:fieldset>
                        <flux:legend>Printing Options</flux:legend>
                        
                        <flux:checkbox wire:model="colorPrinting">
                            Color Printing
                        </flux:checkbox>
                        
                        <flux:checkbox wire:model="doubleSided">
                            Double-Sided Printing
                        </flux:checkbox>
                    </flux:fieldset>
                    
                    <flux:card>
                        <div>
                            <flux:heading size="lg">Estimated Cost</flux:heading>
                        </div>
                        <div>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span>Base Letter (1-2 pages)</span>
                                    <span>$1.95</span>
                                </div>
                                @if ($expressDelivery)
                                    <div class="flex justify-between">
                                        <span>Express Delivery</span>
                                        <span>+$1.50</span>
                                    </div>
                                @endif
                                @if ($certifiedMail)
                                    <div class="flex justify-between">
                                        <span>Certified Mail</span>
                                        <span>+$4.95</span>
                                    </div>
                                @endif
                                @if ($returnReceipt)
                                    <div class="flex justify-between">
                                        <span>Return Receipt</span>
                                        <span>+$2.95</span>
                                    </div>
                                @endif
                                @if ($colorPrinting)
                                    <div class="flex justify-between">
                                        <span>Color Printing</span>
                                        <span>+$0.50</span>
                                    </div>
                                @endif
                                <div class="flex justify-between font-semibold pt-2 border-t">
                                    <span>Estimated Total</span>
                                    <span>${{ number_format($this->calculateEstimatedCost(), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:card>
                </flux:tab.panel>
            </flux:tabs>
        </div>
        
        <div class="flex gap-2 pt-4">
            <flux:button variant="ghost" wire:click="closeModal">
                Cancel
            </flux:button>
            <flux:button variant="primary" wire:click="sendMail">
                <flux:icon name="paper-airplane" class="mr-2" />
                Send Physical Mail
            </flux:button>
        </div>
    </flux:modal>
</div>