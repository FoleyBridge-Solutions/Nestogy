<div>
    {{-- Hero Section --}}
    <div class="text-center mb-16">
        <h1 class="text-4xl sm:text-5xl font-bold text-zinc-900 dark:text-white mb-4">
            Nestogy Documentation
        </h1>
        <p class="text-xl text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
            Everything you need to know about using Nestogy ERP for your MSP business
        </p>
    </div>

    {{-- Popular Pages --}}
    <div class="mb-16">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">
            Popular Guides
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($popularPages as $page)
                <a href="{{ route('docs.show', $page['slug']) }}" wire:navigate class="group">
                    <flux:card class="h-full hover:shadow-lg transition-shadow duration-200">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                    <flux:icon :name="$page['icon']" class="size-6 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-2">
                                    {{ $page['title'] }}
                                </h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $page['description'] }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <flux:icon name="arrow-right" class="size-5 text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" />
                            </div>
                        </div>
                    </flux:card>
                </a>
            @endforeach
        </div>
    </div>

    <flux:separator class="my-12" />

    {{-- All Categories --}}
    <div>
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-8">
            Browse by Category
        </h2>
        
        @foreach($categories as $category => $pages)
            <div class="mb-10">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                    {{ $category }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($pages as $page)
                        <a href="{{ route('docs.show', $page['slug']) }}" wire:navigate 
                           class="group flex items-start gap-3 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-950/20 transition-all">
                            <flux:icon :name="$page['icon']" class="size-5 text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mt-0.5 flex-shrink-0" />
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $page['title'] }}
                                </div>
                                <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                    {{ $page['description'] }}
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Help Section --}}
    <flux:separator class="my-12" />
    
    <div class="text-center py-8">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">
            Need More Help?
        </h2>
        <p class="text-zinc-600 dark:text-zinc-400 mb-6">
            Can't find what you're looking for? We're here to help.
        </p>
        <div class="flex justify-center gap-4">
            <a href="{{ route('docs.show', 'faq') }}" wire:navigate>
                <flux:button variant="outline" icon="question-mark-circle">
                    View FAQ
                </flux:button>
            </a>
            <a href="mailto:support@nestogy.com">
                <flux:button variant="primary" icon="envelope">
                    Contact Support
                </flux:button>
            </a>
        </div>
    </div>
</div>
