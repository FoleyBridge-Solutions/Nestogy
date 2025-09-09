<div class="animate-pulse">
    <flux:card>
        <!-- Header section -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="h-6 bg-zinc-200 dark:bg-zinc-700 rounded w-1/3"></div>
        </div>
        <!-- Body section -->
        <div class="p-6">
            <div class="grid grid-cols-3 gap-4">
                @for ($i = 0; $i < 3; $i++)
                    <div class="text-center space-y-2">
                        <div class="h-8 bg-zinc-200 dark:bg-zinc-700 rounded mx-auto w-16"></div>
                        <div class="h-4 bg-zinc-200 dark:bg-zinc-700 rounded mx-auto w-20"></div>
                    </div>
                @endfor
            </div>
        </div>
    </flux:card>
</div>
