<flux:card class="text-center py-12">
    <div class="mx-auto max-w-md space-y-4">
        <div class="mx-auto w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
            <flux:icon name="chart-bar" class="w-8 h-8 text-zinc-400" />
        </div>
        <flux:heading size="xl">No Report Access</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            You need additional permissions to view reports. Contact your administrator to request access to specific report types.
        </flux:text>
        
        <div class="pt-4 space-y-2 text-left">
            <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Available Report Types:</flux:heading>
            <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-2">
                    <flux:icon name="lifebuoy" class="w-4 h-4" />
                    <span>Support Analytics - requires <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">can_view_tickets</code></span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="banknotes" class="w-4 h-4" />
                    <span>Financial Reports - requires <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">can_view_invoices</code></span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="server-stack" class="w-4 h-4" />
                    <span>Asset Reports - requires <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">can_view_assets</code></span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="briefcase" class="w-4 h-4" />
                    <span>Project Reports - requires <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">can_view_projects</code></span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="document-text" class="w-4 h-4" />
                    <span>Contract Reports - requires <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">can_view_contracts</code></span>
                </li>
                <li class="flex items-center gap-2">
                    <flux:icon name="clipboard-document-check" class="w-4 h-4" />
                    <span>Quote Reports - requires <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">can_view_quotes</code></span>
                </li>
            </ul>
        </div>
    </div>
</flux:card>
