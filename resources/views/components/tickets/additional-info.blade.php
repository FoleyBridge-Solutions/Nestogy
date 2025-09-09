@props([])

<x-forms.section 
    title="Additional Information" 
    description="Vendor and billing details"
    :icon="'<svg class=\'w-5 h-5 text-green-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4\'></path></svg>'">
    
    <div class="space-y-6">
        <!-- Vendor -->
        <x-forms.select 
            name="vendor_id" 
            label="Vendor"
            placeholder="Select Vendor"
            x-model="vendorId">
            @foreach(\App\Models\Vendor::where('company_id', auth()->user()->company_id)->orderBy('name')->get() as $vendor)
                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
            @endforeach
        </x-forms.select>
        
        <!-- Vendor Ticket Number -->
        <x-forms.input 
            name="vendor_ticket_number" 
            label="Vendor Ticket Number"
            placeholder="External ticket reference"
            x-model="vendorTicketNumber" />
        
        <!-- Billable -->
        <x-forms.select 
            name="billable" 
            label="Billable"
            x-model="billable">
            <option value="0" selected>💚 Non-Billable</option>
            <option value="1">💰 Billable</option>
        </x-forms.select>
    </div>
    
</x-forms.section>
