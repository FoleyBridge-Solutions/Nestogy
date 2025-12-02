<div class="space-y-6">
    {{-- Header --}}
    <flux:card>
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold">{{ $article->title }}</h1>
                    <div class="flex items-center gap-4 mt-3 text-sm text-gray-600">
                        <span><strong>Category:</strong> {{ $article->category?->name ?? 'Uncategorized' }}</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>Author:</strong> {{ $article->author?->name }}</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>Published:</strong> {{ $article->published_at?->format('M d, Y') }}</span>
                        <flux:separator vertical class="h-4" />
                        <span><strong>Version:</strong> {{ $article->version }}</span>
                    </div>
                    <div class="flex items-center gap-4 mt-2">
                        <flux:badge variant="{{ $article->status === 'published' ? 'success' : 'warning' }}">
                            {{ ucfirst($article->status) }}
                        </flux:badge>
                        <flux:badge variant="outline">
                            {{ ucfirst($article->visibility) }} Visibility
                        </flux:badge>
                        <flux:badge variant="outline">
                            <flux:icon.eye class="mr-1" />
                            {{ $article->views_count }} views
                        </flux:badge>
                        @if($article->helpful_count + $article->not_helpful_count > 0)
                            <flux:badge variant="outline">
                                <flux:icon.hand-thumb-up class="mr-1" />
                                {{ $article->helpfulness_percentage }}% helpful
                            </flux:badge>
                        @endif
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <flux:button variant="outline" size="sm" href="{{ route('knowledge.articles.edit', $article) }}">
                        <flux:icon.pencil class="mr-2" />
                        Edit
                    </flux:button>
                    
                    <flux:button variant="{{ $article->status === 'published' ? 'ghost' : 'primary' }}" size="sm" wire:click="togglePublish">
                        <flux:icon.{{ $article->status === 'published' ? 'eye-slash' : 'eye' }} class="mr-2" />
                        {{ $article->status === 'published' ? 'Unpublish' : 'Publish' }}
                    </flux:button>
                    
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm">
                            <flux:icon.ellipsis-horizontal />
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item href="{{ route('knowledge.articles.edit', $article) }}">
                                <flux:icon.pencil class="mr-2" />
                                Edit Article
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('knowledge.articles.version-history', $article) }}">
                                <flux:icon.clock class="mr-2" />
                                Version History
                            </flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item wire:click="$set('showDeleteModal', true)" variant="danger">
                                <flux:icon.trash class="mr-2" />
                                Delete Article
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- AI Insights Widget --}}
    <x-ai-insights 
        :enabled="$aiEnabled"
        :loading="$aiLoading"
        :insights="$aiInsights"
    />

    {{-- Article Content --}}
    <flux:card>
        <div class="p-8">
            @if($article->excerpt)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <p class="text-blue-800">{{ $article->excerpt }}</p>
                </div>
            @endif
            
            <div class="prose max-w-none">
                {!! $article->content !!}
            </div>
        </div>
    </flux:card>

    {{-- Tags --}}
    @if($article->tags && count($article->tags) > 0)
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-3">Tags</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($article->tags as $tag)
                        <flux:badge variant="outline">{{ $tag }}</flux:badge>
                    @endforeach
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Related Articles --}}
    @if($article->relatedArticles->count() > 0)
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Related Articles</h3>
                <div class="space-y-3">
                    @foreach($article->relatedArticles as $related)
                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div>
                                <a href="{{ route('knowledge.articles.show', $related) }}" class="font-medium hover:text-blue-600">
                                    {{ $related->title }}
                                </a>
                                <div class="text-sm text-gray-500">
                                    {{ $related->views_count }} views • {{ $related->helpfulness_percentage }}% helpful
                                </div>
                            </div>
                            <flux:icon.chevron-right class="text-gray-400" />
                        </div>
                    @endforeach
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Feedback --}}
    <flux:card>
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Was this article helpful?</h3>
            <div class="flex items-center gap-4">
                <flux:button variant="outline" wire:click="markAsHelpful">
                    <flux:icon.hand-thumb-up class="mr-2" />
                    Yes ({{ $article->helpful_count }})
                </flux:button>
                <flux:button variant="outline" wire:click="markAsNotHelpful">
                    <flux:icon.hand-thumb-down class="mr-2" />
                    No ({{ $article->not_helpful_count }})
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
