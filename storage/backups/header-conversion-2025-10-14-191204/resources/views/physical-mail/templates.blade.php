@extends('layouts.app')

@section('title', 'Physical Mail Templates')

@section('content')
<div class="container-fluid">
    <div class="mb-6">
        <flux:heading size="xl">Mail Templates</flux:heading>
        <flux:text class="text-zinc-500">
            Manage reusable templates for physical mail
            @if(isset($selectedClient) && $selectedClient)
                for {{ $selectedClient->name }}
            @endif
        </flux:text>
        
        <div class="mt-4">
            <flux:button onclick="createTemplate()" icon="plus">
                Create Template
            </button>
        </div>
    </div>

    @php
        $templates = \App\Domains\PhysicalMail\Models\PhysicalMailTemplate::orderBy('name')->get();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $template)
            <flux:card>
                <div>
                    <flux:heading size="lg">{{ $template->name }}</flux:heading>
                    <flux:badge variant="{{ $template->is_active ? 'green' : 'zinc' }}">
                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                    </flux:badge>
                </div>
                
                <div>
                    <flux:text size="sm" class="text-zinc-600 line-clamp-3">
                        {{ $template->description }}
                    </flux:text>
                    
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-zinc-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000 2H4a1 1 0 100 2h1a1 1 0 001-1v4a1 1 0 11-2 0V6a2 2 0 00-2-2z" clip-rule="evenodd" />
                            </svg>
                            <flux:text size="sm">Type: {{ ucfirst($template->type) }}</flux:text>
                        </div>
                        
                        @if($template->postgrid_template_id)
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 text-zinc-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                                </svg>
                                <flux:text size="sm">PostGrid ID: {{ Str::limit($template->postgrid_template_id, 10) }}</flux:text>
                            </div>
                        @endif
                        
                        <div class="flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-zinc-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            <flux:text size="sm">Created: {{ $template->created_at->format('M d, Y') }}</flux:text>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <flux:button size="sm" variant="filled" onclick="editTemplate('{{ $template->id }}')">
                        Edit
                    </button>
                    <flux:button size="sm" variant="ghost" onclick="previewTemplate('{{ $template->id }}')">
                        Preview
                    </button>
                    <flux:button size="sm" variant="ghost" onclick="duplicateTemplate('{{ $template->id }}')">
                        Duplicate
                    </button>
                </div>
            </flux:card>
        @endforeach
        
        @if($templates->isEmpty())
            <div class="col-span-full">
                <flux:card>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-zinc-300 mb-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd" />
                        </svg>
                        <flux:text size="lg" class="text-zinc-500 mb-2">No templates yet</flux:text>
                        <flux:text size="sm" class="text-zinc-400">Create your first template to get started</flux:text>
                    </div>
                </flux:card>
            </div>
        @endif
    </div>

    <!-- Pre-built Templates Section -->
    <div class="mt-12">
        <flux:heading size="lg" class="mb-4">Pre-built Templates</flux:heading>
        <flux:text class="text-zinc-500 mb-6">Start with one of our professionally designed templates</flux:text>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card>
                <div>
                    <flux:heading size="lg">Invoice Cover Letter</flux:heading>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-600">
                        Professional cover letter to accompany invoices with payment instructions and contact information.
                    </flux:text>
                </div>
                <div>
                    <flux:button size="sm" variant="filled" onclick="usePrebuiltTemplate('invoice-cover')">
                        Use Template
                    </button>
                </div>
            </flux:card>
            
            <flux:card>
                <div>
                    <flux:heading size="lg">Payment Reminder</flux:heading>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-600">
                        Friendly reminder for overdue payments with account details and payment options.
                    </flux:text>
                </div>
                <div>
                    <flux:button size="sm" variant="filled" onclick="usePrebuiltTemplate('payment-reminder')">
                        Use Template
                    </button>
                </div>
            </flux:card>
            
            <flux:card>
                <div>
                    <flux:heading size="lg">Service Update</flux:heading>
                </div>
                <div>
                    <flux:text size="sm" class="text-zinc-600">
                        Inform clients about service changes, maintenance schedules, or system updates.
                    </flux:text>
                </div>
                <div>
                    <flux:button size="sm" variant="filled" onclick="usePrebuiltTemplate('service-update')">
                        Use Template
                    </button>
                </div>
            </flux:card>
        </div>
    </div>
</div>

@push('scripts')
<script>
function createTemplate() {
    // Implement template creation modal
    alert('Template editor coming soon!');
}

function editTemplate(templateId) {
    alert('Edit template: ' + templateId);
}

function previewTemplate(templateId) {
    alert('Preview template: ' + templateId);
}

function duplicateTemplate(templateId) {
    if (confirm('Duplicate this template?')) {
        alert('Duplicating template: ' + templateId);
    }
}

function usePrebuiltTemplate(type) {
    alert('Creating template from: ' + type);
}
</script>
@endpush
@endsection