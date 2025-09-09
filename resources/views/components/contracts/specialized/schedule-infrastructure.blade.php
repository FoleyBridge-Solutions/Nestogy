@props(['schedule', 'contract'])

@php
$supportedAssetTypes = $schedule->supported_asset_types ?? [];
$serviceLevels = $schedule->service_levels ?? [];
$coverageRules = $schedule->coverage_rules ?? [];
$excludedAssetTypes = $schedule->excluded_asset_types ?? [];
@endphp

<div class="space-y-6">
    <!-- Supported Asset Types -->
    @if(!empty($supportedAssetTypes))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Supported Asset Types</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($supportedAssetTypes as $assetType => $config)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <!-- Asset Type Icon -->
                                <div class="flex-shrink-0">
                                    @php
                                    $assetIcons = [
                                        'workstation' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                                        'server' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2',
                                        'network_device' => 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
                                        'mobile_device' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
                                        'printer' => 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z'
                                    ];
                                    $icon = $assetIcons[$assetType] ?? $assetIcons['workstation'];
                                    @endphp
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                                    </svg>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                        {{ ucwords(str_replace('_', ' ', $assetType)) }}
                                    </h5>
                                    @if(isset($config['description']))
                                        <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">{{ $config['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Asset Count -->
                            @if($contract)
                                @php
                                $assetCount = $contract->supportedAssets()->where('asset_type', $assetType)->count();
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200">
                                    {{ $assetCount }} {{ Str::plural('asset', $assetCount) }}
                                </span>
                            @endif
                        </div>

                        <!-- Service Level for this asset type -->
                        @if(isset($config['service_level']))
                            <div class="mt-6 pt-3 border-t border-blue-200 dark:border-blue-700">
                                <div class="text-xs text-blue-700 dark:text-blue-300">
                                    <span class="font-medium">Service Level:</span> {{ $config['service_level'] }}
                                </div>
                                @if(isset($config['response_time']))
                                    <div class="text-xs text-blue-700 dark:text-blue-300">
                                        <span class="font-medium">Response Time:</span> {{ $config['response_time'] }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Service Levels -->
    @if(!empty($serviceLevels))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Service Level Definitions</h4>
            <div class="space-y-4">
                @foreach($serviceLevels as $level => $definition)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ ucwords(str_replace('_', ' ', $level)) }}
                                </h5>
                                @if(isset($definition['description']))
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $definition['description'] }}</p>
                                @endif
                                
                                <!-- SLA Metrics -->
                                @if(isset($definition['metrics']))
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                                        @foreach($definition['metrics'] as $metric => $value)
                                            <div class="text-center">
                                                <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">{{ $value }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ ucwords(str_replace('_', ' ', $metric)) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Coverage Rules -->
    @if(!empty($coverageRules))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Coverage Rules</h4>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                <div class="space-y-3">
                    @foreach($coverageRules as $rule)
                        <div class="flex items-start space-x-3">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-yellow-800 dark:text-yellow-200">
                                @if(is_array($rule))
                                    <div class="font-medium">{{ $rule['name'] ?? 'Rule' }}</div>
                                    <div class="mt-1">{{ $rule['description'] ?? $rule['condition'] ?? '' }}</div>
                                @else
                                    {{ $rule }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Excluded Asset Types -->
    @if(!empty($excludedAssetTypes))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Excluded from Support</h4>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($excludedAssetTypes as $assetType => $reason)
                        <div class="flex items-center space-x-3">
                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm">
                                <span class="font-medium text-red-800 dark:text-red-200">{{ ucwords(str_replace('_', ' ', $assetType)) }}</span>
                                @if(is_string($reason))
                                    <div class="text-red-700 dark:text-red-300">{{ $reason }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Asset Assignment Summary -->
    @if(isset($contract) && $contract)
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Current Asset Assignments</h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $contract->supportedAssets()->count() }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Total Supported Assets</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            Last updated: {{ $contract->updated_at->format('M d, Y g:i A') }}
                        </div>
                        @if($contract->canBeEdited())
                            <button type="button" 
                                    class="mt-2 inline-flex items-center px-6 py-1.5 border border-transparent text-sm font-medium rounded text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/20 hover:bg-blue-200 dark:hover:bg-blue-800/20 transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Manage Assignments
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Preview for contract creation -->
        <div class="space-y-6">
            <div>
                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Infrastructure Schedule Preview</h4>
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-lg font-medium text-blue-900 dark:text-blue-100">Schedule A - Infrastructure & SLA Configuration</h5>
                            <p class="mt-2 text-blue-700 dark:text-blue-300">
                                This schedule will define which asset types are covered under the contract, service level agreements, 
                                and automatic assignment rules based on your template selection.
                            </p>
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-blue-200 dark:border-blue-600">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Asset Types</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Workstations, Servers, Network Devices</div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-blue-200 dark:border-blue-600">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">SLA Terms</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Response times, Resolution targets</div>
                                </div>
                                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-blue-200 dark:border-blue-600">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Coverage Rules</div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">Assignment criteria, Exclusions</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Assignment Preview -->
            <div>
                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Asset Assignment Configuration</h4>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h5 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Automated Configuration</h5>
                            <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                Asset assignment rules and infrastructure schedule will be automatically configured based on your selected template 
                                and can be customized after contract creation.
                            </p>
                            <div class="mt-6 flex items-center space-x-4 text-xs text-yellow-600 dark:text-yellow-400">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Auto-assignment rules
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    SLA configuration
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Coverage rules
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
