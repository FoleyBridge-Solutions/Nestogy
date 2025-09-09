<div class="animate-pulse">
    <flux:card>
        <!-- Header section -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="h-6 bg-zinc-200 dark:bg-zinc-700 rounded w-1/3"></div>
        </div>
        <!-- Body section -->
        <div class="p-6">
            <div class="space-y-4">
                @for ($i = 0; $i < 3; $i++)
                    <div class="flex space-x-3">
                        <div class="h-10 w-10 bg-zinc-200 dark:bg-zinc-700 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded w-3/4"></div>
                            <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </flux:card>
</div>
