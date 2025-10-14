@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">Export Financial Data</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Export your financial data in various formats</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($exportTypes as $key => $name)
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-2">{{ $name }}</h3>
                <p class="text-zinc-600 dark:text-zinc-400 mb-4">Export all {{ strtolower($name) }} data to CSV format</p>
                <flux:button 
                    onclick="document.getElementById('export-{{ $key }}-form').submit()"
                    variant="primary"
                    size="sm"
                >
                    Export {{ $name }}
                </flux:button>
                <form id="export-{{ $key }}-form" action="{{ route('financial.export.' . $key) }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        </flux:card>
        @endforeach
    </div>

    <div class="mt-8">
        <flux:card>
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Advanced Export Options</h3>
                <form method="POST" action="{{ route('financial.export.reports') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <flux:label>Date Range</flux:label>
                            <div class="flex gap-2">
                                <flux:input type="date" name="start_date" />
                                <flux:input type="date" name="end_date" />
                            </div>
                        </div>
                        <div>
                            <flux:label>Export Format</flux:label>
                            <flux:select name="format">
                                <flux:select.option value="csv">CSV</flux:select.option>
                                <flux:select.option value="xlsx">Excel</flux:select.option>
                                <flux:select.option value="pdf">PDF</flux:select.option>
                            </flux:select>
                        </div>
                    </div>
                    <flux:button type="submit" variant="primary">
                        Generate Custom Report
                    </flux:button>
                </form>
            </div>
        </flux:card>
    </div>
</div>
@endsection