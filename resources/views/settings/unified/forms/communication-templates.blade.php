<div class="space-y-8">
    <flux:card>
        <flux:heading size="lg">Email Templates</flux:heading>
        <flux:text variant="muted" class="mb-6">Customize email templates for your communications</flux:text>
        
        <div class="space-y-4">
            <flux:field>
                <flux:label>Use Custom Templates</flux:label>
                <flux:switch name="use_custom_templates" :checked="$settings['use_custom_templates'] ?? false" />
                <flux:text size="sm" variant="muted">Override default templates with your own custom HTML templates</flux:text>
            </flux:field>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Financial Templates</flux:heading>
        <flux:text variant="muted" class="mb-6">Templates for invoices, quotes, and receipts</flux:text>
        
        <div class="space-y-3">
            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex-1">
                    <div class="font-medium">Invoice Email Template</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ !empty($settings['invoice_template']) ? 'Custom template configured' : 'Using default template' }}
                    </div>
                </div>
                <flux:button 
                    type="button" 
                    variant="ghost" 
                    size="sm"
                    onclick="openTemplateEditor('invoice_template', 'Invoice Email Template', {{ json_encode($settings['invoice_template'] ?? '') }})">
                    Edit Template
                </flux:button>
            </div>

            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex-1">
                    <div class="font-medium">Quote Email Template</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ !empty($settings['quote_template']) ? 'Custom template configured' : 'Using default template' }}
                    </div>
                </div>
                <flux:button 
                    type="button" 
                    variant="ghost" 
                    size="sm"
                    onclick="openTemplateEditor('quote_template', 'Quote Email Template', {{ json_encode($settings['quote_template'] ?? '') }})">
                    Edit Template
                </flux:button>
            </div>

            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex-1">
                    <div class="font-medium">Receipt Email Template</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ !empty($settings['receipt_template']) ? 'Custom template configured' : 'Using default template' }}
                    </div>
                </div>
                <flux:button 
                    type="button" 
                    variant="ghost" 
                    size="sm"
                    onclick="openTemplateEditor('receipt_template', 'Receipt Email Template', {{ json_encode($settings['receipt_template'] ?? '') }})">
                    Edit Template
                </flux:button>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Authentication Templates</flux:heading>
        <flux:text variant="muted" class="mb-6">Templates for user account emails</flux:text>
        
        <div class="space-y-3">
            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex-1">
                    <div class="font-medium">Welcome Email Template</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ !empty($settings['welcome_email_template']) ? 'Custom template configured' : 'Using default template' }}
                    </div>
                </div>
                <flux:button 
                    type="button" 
                    variant="ghost" 
                    size="sm"
                    onclick="openTemplateEditor('welcome_email_template', 'Welcome Email Template', {{ json_encode($settings['welcome_email_template'] ?? '') }})">
                    Edit Template
                </flux:button>
            </div>

            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex-1">
                    <div class="font-medium">Password Reset Template</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ !empty($settings['password_reset_template']) ? 'Custom template configured' : 'Using default template' }}
                    </div>
                </div>
                <flux:button 
                    type="button" 
                    variant="ghost" 
                    size="sm"
                    onclick="openTemplateEditor('password_reset_template', 'Password Reset Template', {{ json_encode($settings['password_reset_template'] ?? '') }})">
                    Edit Template
                </flux:button>
            </div>
        </div>
    </flux:card>

    <flux:card>
        <flux:heading size="lg">Notification Templates</flux:heading>
        <flux:text variant="muted" class="mb-6">Templates for system notifications</flux:text>
        
        <div class="space-y-3">
            <div class="flex items-center justify-between p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                <div class="flex-1">
                    <div class="font-medium">General Notification Template</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ !empty($settings['notification_template']) ? 'Custom template configured' : 'Using default template' }}
                    </div>
                </div>
                <flux:button 
                    type="button" 
                    variant="ghost" 
                    size="sm"
                    onclick="openTemplateEditor('notification_template', 'Notification Template', {{ json_encode($settings['notification_template'] ?? '') }})">
                    Edit Template
                </flux:button>
            </div>
        </div>
    </flux:card>

    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <flux:heading size="sm" class="text-blue-800 dark:text-blue-200">Template Variables</flux:heading>
        <flux:text size="sm" class="text-blue-700 dark:text-blue-300 mt-2">
            Click "Edit Template" to customize any template. Use {variable_name} for dynamic content. Leave empty to use defaults.
        </flux:text>
    </div>

    <!-- Hidden inputs to store template data -->
    <input type="hidden" name="invoice_template" id="invoice_template" value="{{ $settings['invoice_template'] ?? '' }}">
    <input type="hidden" name="quote_template" id="quote_template" value="{{ $settings['quote_template'] ?? '' }}">
    <input type="hidden" name="receipt_template" id="receipt_template" value="{{ $settings['receipt_template'] ?? '' }}">
    <input type="hidden" name="welcome_email_template" id="welcome_email_template" value="{{ $settings['welcome_email_template'] ?? '' }}">
    <input type="hidden" name="password_reset_template" id="password_reset_template" value="{{ $settings['password_reset_template'] ?? '' }}">
    <input type="hidden" name="notification_template" id="notification_template" value="{{ $settings['notification_template'] ?? '' }}">
</div>

<!-- Template Editor Modal -->
<flux:modal name="template-editor" class="max-w-4xl">
    <div class="p-6">
        <flux:heading size="lg" id="modal-title">Edit Template</flux:heading>
        <flux:text variant="muted" class="mt-2 mb-6" id="modal-variables">Available variables will appear here</flux:text>
        
        <div class="mb-4">
            <flux:label for="modal-editor">Template HTML</flux:label>
            <textarea 
                id="modal-editor" 
                rows="15" 
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 font-mono text-sm"
                placeholder="Enter your HTML template here..."></textarea>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" onclick="Flux.modal('template-editor').close()">
                Cancel
            </flux:button>
            <div class="flex gap-2">
                <flux:button type="button" variant="ghost" onclick="clearTemplate()">
                    Clear (Use Default)
                </flux:button>
                <flux:button type="button" variant="primary" onclick="saveTemplate()">
                    Save Template
                </flux:button>
            </div>
        </div>
    </div>
</flux:modal>

@push('scripts')
<script>
let currentTemplateField = null;

const templateVariables = {
    'invoice_template': '{company_name}, {invoice_number}, {amount}, {due_date}, {customer_name}',
    'quote_template': '{company_name}, {quote_number}, {amount}, {valid_until}, {customer_name}',
    'receipt_template': '{company_name}, {amount}, {payment_date}, {payment_method}, {customer_name}',
    'welcome_email_template': '{user_name}, {company_name}, {login_url}',
    'password_reset_template': '{user_name}, {reset_link}, {expires_in}',
    'notification_template': '{notification_title}, {notification_body}, {action_url}'
};

const templateTitles = {
    'invoice_template': 'Invoice Email Template',
    'quote_template': 'Quote Email Template',
    'receipt_template': 'Receipt Email Template',
    'welcome_email_template': 'Welcome Email Template',
    'password_reset_template': 'Password Reset Template',
    'notification_template': 'General Notification Template'
};

function openTemplateEditor(fieldName, title, currentValue) {
    currentTemplateField = fieldName;
    
    document.getElementById('modal-title').textContent = templateTitles[fieldName] || title;
    document.getElementById('modal-variables').textContent = 'Available variables: ' + (templateVariables[fieldName] || '');
    document.getElementById('modal-editor').value = currentValue || '';
    
    Flux.modal('template-editor').show();
}

function saveTemplate() {
    if (!currentTemplateField) return;
    
    const content = document.getElementById('modal-editor').value;
    document.getElementById(currentTemplateField).value = content;
    
    // Update the status text
    const statusElement = event.target.closest('.space-y-3')?.querySelector('.text-sm.text-zinc-600');
    if (statusElement) {
        statusElement.textContent = content ? 'Custom template configured' : 'Using default template';
    }
    
    Flux.modal('template-editor').close();
}

function clearTemplate() {
    document.getElementById('modal-editor').value = '';
}
</script>
@endpush
