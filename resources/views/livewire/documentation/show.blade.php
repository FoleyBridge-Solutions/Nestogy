<div>
    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-zinc-900 dark:text-white mb-4">
            {{ $this->pageTitle }}
        </h1>
        <p class="text-lg text-zinc-600 dark:text-zinc-400">
            {{ $this->pageDescription }}
        </p>
    </div>

    <flux:separator class="my-8" />

    {{-- Page Content --}}
    <div>
        @php
            $markdown = app(\App\Domains\Documentation\Services\DocumentationService::class)->getPageContent($page);
        @endphp
        
        @if($markdown)
            <div class="prose prose-zinc dark:prose-invert max-w-none prose-headings:font-bold prose-h1:text-3xl prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4 prose-a:text-blue-600 dark:prose-a:text-blue-400 prose-a:no-underline hover:prose-a:underline prose-code:bg-zinc-100 dark:prose-code:bg-zinc-800 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:before:content-none prose-code:after:content-none prose-blockquote:border-l-4 prose-blockquote:border-blue-500 prose-blockquote:bg-blue-50 dark:prose-blockquote:bg-blue-950/20 prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:rounded prose-blockquote:not-italic">
                {!! \Illuminate\Support\Str::markdown($markdown) !!}
            </div>
        @else
            {{-- Placeholder content --}}
            <flux:callout icon="exclamation-triangle" color="amber" variant="warning">
                <flux:callout.heading>Content Coming Soon</flux:callout.heading>
                <flux:callout.text>
                    This documentation page is currently being written. Check back soon for detailed information about {{ $this->pageTitle }}.
                </flux:callout.text>
            </flux:callout>
            
            <div class="mt-8 space-y-4">
                <flux:heading size="lg">Need Help Right Now?</flux:heading>
                <flux:text>While we're working on this documentation, you can:</flux:text>
                <div class="prose prose-zinc dark:prose-invert max-w-none">
                    <ul>
                        <li>Contact our support team at <a href="mailto:support@nestogy.com">support@nestogy.com</a></li>
                        <li>Check the <a href="{{ route('docs.show', 'faq') }}" wire:navigate>FAQ page</a> for common questions</li>
                        <li>Return to the <a href="{{ route('docs.index') }}" wire:navigate>documentation home</a></li>
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- Page Navigation --}}
    <div class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800">
        <div class="flex justify-between items-center">
            @if($this->previousPage)
                <a href="{{ route('docs.show', $this->previousPage) }}" wire:navigate class="group">
                    <flux:button variant="ghost" icon="arrow-left">
                        <div class="text-left">
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Previous</div>
                            <div>{{ $this->previousPageData['title'] ?? 'Previous Page' }}</div>
                        </div>
                    </flux:button>
                </a>
            @else
                <div></div>
            @endif
            
            @if($this->nextPage)
                <a href="{{ route('docs.show', $this->nextPage) }}" wire:navigate class="group">
                    <flux:button variant="ghost" icon:trailing="arrow-right">
                        <div class="text-right">
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">Next</div>
                            <div>{{ $this->nextPageData['title'] ?? 'Next Page' }}</div>
                        </div>
                    </flux:button>
                </a>
            @endif
        </div>
    </div>

    {{-- Back to Top --}}
    <div class="mt-8 text-center">
        <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="inline-flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100">
            <flux:icon name="arrow-up" class="size-4" />
            Back to top
        </button>
    </div>
</div>
