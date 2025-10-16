@props([
    'selectedClient' => null,
    'contacts' => [],
    'loadingContacts' => false
])

<x-forms.section 
    title="Ticket Information" 
    description="Basic details about the support ticket"
    :icon="'<svg class=\'w-5 h-5 text-blue-600\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\'></path></svg>'">
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Client Selection -->
        <div>
            <x-forms.client-search-field 
                name="client_id" 
                :required="true"
                :selected="$selectedClient"
                label="Client"
                placeholder="Search for client..." />
        </div>
        
        <!-- Contact Selection -->
        <div x-data="{ contactFieldComponent: null }" x-init="contactFieldComponent = $refs.contactField">
            <div x-ref="contactField">
                <x-forms.contact-search-field 
                    name="contact_id" 
                    label="Contact"
                    placeholder="Search for contact..."
                    :clientId="$selectedClient?->id"
                    @contact-selected="contactId = $event.detail.contact.id"
                    @contact-cleared="contactId = ''" />
            </div>
        </div>
    </div>
    
    <!-- Subject and Priority -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-forms.input 
            name="subject" 
            label="Subject" 
            :required="true"
            placeholder="Brief description of the issue"
            x-model="subject" />
            
        <x-forms.select 
            name="priority" 
            label="Priority" 
            :required="true"
            x-model="priority">
            <option value="Low">🔵 Low Priority</option>
            <option value="Medium" selected>🟡 Medium Priority</option>
            <option value="High">🟠 High Priority</option>
            <option value="Critical">🔴 Critical Priority</option>
        </x-forms.select>
    </div>
    
    <!-- Assignment and Asset -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Keep the original select hidden for JavaScript fallback -->
        <select name="assigned_to_fallback" style="display: none;">
            @foreach(\App\Domains\Core\Models\User::where('company_id', auth()->user()->company_id)->where('status', 1)->orderBy('name')->get() as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
        
        <div x-data="{ userFieldComponent: null }" x-init="userFieldComponent = $refs.userField">
            <div x-ref="userField">
                <x-forms.user-search-field 
                    name="assigned_to" 
                    label="Assign To"
                    placeholder="Search for user or leave unassigned..."
                    @user-selected="assignedTo = $event.detail.user.id"
                    @user-cleared="assignedTo = ''" />
            </div>
        </div>
        
        <div x-data="{ assetFieldComponent: null }" x-init="assetFieldComponent = $refs.assetField">
            <div x-ref="assetField">
                <x-forms.asset-search-field 
                    name="asset_id" 
                    label="Related Asset"
                    placeholder="Search for asset..."
                    :clientId="$selectedClient?->id"
                    @asset-selected="assetId = $event.detail.asset.id"
                    @asset-cleared="assetId = ''" />
            </div>
        </div>
    </div>
    
    <!-- Details -->
    <x-forms.textarea 
        name="details" 
        label="Details" 
        :required="true"
        :rows="6"
        placeholder="Describe the issue in detail. Include steps to reproduce, error messages, and any relevant information."
        x-model="details" />
        
    <!-- Hidden Status Field (defaults to 'new') -->
    <input type="hidden" name="status" value="new" />
        
</x-forms.section>
