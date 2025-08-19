{{-- Telecom Schedule - For VoIP and telecommunications contract templates --}}
<div class="space-y-8">
    <!-- Schedule Header -->
    <div class="border-b border-gray-200 dark:border-gray-600 pb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            Schedule A - Telecommunications Infrastructure & Service Levels
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Configure telecommunications services, channel capacity, calling plans, and quality of service metrics.
        </p>
    </div>

    <!-- Service Type Configuration -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
        <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            Service Configuration
        </h4>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Channel Capacity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Channel Count
                </label>
                <div class="relative">
                    <input type="number" 
                           x-model="telecomSchedule.channelCount" 
                           min="1" max="1000"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-sm">channels</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Number of simultaneous call channels</p>
            </div>

            <!-- Calling Plan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Calling Plan
                </label>
                <select x-model="telecomSchedule.callingPlan" 
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select calling plan...</option>
                    <option value="local_only">Local Only</option>
                    <option value="local_long_distance">Local + Long Distance</option>
                    <option value="unlimited">Unlimited</option>
                    <option value="custom">Custom Plan</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Included calling features</p>
            </div>

            <!-- International Calling -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    International Calling
                </label>
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="radio" x-model="telecomSchedule.internationalCalling" value="included" 
                               class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Included</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" x-model="telecomSchedule.internationalCalling" value="additional" 
                               class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Additional Charges</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" x-model="telecomSchedule.internationalCalling" value="disabled" 
                               class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Disabled</span>
                    </label>
                </div>
            </div>

            <!-- Emergency Services -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Emergency Services (E911)
                </label>
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="radio" x-model="telecomSchedule.emergencyServices" value="enabled" 
                               class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enabled</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" x-model="telecomSchedule.emergencyServices" value="enhanced" 
                               class="text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enhanced E911</span>
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1">FCC-compliant emergency services</p>
            </div>
        </div>
    </div>

    <!-- Quality of Service (QoS) Configuration -->
    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-6 border border-green-200 dark:border-green-800">
        <h4 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Quality of Service Metrics
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Call Quality Metrics -->
            <div class="space-y-4">
                <h5 class="font-medium text-gray-900 dark:text-gray-100">Call Quality</h5>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Mean Opinion Score (MOS)
                    </label>
                    <select x-model="telecomSchedule.qos.meanOpinionScore" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                        <option value="4.0">4.0 - Good</option>
                        <option value="4.2">4.2 - Very Good</option>
                        <option value="4.4">4.4 - Excellent</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Jitter (ms)
                    </label>
                    <input type="number" x-model="telecomSchedule.qos.jitterMs" max="50" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Packet Loss (%)
                    </label>
                    <input type="number" x-model="telecomSchedule.qos.packetLossPercent" step="0.01" max="1" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                </div>
            </div>

            <!-- Availability Metrics -->
            <div class="space-y-4">
                <h5 class="font-medium text-gray-900 dark:text-gray-100">Service Availability</h5>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Uptime Guarantee (%)
                    </label>
                    <select x-model="telecomSchedule.qos.uptimePercent" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                        <option value="99.0">99.0% - Basic</option>
                        <option value="99.5">99.5% - Standard</option>
                        <option value="99.9">99.9% - Premium</option>
                        <option value="99.95">99.95% - Enterprise</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Maximum Outage Duration
                    </label>
                    <input type="text" x-model="telecomSchedule.qos.maxOutageDuration" 
                           placeholder="e.g., 4 hours"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Network Latency (ms)
                    </label>
                    <input type="number" x-model="telecomSchedule.qos.latencyMs" max="150" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                </div>
            </div>

            <!-- Support Metrics -->
            <div class="space-y-4">
                <h5 class="font-medium text-gray-900 dark:text-gray-100">Technical Support</h5>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Response Time (Hours)
                    </label>
                    <select x-model="telecomSchedule.qos.responseTimeHours" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                        <option value="0.25">15 minutes</option>
                        <option value="1">1 hour</option>
                        <option value="2">2 hours</option>
                        <option value="4">4 hours</option>
                        <option value="8">8 hours</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Resolution Time (Hours)
                    </label>
                    <input type="number" x-model="telecomSchedule.qos.resolutionTimeHours" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Support Coverage
                    </label>
                    <select x-model="telecomSchedule.qos.supportCoverage" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-green-500">
                        <option value="24x7">24x7x365</option>
                        <option value="24x5">24x5 (Mon-Fri)</option>
                        <option value="12x5">12x5 Business Hours</option>
                        <option value="8x5">8x5 Business Hours</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Carrier & Interconnection -->
    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
        <h4 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Carrier & Network Configuration
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Primary Carrier -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Primary Carrier
                </label>
                <input type="text" x-model="telecomSchedule.carrier.primary" 
                       placeholder="e.g., Verizon, AT&T, Level3"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Backup Carrier -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Backup Carrier (Optional)
                </label>
                <input type="text" x-model="telecomSchedule.carrier.backup" 
                       placeholder="Redundant carrier for failover"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Protocol -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Protocol
                </label>
                <select x-model="telecomSchedule.protocol" 
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500">
                    <option value="sip">SIP (Session Initiation Protocol)</option>
                    <option value="pri">PRI (Primary Rate Interface)</option>
                    <option value="sip_trunking">SIP Trunking</option>
                    <option value="hosted_pbx">Hosted PBX</option>
                </select>
            </div>

            <!-- Codec Support -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Supported Codecs
                </label>
                <div class="space-y-2">
                    <template x-for="codec in ['G.711', 'G.722', 'G.729', 'Opus']" :key="codec">
                        <label class="flex items-center">
                            <input type="checkbox" :value="codec" x-model="telecomSchedule.codecs" 
                                   class="text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300" x-text="codec"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Compliance & Regulatory -->
    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-6 border border-orange-200 dark:border-orange-800">
        <h4 class="text-lg font-semibold text-orange-900 dark:text-orange-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Regulatory Compliance
        </h4>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- FCC Compliance -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    FCC Compliance Requirements
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="telecomSchedule.compliance.fccCompliant" 
                               class="text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">FCC Part 68 Certified</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="telecomSchedule.compliance.karisLaw" 
                               class="text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Kari's Law Compliance</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="telecomSchedule.compliance.rayBaums" 
                               class="text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">RAY BAUM'S Act Compliance</span>
                    </label>
                </div>
            </div>

            <!-- Security Features -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Security Features
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="telecomSchedule.security.encryption" 
                               class="text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Voice Encryption (TLS/SRTP)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="telecomSchedule.security.fraudProtection" 
                               class="text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Fraud Protection</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="telecomSchedule.security.callRecording" 
                               class="text-orange-600 focus:ring-orange-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Call Recording Available</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>