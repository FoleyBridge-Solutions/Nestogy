@extends('layouts.app')

@section('title', 'Send Physical Mail')

@section('content')
<div class="container-fluid">
    <div class="mb-6">
        <flux:heading size="xl">Send Physical Mail</flux:heading>
        <flux:text class="text-zinc-500">Create and send physical letters to your clients</flux:text>
    </div>

    <form id="sendMailForm">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <!-- Mail Type -->
                <flux:card class="mb-6">
                    <flux:card.header>
                        <flux:card.title>Mail Type</flux:card.title>
                    </flux:card.header>
                    <flux:card.body>
                        <flux:radio.group name="type" value="letter">
                            <div class="grid grid-cols-2 gap-4">
                                <flux:radio value="letter">
                                    <flux:label>Letter</flux:label>
                                    <flux:description>Standard business letter (8.5" x 11")</flux:description>
                                </flux:radio>
                                <flux:radio value="postcard">
                                    <flux:label>Postcard</flux:label>
                                    <flux:description>Marketing postcard (6" x 4.25")</flux:description>
                                </flux:radio>
                                <flux:radio value="cheque">
                                    <flux:label>Check</flux:label>
                                    <flux:description>Business check with stub</flux:description>
                                </flux:radio>
                                <flux:radio value="self_mailer">
                                    <flux:label>Self Mailer</flux:label>
                                    <flux:description>Folded mailer, no envelope</flux:description>
                                </flux:radio>
                            </div>
                        </flux:radio.group>
                    </flux:card.body>
                </flux:card>

                <!-- Recipient -->
                <flux:card class="mb-6">
                    <flux:card.header>
                        <flux:card.title>Recipient</flux:card.title>
                    </flux:card.header>
                    <flux:card.body class="space-y-4">
                        <!-- Client Selection -->
                        <flux:field>
                            <flux:label>Select Client</flux:label>
                            <flux:select name="client_id" onchange="loadClientAddress(this.value)">
                                <flux:option value="">Choose a client...</flux:option>
                                @foreach(\App\Models\Client::orderBy('name')->get() as $client)
                                    <flux:option value="{{ $client->id }}">{{ $client->name }}</flux:option>
                                @endforeach
                            </flux:select>
                        </flux:field>

                        <!-- Manual Address -->
                        <div id="recipientAddress" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input name="to[firstName]" placeholder="First Name" />
                                <flux:input name="to[lastName]" placeholder="Last Name" />
                            </div>
                            
                            <flux:input name="to[companyName]" placeholder="Company Name" />
                            
                            <flux:input name="to[addressLine1]" placeholder="Address Line 1" required />
                            <flux:input name="to[addressLine2]" placeholder="Address Line 2" />
                            
                            <div class="grid grid-cols-3 gap-4">
                                <flux:input name="to[city]" placeholder="City" required />
                                <flux:input name="to[provinceOrState]" placeholder="State" maxlength="2" required />
                                <flux:input name="to[postalOrZip]" placeholder="ZIP Code" required />
                            </div>
                        </div>
                    </flux:card.body>
                </flux:card>

                <!-- Content -->
                <flux:card class="mb-6">
                    <flux:card.header>
                        <flux:card.title>Content</flux:card.title>
                    </flux:card.header>
                    <flux:card.body class="space-y-4">
                        <!-- Template or Custom -->
                        <flux:tabs>
                            <flux:tab name="template">Use Template</flux:tab>
                            <flux:tab name="custom">Custom Content</flux:tab>
                            <flux:tab name="pdf">Upload PDF</flux:tab>
                            
                            <flux:tab.panel name="template">
                                <flux:select name="template_id">
                                    <flux:option value="">Choose a template...</flux:option>
                                    @foreach(\App\Domains\PhysicalMail\Models\PhysicalMailTemplate::orderBy('name')->get() as $template)
                                        <flux:option value="{{ $template->id }}">{{ $template->name }}</flux:option>
                                    @endforeach
                                </flux:select>
                                
                                <!-- Merge Variables -->
                                <div id="mergeVariables" class="mt-4 hidden">
                                    <flux:text size="sm" class="text-zinc-500 mb-2">Template Variables</flux:text>
                                    <div class="space-y-2" id="mergeVariableFields">
                                        <!-- Dynamic fields will be added here -->
                                    </div>
                                </div>
                            </flux:tab.panel>
                            
                            <flux:tab.panel name="custom">
                                <flux:textarea 
                                    name="content" 
                                    rows="10"
                                    placeholder="Enter your letter content here..."
                                    style="font-family: monospace;">
                                </flux:textarea>
                                <flux:text size="sm" class="text-zinc-500 mt-2">
                                    Basic HTML formatting is supported.
                                </flux:text>
                            </flux:tab.panel>
                            
                            <flux:tab.panel name="pdf">
                                <flux:input 
                                    type="url" 
                                    name="pdf_url" 
                                    placeholder="https://example.com/document.pdf"
                                />
                                <flux:text size="sm" class="text-zinc-500 mt-2">
                                    Enter the URL of a PDF document to send.
                                </flux:text>
                            </flux:tab.panel>
                        </flux:tabs>
                    </flux:card.body>
                </flux:card>
            </div>

            <!-- Sidebar Options -->
            <div class="lg:col-span-1">
                <!-- Mailing Options -->
                <flux:card class="mb-6">
                    <flux:card.header>
                        <flux:card.title>Mailing Options</flux:card.title>
                    </flux:card.header>
                    <flux:card.body class="space-y-4">
                        <!-- Mailing Class -->
                        <flux:field>
                            <flux:label>Mailing Class</flux:label>
                            <flux:select name="mailing_class">
                                <flux:option value="first_class" selected>First Class</flux:option>
                                <flux:option value="standard_class">Standard Class</flux:option>
                            </flux:select>
                        </flux:field>

                        <!-- Extra Services -->
                        <flux:field>
                            <flux:label>Extra Services</flux:label>
                            <flux:select name="extra_service">
                                <flux:option value="">None</flux:option>
                                <flux:option value="certified">Certified Mail</flux:option>
                                <flux:option value="certified_return_receipt">Certified with Return Receipt</flux:option>
                                <flux:option value="registered">Registered Mail</flux:option>
                            </flux:select>
                        </flux:field>

                        <!-- Print Options -->
                        <flux:checkbox name="color" checked>
                            Color printing
                        </flux:checkbox>
                        
                        <flux:checkbox name="double_sided">
                            Double-sided printing
                        </flux:checkbox>

                        <!-- Send Date -->
                        <flux:field>
                            <flux:label>Send Date</flux:label>
                            <flux:input type="date" name="send_date" min="{{ now()->format('Y-m-d') }}" />
                            <flux:description>Leave blank to send immediately</flux:description>
                        </flux:field>
                    </flux:card.body>
                </flux:card>

                <!-- Cost Estimate -->
                <flux:card class="mb-6">
                    <flux:card.header>
                        <flux:card.title>Cost Estimate</flux:card.title>
                    </flux:card.header>
                    <flux:card.body>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <flux:text size="sm">Base Cost:</flux:text>
                                <flux:text size="sm" id="baseCost">$1.50</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text size="sm">Color Printing:</flux:text>
                                <flux:text size="sm" id="colorCost">$0.25</flux:text>
                            </div>
                            <div class="flex justify-between">
                                <flux:text size="sm">Extra Services:</flux:text>
                                <flux:text size="sm" id="extraCost">$0.00</flux:text>
                            </div>
                            <flux:separator />
                            <div class="flex justify-between">
                                <flux:text weight="medium">Total:</flux:text>
                                <flux:text weight="medium" id="totalCost">$1.75</flux:text>
                            </div>
                        </div>
                        <flux:text size="xs" class="text-zinc-400 mt-4">
                            * Estimated cost. Actual cost may vary based on PostGrid pricing.
                        </flux:text>
                    </flux:card.body>
                </flux:card>

                <!-- Actions -->
                <div class="space-y-2">
                    <flux:button type="button" onclick="sendMail()" class="w-full">
                        Send Mail
                    </flux:button>
                    <flux:button variant="secondary" onclick="previewMail()" class="w-full">
                        Preview
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('mail.index') }}" class="w-full">
                        Cancel
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function loadClientAddress(clientId) {
    if (!clientId) return;
    
    fetch(`/api/clients/${clientId}`)
        .then(response => response.json())
        .then(client => {
            document.querySelector('[name="to[companyName]"]').value = client.name || '';
            document.querySelector('[name="to[addressLine1]"]').value = client.address || '';
            document.querySelector('[name="to[city]"]').value = client.city || '';
            document.querySelector('[name="to[provinceOrState]"]').value = client.state || '';
            document.querySelector('[name="to[postalOrZip]"]').value = client.zip || '';
        });
}

function updateCostEstimate() {
    let total = 1.50; // Base cost
    
    if (document.querySelector('[name="color"]').checked) {
        total += 0.25;
    }
    
    const extraService = document.querySelector('[name="extra_service"]').value;
    if (extraService === 'certified') {
        total += 3.85;
        document.getElementById('extraCost').textContent = '$3.85';
    } else if (extraService === 'certified_return_receipt') {
        total += 6.85;
        document.getElementById('extraCost').textContent = '$6.85';
    } else if (extraService === 'registered') {
        total += 12.00;
        document.getElementById('extraCost').textContent = '$12.00';
    } else {
        document.getElementById('extraCost').textContent = '$0.00';
    }
    
    document.getElementById('totalCost').textContent = '$' + total.toFixed(2);
}

// Update cost when options change
document.querySelector('[name="color"]').addEventListener('change', updateCostEstimate);
document.querySelector('[name="extra_service"]').addEventListener('change', updateCostEstimate);

function sendMail() {
    const form = document.getElementById('sendMailForm');
    const formData = new FormData(form);
    
    // Convert to JSON
    const data = {
        type: formData.get('type'),
        to: {
            firstName: formData.get('to[firstName]'),
            lastName: formData.get('to[lastName]'),
            companyName: formData.get('to[companyName]'),
            addressLine1: formData.get('to[addressLine1]'),
            addressLine2: formData.get('to[addressLine2]'),
            city: formData.get('to[city]'),
            provinceOrState: formData.get('to[provinceOrState]'),
            postalOrZip: formData.get('to[postalOrZip]'),
            country: 'US'
        },
        color: formData.get('color') === 'on',
        double_sided: formData.get('double_sided') === 'on',
        mailing_class: formData.get('mailing_class'),
        extra_service: formData.get('extra_service'),
        send_date: formData.get('send_date')
    };
    
    // Add content based on selected tab
    if (formData.get('template_id')) {
        data.template = formData.get('template_id');
    } else if (formData.get('pdf_url')) {
        data.pdf = formData.get('pdf_url');
    } else if (formData.get('content')) {
        data.content = formData.get('content');
    }
    
    // Send request
    fetch('/api/physical-mail/send', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Mail sent successfully!');
            window.location.href = '{{ route("mail.index") }}';
        } else {
            alert('Error: ' + (result.error || 'Failed to send mail'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send mail. Please try again.');
    });
}

function previewMail() {
    alert('Preview functionality coming soon!');
}
</script>
@endpush
@endsection