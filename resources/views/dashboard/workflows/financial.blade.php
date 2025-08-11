<!-- Financial Dashboard -->
<div class="mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Financial Overview</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            @if(isset($data['counts']))
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $data['counts']['pending_invoices'] ?? 0 }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Pending Invoices</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $data['counts']['recent_payments'] ?? 0 }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Recent Payments</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ $data['counts']['upcoming_invoices'] ?? 0 }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Upcoming Invoices</p>
            </div>
            @endif
        </div>
    </div>
</div>