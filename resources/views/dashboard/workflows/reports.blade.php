<!-- Reports Dashboard -->
<div class="mb-8">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Reports Dashboard</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">Reports workflow view - Coming soon</p>
        
        @if(isset($data['report_categories']))
        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($data['report_categories'] as $category => $reports)
            <div>
                <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 capitalize mb-2">{{ $category }} Reports</h4>
                <ul class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
                    @foreach($reports as $report)
                    <li>" {{ $report }}</li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>