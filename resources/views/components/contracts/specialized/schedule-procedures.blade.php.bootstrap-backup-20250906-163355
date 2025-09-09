@props(['schedule', 'contract'])

@php
$procedures = $schedule->procedures ?? [];
$protocols = $schedule->protocols ?? [];
$workflowSteps = $schedule->workflow_steps ?? [];
$documentation = $schedule->documentation ?? [];
$responsibilities = $schedule->responsibilities ?? [];
@endphp

<div class="space-y-6">
    <!-- Standard Operating Procedures -->
    @if(!empty($procedures))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Standard Operating Procedures</h4>
            <div class="space-y-4">
                @foreach($procedures as $procedureId => $procedure)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ $procedure['title'] ?? 'Procedure ' . ($procedureId + 1) }}
                                </h5>
                                
                                @if(isset($procedure['category']))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-200 mt-2">
                                        {{ $procedure['category'] }}
                                    </span>
                                @endif
                                
                                @if(isset($procedure['description']))
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $procedure['description'] }}</p>
                                @endif
                            </div>
                            
                            @if(isset($procedure['criticality']))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($procedure['criticality'] === 'high') bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200
                                    @elseif($procedure['criticality'] === 'medium') bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200
                                    @else bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 @endif">
                                    {{ ucfirst($procedure['criticality']) }} Priority
                                </span>
                            @endif
                        </div>
                        
                        @if(isset($procedure['steps']) && is_array($procedure['steps']))
                            <div class="mt-4">
                                <h6 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Procedure Steps:</h6>
                                <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                    @foreach($procedure['steps'] as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                        
                        @if(isset($procedure['tools_required']))
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex flex-wrap gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tools Required:</span>
                                    @foreach($procedure['tools_required'] as $tool)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                            {{ $tool }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if(isset($procedure['estimated_time']))
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Estimated Time: {{ $procedure['estimated_time'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Communication Protocols -->
    @if(!empty($protocols))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Communication Protocols</h4>
            <div class="space-y-4">
                @foreach($protocols as $protocolType => $protocol)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                        <h5 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-3">
                            {{ ucwords(str_replace('_', ' ', $protocolType)) }} Protocol
                        </h5>
                        
                        @if(isset($protocol['description']))
                            <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">{{ $protocol['description'] }}</p>
                        @endif
                        
                        @if(isset($protocol['contacts']))
                            <div class="mb-3">
                                <h6 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Contact Information:</h6>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($protocol['contacts'] as $role => $contact)
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-blue-200 dark:bg-blue-800 rounded-full flex items-center justify-center">
                                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ ucfirst($role) }}</div>
                                                <div class="text-xs text-blue-700 dark:text-blue-300">
                                                    @if(is_array($contact))
                                                        {{ $contact['name'] ?? $contact['email'] ?? 'Contact Info' }}
                                                        @if(isset($contact['phone']))
                                                            | {{ $contact['phone'] }}
                                                        @endif
                                                    @else
                                                        {{ $contact }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if(isset($protocol['escalation_matrix']))
                            <div>
                                <h6 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">Escalation Matrix:</h6>
                                <div class="space-y-2">
                                    @foreach($protocol['escalation_matrix'] as $level => $details)
                                        <div class="flex items-center justify-between py-1 px-2 bg-blue-100 dark:bg-blue-800/20 rounded">
                                            <span class="text-sm text-blue-800 dark:text-blue-200">Level {{ $level }}</span>
                                            <span class="text-sm text-blue-700 dark:text-blue-300">{{ $details }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Workflow Steps -->
    @if(!empty($workflowSteps))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Standard Workflows</h4>
            <div class="space-y-6">
                @foreach($workflowSteps as $workflowName => $workflow)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h5 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            {{ ucwords(str_replace('_', ' ', $workflowName)) }}
                        </h5>
                        
                        @if(isset($workflow['description']))
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">{{ $workflow['description'] }}</p>
                        @endif
                        
                        @if(isset($workflow['steps']))
                            <div class="relative">
                                @foreach($workflow['steps'] as $stepIndex => $step)
                                    <div class="flex items-start space-x-4 {{ !$loop->last ? 'pb-6' : '' }}">
                                        @if(!$loop->last)
                                            <div class="absolute left-4 top-8 bottom-0 w-0.5 bg-gray-300 dark:bg-gray-600"></div>
                                        @endif
                                        
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center relative z-10">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $stepIndex + 1 }}</span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ is_array($step) ? $step['title'] : $step }}
                                            </div>
                                            
                                            @if(is_array($step))
                                                @if(isset($step['description']))
                                                    <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $step['description'] }}</div>
                                                @endif
                                                
                                                @if(isset($step['responsible']))
                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        Responsible: {{ $step['responsible'] }}
                                                    </div>
                                                @endif
                                                
                                                @if(isset($step['estimated_duration']))
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        Duration: {{ $step['estimated_duration'] }}
                                                    </div>
                                                @endif
                                                
                                                @if(isset($step['deliverables']))
                                                    <div class="mt-2">
                                                        <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Deliverables:</div>
                                                        <ul class="list-disc list-inside text-xs text-gray-600 dark:text-gray-300 mt-1">
                                                            @foreach($step['deliverables'] as $deliverable)
                                                                <li>{{ $deliverable }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Documentation Requirements -->
    @if(!empty($documentation))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Documentation Requirements</h4>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                <div class="space-y-4">
                    @foreach($documentation as $docType => $requirements)
                        <div class="border-b border-yellow-200 dark:border-yellow-700 pb-4 last:border-0 last:pb-0">
                            <h5 class="text-sm font-medium text-yellow-900 dark:text-yellow-100 mb-2">
                                {{ ucwords(str_replace('_', ' ', $docType)) }}
                            </h5>
                            
                            @if(is_array($requirements))
                                @if(isset($requirements['description']))
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-2">{{ $requirements['description'] }}</p>
                                @endif
                                
                                @if(isset($requirements['frequency']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                        <span class="font-medium">Update Frequency:</span> {{ $requirements['frequency'] }}
                                    </div>
                                @endif
                                
                                @if(isset($requirements['format']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                        <span class="font-medium">Format:</span> {{ $requirements['format'] }}
                                    </div>
                                @endif
                                
                                @if(isset($requirements['access_level']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                        <span class="font-medium">Access Level:</span> {{ $requirements['access_level'] }}
                                    </div>
                                @endif
                                
                                @if(isset($requirements['retention_period']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                                        <span class="font-medium">Retention Period:</span> {{ $requirements['retention_period'] }}
                                    </div>
                                @endif
                            @else
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ $requirements }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Responsibilities Matrix -->
    @if(!empty($responsibilities))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Responsibilities Matrix</h4>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Task/Activity
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Client
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Service Provider
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Notes
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($responsibilities as $task => $responsibility)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ ucwords(str_replace('_', ' ', $task)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if(is_array($responsibility) && isset($responsibility['client']))
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                @if($responsibility['client'] === 'responsible') bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200
                                                @elseif($responsibility['client'] === 'accountable') bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200
                                                @elseif($responsibility['client'] === 'consulted') bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200
                                                @elseif($responsibility['client'] === 'informed') bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                                                @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 @endif">
                                                {{ ucfirst($responsibility['client']) }}
                                            </span>
                                        @else
                                            {{ $responsibility['client'] ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if(is_array($responsibility) && isset($responsibility['provider']))
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                @if($responsibility['provider'] === 'responsible') bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200
                                                @elseif($responsibility['provider'] === 'accountable') bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-200
                                                @elseif($responsibility['provider'] === 'consulted') bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200
                                                @elseif($responsibility['provider'] === 'informed') bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                                                @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 @endif">
                                                {{ ucfirst($responsibility['provider']) }}
                                            </span>
                                        @else
                                            {{ $responsibility['provider'] ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ is_array($responsibility) ? ($responsibility['notes'] ?? '') : '' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-green-100 dark:bg-green-900/20 rounded mr-1"></span>
                        <span>Responsible</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-blue-100 dark:bg-blue-900/20 rounded mr-1"></span>
                        <span>Accountable</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-yellow-100 dark:bg-yellow-900/20 rounded mr-1"></span>
                        <span>Consulted</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-gray-100 dark:bg-gray-700 rounded mr-1"></span>
                        <span>Informed</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>