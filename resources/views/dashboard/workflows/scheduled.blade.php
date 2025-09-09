<!-- Scheduled Work Dashboard -->
<div class="mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Scheduled Work</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">Scheduled workflow view - Coming soon</p>
        
        @if(isset($data['scheduled_tickets']))
        <div class="mt-4">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                {{ count($data['scheduled_tickets']) }} scheduled tickets in the next week
            </p>
        </div>
        @endif
    </div>
</div>
