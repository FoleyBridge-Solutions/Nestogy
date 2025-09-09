<div class="animate-pulse">
    <flux:card>
        <!-- Header section -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="h-6 bg-zinc-200 dark:bg-zinc-700 rounded w-1/3"></div>
        </div>
        <!-- Body section -->
        <div class="p-6">
            <div class="space-y-3">
                @for ($i = 0; $i < 5; $i++)
                    <div class="flex space-x-4">
                        <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-1/4"></div>
                        <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-1/2"></div>
                        <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-1/4"></div>
                    </div>
                @endfor
            </div>
        </div>
    </flux:card>
</div>
