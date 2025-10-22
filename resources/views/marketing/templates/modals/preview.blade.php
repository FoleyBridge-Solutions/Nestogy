@props(['template'])

<div class="p-6">
    <flux:heading size="lg" class="mb-6">Template Preview</flux:heading>

    <div class="space-y-6">
        <div>
            <flux:text variant="strong" class="mb-2">Template Name</flux:text>
            <flux:text>{{ $template->name }}</flux:text>
        </div>

        <div>
            <flux:text variant="strong" class="mb-2">Subject Line</flux:text>
            <flux:text>{{ $template->subject }}</flux:text>
        </div>

        <div>
            <flux:text variant="strong" class="mb-2">Category</flux:text>
            <flux:badge size="sm" color="purple">
                {{ \App\Domains\Marketing\Models\EmailTemplate::getCategories()[$template->category] ?? $template->category }}
            </flux:badge>
        </div>

        <flux:separator />

        <div>
            <flux:text variant="strong" class="mb-3">HTML Preview</flux:text>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800 max-h-96 overflow-y-auto">
                {!! $template->body_html !!}
            </div>
        </div>

        @if($template->body_text)
        <div>
            <flux:text variant="strong" class="mb-3">Plain Text Version</flux:text>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900 max-h-48 overflow-y-auto">
                <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $template->body_text }}</pre>
            </div>
        </div>
        @endif

        <div class="text-xs text-gray-500">
            Created by {{ $template->creator->name ?? 'Unknown' }} on {{ $template->created_at->format('M d, Y') }}
        </div>
    </div>

    <div class="flex gap-3 justify-end mt-6">
        <flux:button variant="ghost" wire:click="closeCellModal">Close</flux:button>
        <flux:button variant="primary" href="{{ route('marketing.templates.edit', $template) }}">
            Edit Template
        </flux:button>
    </div>
</div>
