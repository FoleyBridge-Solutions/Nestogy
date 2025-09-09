@props(['schedule', 'contract'])

@php
$slaMetrics = $schedule->sla_metrics ?? [];
$responseTimeRequirements = $schedule->response_time_requirements ?? [];
$availabilityRequirements = $schedule->availability_requirements ?? [];
$escalationProcedures = $schedule->escalation_procedures ?? [];
$performanceTargets = $schedule->performance_targets ?? [];
@endphp

<div class="space-y-6">
    <!-- SLA Metrics Overview -->
    @if(!empty($slaMetrics))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Service Level Metrics</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($slaMetrics as $metric => $target)
                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ is_array($target) ? $target['target'] : $target }}
                                @if(is_array($target) && isset($target['unit']))
                                    <span class="text-sm font-normal">{{ $target['unit'] }}</span>
                                @endif
                            </div>
                            <div class="text-sm font-medium text-purple-800 dark:text-purple-200 mt-1">
                                {{ ucwords(str_replace('_', ' ', $metric)) }}
                            </div>
                            @if(is_array($target) && isset($target['current']))
                                <div class="text-xs text-purple-600 dark:text-purple-300 mt-1">
                                    Current: {{ $target['current'] }}{{ $target['unit'] ?? '' }}
                                </div>
                            @endif
                        </div>
                        
                        @if(is_array($target) && isset($target['current']) && isset($target['target']))
                            @php
                            $percentage = $target['target'] > 0 ? ($target['current'] / $target['target']) * 100 : 0;
                            $isGood = $metric === 'uptime_percentage' ? $percentage >= 95 : $percentage <= 100;
                            @endphp
                            <div class="mt-6">
                                <div class="flex justify-between text-xs text-purple-700 dark:text-purple-300">
                                    <span>Performance</span>
                                    <span>{{ round($percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-purple-200 dark:bg-purple-800 rounded-full h-2 mt-1">
                                    <div class="h-2 rounded-full {{ $isGood ? 'bg-green-500' : 'bg-red-500' }}" 
                                         style="width: {{ min(100, $percentage) }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Response Time Requirements -->
    @if(!empty($responseTimeRequirements))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Response Time Requirements</h4>
            <div class="space-y-4">
                @foreach($responseTimeRequirements as $priority => $requirement)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Priority Badge -->
                                <span class="inline-flex items-center px-6 py-1 rounded-full text-sm font-medium
                                    @if($priority === 'critical') bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200
                                    @elseif($priority === 'high') bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-200
                                    @elseif($priority === 'medium') bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200
                                    @else bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200 @endif">
                                    {{ ucfirst($priority) }} Priority
                                </span>
                                
                                <!-- Response Time -->
                                <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $requirement['response_time'] ?? 'Not specified' }}
                                </div>
                            </div>
                            
                            <!-- Resolution Time -->
                            @if(isset($requirement['resolution_time']))
                                <div class="text-right">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Resolution Target</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $requirement['resolution_time'] }}</div>
                                </div>
                            @endif
                        </div>
                        
                        @if(isset($requirement['description']))
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $requirement['description'] }}</p>
                        @endif
                        
                        @if(isset($requirement['coverage_hours']))
                            <div class="mt-6 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Coverage Hours:</span> {{ $requirement['coverage_hours'] }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Availability Requirements -->
    @if(!empty($availabilityRequirements))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Availability Requirements</h4>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($availabilityRequirements as $service => $requirement)
                        <div class="text-center">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">
                                {{ $requirement['target'] ?? 'N/A' }}
                            </div>
                            <div class="text-sm font-medium text-green-800 dark:text-green-200">
                                {{ ucwords(str_replace('_', ' ', $service)) }}
                            </div>
                            @if(isset($requirement['measured_period']))
                                <div class="text-xs text-green-600 dark:text-green-300">
                                    Per {{ $requirement['measured_period'] }}
                                </div>
                            @endif
                            
                            @if(isset($requirement['planned_downtime']))
                                <div class="text-xs text-green-600 dark:text-green-300 mt-1">
                                    Planned Downtime: {{ $requirement['planned_downtime'] }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Escalation Procedures -->
    @if(!empty($escalationProcedures))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Escalation Procedures</h4>
            <div class="space-y-4">
                @foreach($escalationProcedures as $level => $procedure)
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 text-white rounded-full flex items-center justify-center font-bold text-sm">
                                    {{ $level }}
                                </div>
                            </div>
                            <div class="flex-1">
                                <h5 class="text-sm font-medium text-yellow-900 dark:text-yellow-100">
                                    Level {{ $level }} Escalation
                                </h5>
                                
                                @if(isset($procedure['trigger_time']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        <span class="font-medium">Trigger:</span> After {{ $procedure['trigger_time'] }}
                                    </div>
                                @endif
                                
                                @if(isset($procedure['contacts']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        <span class="font-medium">Contacts:</span> {{ implode(', ', $procedure['contacts']) }}
                                    </div>
                                @endif
                                
                                @if(isset($procedure['actions']))
                                    <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-2">
                                        <span class="font-medium">Actions:</span>
                                        <ul class="list-disc list-inside mt-1 space-y-1">
                                            @foreach($procedure['actions'] as $action)
                                                <li>{{ $action }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Performance Targets -->
    @if(!empty($performanceTargets))
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Performance Targets</h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                <div class="space-y-4">
                    @foreach($performanceTargets as $target => $details)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-600 last:border-0">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ ucwords(str_replace('_', ' ', $target)) }}
                                </div>
                                @if(is_array($details) && isset($details['description']))
                                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $details['description'] }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ is_array($details) ? $details['target'] : $details }}
                                </div>
                                @if(is_array($details) && isset($details['penalty']))
                                    <div class="text-xs text-red-600 dark:text-red-400">
                                        Penalty: {{ $details['penalty'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Current SLA Compliance -->
    @if($contract)
        <div>
            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-6">Current SLA Compliance</h4>
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-6">
                @php $compliance = $contract->checkSLACompliance(); @endphp
                
                @if(!empty($compliance))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($compliance as $metric => $data)
                            <div class="text-center">
                                <div class="text-2xl font-bold {{ $data['compliant'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $data['actual'] }}
                                </div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ ucwords(str_replace('_', ' ', $metric)) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Target: {{ $data['target'] }}
                                </div>
                                <div class="mt-1">
                                    @if($data['compliant'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Compliant
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            Non-Compliant
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center">
                        <div class="text-gray-500 dark:text-gray-400">
                            No SLA compliance data available yet.
                        </div>
                        <div class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                            Data will be available after monitoring systems are integrated.
                        </div>
                    </div>
                @endif
                
                <div class="mt-6 text-center">
                    <button type="button" 
                            class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        View Detailed SLA Report
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
