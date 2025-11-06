<nav class="p-6 space-y-8">
    {{-- Home Link --}}
    <div>
        <a href="{{ route('docs.index') }}" 
           wire:navigate 
           class="flex items-center gap-2 text-zinc-900 dark:text-white font-semibold hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
            <flux:icon name="home" class="size-5" />
            Documentation Home
        </a>
    </div>

    <flux:separator />

    {{-- Navigation Categories --}}
    @foreach($navigation as $category => $pages)
        <div>
            <h3 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">
                {{ $category }}
            </h3>
            <ul class="space-y-1">
                @foreach($pages as $page)
                    <li>
                        <a href="{{ route('docs.show', $page['slug']) }}"
                           wire:navigate
                           @class([
                               'flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors',
                               'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100 font-medium' => $this->isActive($page['slug']),
                               'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800' => !$this->isActive($page['slug']),
                           ])>
                            <flux:icon :name="$page['icon']" class="size-4 flex-shrink-0" />
                            <span>{{ $page['title'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
