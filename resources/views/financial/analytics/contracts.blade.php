@extends('layouts.app')

@section('title', 'Contract Analytics')

@section('content')
<div class="min-h-screen">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">Contract Analytics</flux:heading>
                    <flux:text class="mt-1">Performance insights and contract metrics</flux:text>
                </div>
                <div class="flex items-center space-x-3">
                    <flux:button variant="ghost" icon="arrow-path">
                        Refresh Data
                    </flux:button>
                    <flux:button variant="ghost" icon="arrow-down-tray">
                        Export Report
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <flux:card>
                <div class="flex items-center">
                    <div class="flex-1">
                        <flux:text size="sm" class="text-gray-500">Total Contracts</flux:text>
                        <flux:heading size="lg">0</flux:heading>
                    </div>
                    <flux:icon.document-text class="w-8 h-8 text-blue-500" />
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center">
                    <div class="flex-1">
                        <flux:text size="sm" class="text-gray-500">Active Contracts</flux:text>
                        <flux:heading size="lg">0</flux:heading>
                    </div>
                    <flux:icon.check-circle class="w-8 h-8 text-green-500" />
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center">
                    <div class="flex-1">
                        <flux:text size="sm" class="text-gray-500">Total Value</flux:text>
                        <flux:heading size="lg">$0</flux:heading>
                    </div>
                    <flux:icon.currency-dollar class="w-8 h-8 text-yellow-500" />
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center">
                    <div class="flex-1">
                        <flux:text size="sm" class="text-gray-500">Expiring Soon</flux:text>
                        <flux:heading size="lg">0</flux:heading>
                    </div>
                    <flux:icon.exclamation-triangle class="w-8 h-8 text-red-500" />
                </div>
            </flux:card>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Contract Status Chart -->
            <flux:card>
                <div class="p-6">
                    <flux:heading size="lg" class="mb-4">Contract Status Overview</flux:heading>
                    <div class="text-center py-12">
                        <flux:text class="text-gray-500">Chart will be implemented here</flux:text>
                    </div>
                </div>
            </flux:card>

            <!-- Recent Activity -->
            <flux:card>
                <div class="p-6">
                    <flux:heading size="lg" class="mb-4">Recent Contract Activity</flux:heading>
                    <div class="text-center py-12">
                        <flux:text class="text-gray-500">No recent activity</flux:text>
                    </div>
                </div>
            </flux:card>
        </div>

        <!-- Analytics Note -->
        <div class="mt-8">
            <flux:card class="bg-blue-50 border-blue-200">
                <div class="p-6">
                    <div class="flex items-start">
                        <flux:icon.information-circle class="w-6 h-6 text-blue-500 mr-3 mt-0.5" />
                        <div>
                            <flux:heading size="sm" class="text-blue-900 mb-2">Analytics Coming Soon</flux:heading>
                            <flux:text class="text-blue-700">
                                Contract analytics features are currently under development. This page will be updated with comprehensive metrics, charts, and insights once the analytics engine is implemented.
                            </flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>
@endsection