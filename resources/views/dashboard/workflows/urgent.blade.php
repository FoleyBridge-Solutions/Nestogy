<!-- CRISIS COMMAND CENTER -->
<div x-data="urgentDashboard()" x-init="init()" class="crisis-command-center">
    
    <!-- CRITICAL ALERT HEADER -->
    <div class="alert-header-bar mb-6 relative overflow-hidden rounded-xl">
        <div class="absolute inset-0 bg-gradient-to-r from-red-600 via-red-500 to-orange-500 animate-gradient"></div>
        <div class="relative px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="pulse-alert">
                        <svg class="h-8 w-8 text-white animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">CRISIS COMMAND CENTER</h2>
                        <p class="text-red-100 text-sm">
                            <span x-text="criticalCount"></span> Critical Items Requiring Immediate Action
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Live Clock -->
                    <div class="text-white font-mono text-lg" x-text="currentTime"></div>
                    <!-- Full Screen Mode -->
                    <button @click="toggleFullScreen()" class="text-white hover:text-red-100 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CRISIS METRICS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- SLA Breaches -->
        <div class="crisis-metric-card bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-xl transform hover:scale-105 transition-all duration-300" 
             :class="{ 'animate-shake': slaBreaches > 0 }">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-xs uppercase tracking-wide">SLA Breaches</p>
                    <p class="text-3xl font-bold mt-1">
                        <span x-text="slaBreaches" class="counter">{{ $data['counts']['sla_breaches'] ?? 0 }}</span>
                    </p>
                </div>
                <div class="bg-red-400/30 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <div class="bg-red-400/30 rounded-full h-2">
                    <div class="bg-white rounded-full h-2 animate-pulse" :style="`width: ${Math.min((slaBreaches / 10) * 100, 100)}%`"></div>
                </div>
            </div>
        </div>

        <!-- Critical Tickets -->
        <div class="crisis-metric-card bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-xl transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-xs uppercase tracking-wide">Critical Tickets</p>
                    <p class="text-3xl font-bold mt-1">
                        <span x-text="criticalTickets" class="counter">{{ $data['counts']['urgent_tickets'] ?? 0 }}</span>
                    </p>
                </div>
                <div class="bg-orange-400/30 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </div>
            </div>
            <p class="text-orange-100 text-xs mt-2">
                <span x-show="newCritical > 0" class="inline-flex items-center">
                    <span class="animate-pulse mr-1">🔴</span>
                    <span x-text="newCritical"></span> new in last hour
                </span>
            </p>
        </div>

        <!-- Overdue Invoices -->
        <div class="crisis-metric-card bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-xl transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-xs uppercase tracking-wide">Overdue</p>
                    <p class="text-3xl font-bold mt-1">
                        <span x-text="overdueInvoices" class="counter">{{ $data['counts']['overdue_invoices'] ?? 0 }}</span>
                    </p>
                </div>
                <div class="bg-purple-400/30 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
            </div>
            <p class="text-purple-100 text-xs mt-2">
                ${{ number_format(array_sum(($data['overdue_invoices'] ?? collect())->pluck('amount')->toArray()), 2) }} total
            </p>
        </div>

        <!-- Escalations -->
        <div class="crisis-metric-card bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl p-4 text-white shadow-xl transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-pink-100 text-xs uppercase tracking-wide">Escalations</p>
                    <p class="text-3xl font-bold mt-1">
                        <span x-text="escalations">{{ $data['counts']['urgent_tickets'] ?? 0 }}</span>
                    </p>
                </div>
                <div class="bg-pink-400/30 p-3 rounded-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                    </svg>
                </div>
            </div>
            <p class="text-pink-100 text-xs mt-2">Next in <span x-text="nextEscalation"></span></p>
        </div>
    </div>

    <!-- PRIORITY HEAT MAP & CRITICAL ITEMS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Priority Heat Map -->
        <div class="lg:col-span-1">
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl p-4 shadow-xl">
                <h3 class="text-white font-semibold mb-3 flex items-center">
                    <svg class="h-5 w-5 mr-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Priority Heat Map
                </h3>
                <div class="grid grid-cols-4 gap-1">
                    <template x-for="(cell, index) in heatmapData" :key="index">
                        <div class="aspect-square rounded cursor-pointer transition-all duration-300 hover:scale-110"
                             :class="getHeatmapColor(cell.value)"
                             :title="`${cell.client}: ${cell.value} critical items`">
                            <div class="w-full h-full flex items-center justify-center text-xs font-bold text-white">
                                <span x-text="cell.value"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                    <span>Low</span>
                    <div class="flex space-x-1">
                        <div class="w-3 h-3 bg-green-500 rounded"></div>
                        <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                        <div class="w-3 h-3 bg-orange-500 rounded"></div>
                        <div class="w-3 h-3 bg-red-500 rounded"></div>
                    </div>
                    <span>Critical</span>
                </div>
            </div>
        </div>

        <!-- Critical Items List -->
        <div class="lg:col-span-2">
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl p-4 shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-white font-semibold flex items-center">
                        <span class="animate-pulse mr-2 text-red-500">⚠️</span>
                        Critical Items Requiring Action
                    </h3>
                    <button @click="refreshData()" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="h-5 w-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
                
                @if(isset($data['urgent_tickets']) && count($data['urgent_tickets']) > 0)
                <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                    @foreach($data['urgent_tickets']->take(10) as $ticket)
                    <div class="critical-item bg-gradient-to-r from-red-900/50 to-red-800/30 rounded-lg p-3 border border-red-500/30 hover:border-red-500 transition-all duration-300 cursor-pointer"
                         @click="openTicket({{ $ticket->id }})">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-red-400 font-mono text-sm">#{{ $ticket->id }}</span>
                                    <span class="text-white font-medium text-sm">{{ Str::limit($ticket->subject ?? 'No Subject', 40) }}</span>
                                    @if($ticket->created_at && $ticket->created_at->diffInMinutes() < 30)
                                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full animate-pulse">NEW</span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-3 mt-1">
                                    <span class="text-slate-400 text-xs">{{ $ticket->client->name ?? 'No Client' }}</span>
                                    <span class="text-slate-500">•</span>
                                    <span class="text-orange-400 text-xs">{{ $ticket->created_at ? $ticket->created_at->diffForHumans() : 'N/A' }}</span>
                                    @if(isset($ticket->assignee))
                                    <span class="text-slate-500">•</span>
                                    <span class="text-blue-400 text-xs">{{ $ticket->assignee->name }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="bg-red-500/20 px-2 py-1 rounded">
                                    <span class="text-red-300 text-xs font-medium">{{ strtoupper($ticket->priority ?? 'CRITICAL') }}</span>
                                </div>
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="h-12 w-12 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-green-400 font-medium">All Clear!</p>
                    <p class="text-slate-400 text-sm">No critical tickets at this time</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- AUTO-ESCALATION TIMELINE -->
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl p-4 shadow-xl mb-6">
        <h3 class="text-white font-semibold mb-3 flex items-center">
            <svg class="h-5 w-5 mr-2 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Auto-Escalation Timeline
        </h3>
        <div class="relative">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-green-500 via-yellow-500 to-red-500"></div>
            <div class="space-y-4 pl-6">
                <template x-for="escalation in escalationTimeline" :key="escalation.id">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-3 h-3 rounded-full animate-pulse"
                                 :class="{
                                     'bg-green-500': escalation.minutes > 60,
                                     'bg-yellow-500': escalation.minutes > 30 && escalation.minutes <= 60,
                                     'bg-orange-500': escalation.minutes > 15 && escalation.minutes <= 30,
                                     'bg-red-500': escalation.minutes <= 15
                                 }"></div>
                        </div>
                        <div class="flex-1 flex items-center justify-between bg-slate-800/50 rounded-lg px-3 py-2">
                            <span class="text-white text-sm" x-text="escalation.title"></span>
                            <span class="text-xs font-mono" 
                                  :class="{
                                      'text-green-400': escalation.minutes > 60,
                                      'text-yellow-400': escalation.minutes > 30 && escalation.minutes <= 60,
                                      'text-orange-400': escalation.minutes > 15 && escalation.minutes <= 30,
                                      'text-red-400': escalation.minutes <= 15
                                  }"
                                  x-text="`${escalation.minutes}m`"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- LIVE ACTIVITY FEED -->
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl p-4 shadow-xl">
        <h3 class="text-white font-semibold mb-3 flex items-center">
            <span class="relative flex h-3 w-3 mr-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
            Live Activity Feed
        </h3>
        <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar" x-ref="activityFeed">
            <template x-for="activity in activities" :key="activity.id">
                <div class="flex items-start space-x-2 text-sm animate-slide-in">
                    <span class="text-slate-500 font-mono text-xs" x-text="activity.time"></span>
                    <span class="text-white" x-text="activity.message"></span>
                </div>
            </template>
        </div>
    </div>
</div>

<style>
    @keyframes gradient {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .animate-gradient {
        background-size: 200% 200%;
        animation: gradient 3s ease infinite;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .animate-shake {
        animation: shake 0.5s ease-in-out infinite;
    }
    
    @keyframes slide-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(239, 68, 68, 0.5);
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(239, 68, 68, 0.7);
    }
    
    .crisis-metric-card {
        position: relative;
        overflow: hidden;
    }
    
    .crisis-metric-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: rotate(45deg);
        transition: all 0.5s;
    }
    
    .crisis-metric-card:hover::before {
        animation: shine 0.5s ease-in-out;
    }
    
    @keyframes shine {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
</style>

<script>
function urgentDashboard() {
    return {
        loading: false,
        currentTime: '',
        criticalCount: {{ $data['counts']['urgent_tickets'] ?? 0 }},
        slaBreaches: {{ $data['counts']['sla_breaches'] ?? 0 }},
        criticalTickets: {{ $data['counts']['urgent_tickets'] ?? 0 }},
        overdueInvoices: {{ $data['counts']['overdue_invoices'] ?? 0 }},
        newCritical: 0,
        avgResponse: 45,
        escalations: {{ $data['counts']['urgent_tickets'] ?? 0 }},
        nextEscalation: '12m',
        heatmapData: [],
        escalationTimeline: [],
        activities: [],
        
        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            this.generateHeatmap();
            this.generateEscalationTimeline();
            this.generateActivities();
            this.animateCounters();
            
            // Auto-refresh every 30 seconds
            setInterval(() => this.refreshData(), 30000);
        },
        
        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('en-US', { 
                hour12: false, 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
        },
        
        generateHeatmap() {
            // Generate sample heatmap data - replace with real data
            this.heatmapData = Array.from({length: 16}, (_, i) => ({
                client: `Client ${i + 1}`,
                value: Math.floor(Math.random() * 10)
            }));
        },
        
        getHeatmapColor(value) {
            if (value === 0) return 'bg-slate-700';
            if (value <= 2) return 'bg-green-500';
            if (value <= 5) return 'bg-yellow-500';
            if (value <= 7) return 'bg-orange-500';
            return 'bg-red-500';
        },
        
        generateEscalationTimeline() {
            // Sample data - replace with real tickets that need escalation
            const tickets = @json($data['urgent_tickets'] ?? collect())->slice(0, 5);
            this.escalationTimeline = tickets.map((ticket, index) => ({
                id: ticket.id,
                title: `Ticket #${ticket.id} - ${ticket.subject?.substring(0, 30) ?? 'No Subject'}`,
                minutes: Math.floor(Math.random() * 90) + 5
            }));
            
            if (this.escalationTimeline.length === 0) {
                // Default sample data if no tickets
                this.escalationTimeline = [
                    { id: 1, title: 'No pending escalations', minutes: 999 }
                ];
            }
        },
        
        generateActivities() {
            const messages = [
                'New critical ticket created',
                'SLA breach detected',
                'Ticket escalated to manager',
                'Emergency response initiated',
                'Client priority raised',
                'Urgent invoice payment received',
                'Critical system alert resolved'
            ];
            
            // Initial activities
            this.activities = [
                {
                    id: Date.now(),
                    time: new Date().toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' }),
                    message: 'Crisis Command Center initialized'
                }
            ];
            
            // Add new activity every 5 seconds
            setInterval(() => {
                const now = new Date();
                this.activities.unshift({
                    id: Date.now(),
                    time: now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' }),
                    message: messages[Math.floor(Math.random() * messages.length)]
                });
                
                // Keep only last 10 activities
                if (this.activities.length > 10) {
                    this.activities.pop();
                }
            }, 5000);
        },
        
        animateCounters() {
            // Animate number counters on load
            const counters = document.querySelectorAll('.counter');
            counters.forEach(counter => {
                const target = parseInt(counter.innerText);
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.innerText = target;
                        clearInterval(timer);
                    } else {
                        counter.innerText = Math.floor(current);
                    }
                }, 50);
            });
        },
        
        refreshData() {
            this.loading = true;
            // Simulate data refresh - replace with actual API call
            setTimeout(() => {
                this.loading = false;
                this.newCritical = Math.floor(Math.random() * 3);
                // Update next escalation time
                const minutes = Math.floor(Math.random() * 30) + 5;
                this.nextEscalation = `${minutes}m`;
            }, 1000);
        },
        
        openTicket(id) {
            window.location.href = `/tickets/${id}`;
        },
        
        toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
    }
}
</script>