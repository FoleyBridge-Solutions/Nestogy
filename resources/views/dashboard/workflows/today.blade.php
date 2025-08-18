<!-- PRODUCTIVITY HUB -->
<div x-data="todayDashboard()" x-init="init()" class="productivity-hub">
    
    <!-- PRODUCTIVITY HEADER -->
    <div class="productivity-header mb-6 relative overflow-hidden rounded-xl">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-teal-500 to-cyan-500 animate-wave"></div>
        <div class="relative px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="productivity-icon">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">PRODUCTIVITY HUB</h2>
                        <p class="text-blue-100 text-sm">
                            Your daily workflow optimized • <span x-text="tasksCompleted"></span> of <span x-text="totalTasks"></span> tasks completed
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Date Display -->
                    <div class="text-white">
                        <p class="text-xs text-blue-100">Today</p>
                        <p class="font-semibold" x-text="currentDate"></p>
                    </div>
                    <!-- Focus Mode Toggle -->
                    <button @click="toggleFocusMode()" class="text-white hover:text-blue-100 transition-colors" title="Focus Mode">
                        <svg x-show="!focusMode" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="focusMode" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PERSONAL PROGRESS SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Daily Progress Ring -->
        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-slate-800 dark:to-slate-900 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Daily Progress</h3>
            <div class="relative">
                <svg class="w-32 h-32 mx-auto transform -rotate-90">
                    <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="12" fill="none" class="text-gray-200 dark:text-gray-700"/>
                    <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="12" fill="none" 
                            :stroke-dasharray="`${progressPercentage * 3.52} 352`"
                            stroke-dashoffset="0"
                            class="text-blue-500 transition-all duration-1000 ease-out"
                            stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-slate-800 dark:text-white">
                            <span x-text="Math.round(progressPercentage)"></span>%
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Complete</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">Tasks</span>
                    <span class="font-medium text-slate-800 dark:text-white">
                        <span x-text="tasksCompleted"></span>/<span x-text="totalTasks"></span>
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">Time Tracked</span>
                    <span class="font-medium text-slate-800 dark:text-white" x-text="timeTracked"></span>
                </div>
            </div>
        </div>

        <!-- Productivity Score -->
        <div class="bg-gradient-to-br from-teal-50 to-blue-50 dark:from-slate-800 dark:to-slate-900 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Productivity Score</h3>
            <div class="space-y-4">
                <!-- Score Display -->
                <div class="text-center py-4">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 text-white shadow-lg">
                        <span class="text-2xl font-bold" x-text="productivityScore"></span>
                    </div>
                </div>
                <!-- Achievement Badges -->
                <div class="flex justify-center space-x-2">
                    <template x-for="badge in badges" :key="badge.id">
                        <div class="relative group">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center transition-transform hover:scale-110"
                                 :class="badge.earned ? 'bg-gradient-to-br from-yellow-400 to-orange-500' : 'bg-gray-300 dark:bg-gray-600'">
                                <span x-text="badge.icon"></span>
                            </div>
                            <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-slate-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                <span x-text="badge.name"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                    <span x-text="encouragementMessage"></span>
                </p>
            </div>
        </div>

        <!-- Pomodoro Timer -->
        <div class="bg-gradient-to-br from-purple-50 to-blue-50 dark:from-slate-800 dark:to-slate-900 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Focus Timer</h3>
            <div class="text-center">
                <div class="text-4xl font-bold font-mono text-slate-800 dark:text-white mb-4">
                    <span x-text="formatTime(timerMinutes)"></span>:<span x-text="formatTime(timerSeconds)"></span>
                </div>
                <div class="space-x-2">
                    <button @click="startTimer()" 
                            x-show="!timerRunning"
                            class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                        Start
                    </button>
                    <button @click="pauseTimer()" 
                            x-show="timerRunning"
                            class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors">
                        Pause
                    </button>
                    <button @click="resetTimer()" 
                            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                        Reset
                    </button>
                </div>
                <div class="mt-4 flex justify-center space-x-1">
                    <template x-for="i in 4" :key="i">
                        <div class="w-2 h-2 rounded-full"
                             :class="pomodoroSession >= i ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"></div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- TASK FLOW PIPELINE -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 mb-6" x-show="!focusMode">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Task Flow Pipeline</h3>
            <button @click="addTask()" class="text-blue-600 hover:text-blue-700 transition-colors">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- To Do Column -->
            <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-slate-700 dark:text-slate-300">To Do</h4>
                    <span class="bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-full">
                        <span x-text="todoTasks.length"></span>
                    </span>
                </div>
                <div class="space-y-2" x-ref="todoColumn" @drop="handleDrop($event, 'todo')" @dragover.prevent>
                    <template x-for="task in todoTasks" :key="task.id">
                        <div draggable="true" 
                             @dragstart="handleDragStart($event, task)"
                             @click="toggleTaskComplete(task)"
                             class="bg-white dark:bg-slate-600 p-3 rounded-lg shadow cursor-move hover:shadow-md transition-all duration-200 task-bg-white rounded-lg shadow-md overflow-hidden">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200" x-text="task.title"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400" x-text="task.client"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                      :class="{ 'bg-red-100 text-red-700': task.priority === 'high', 'bg-yellow-100 text-yellow-700': task.priority === 'medium', 'bg-green-100 text-green-700': task.priority === 'low' }"
                                      x-text="task.priority"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- In Progress Column -->
            <div class="bg-blue-50 dark:bg-slate-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-slate-700 dark:text-slate-300">In Progress</h4>
                    <span class="bg-blue-200 dark:bg-blue-600 text-blue-700 dark:text-blue-200 text-xs px-2 py-1 rounded-full">
                        <span x-text="inProgressTasks.length"></span>
                    </span>
                </div>
                <div class="space-y-2" x-ref="progressColumn" @drop="handleDrop($event, 'progress')" @dragover.prevent>
                    <template x-for="task in inProgressTasks" :key="task.id">
                        <div draggable="true"
                             @dragstart="handleDragStart($event, task)"
                             class="bg-white dark:bg-slate-600 p-3 rounded-lg shadow cursor-move hover:shadow-md transition-all duration-200 task-bg-white rounded-lg shadow-md overflow-hidden border-l-4 border-blue-500">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200" x-text="task.title"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400" x-text="task.client"></span>
                                <div class="flex items-center space-x-1">
                                    <svg class="h-3 w-3 text-blue-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span class="text-xs text-blue-600 dark:text-blue-400" x-text="task.timeSpent"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Done Column -->
            <div class="bg-green-50 dark:bg-slate-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-slate-700 dark:text-slate-300">Done</h4>
                    <span class="bg-green-200 dark:bg-green-600 text-green-700 dark:text-green-200 text-xs px-2 py-1 rounded-full">
                        <span x-text="doneTasks.length"></span>
                    </span>
                </div>
                <div class="space-y-2" x-ref="doneColumn" @drop="handleDrop($event, 'done')" @dragover.prevent>
                    <template x-for="task in doneTasks" :key="task.id">
                        <div class="bg-white dark:bg-slate-600 p-3 rounded-lg shadow opacity-75 task-card">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200 line-through" x-text="task.title"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400" x-text="task.client"></span>
                                <svg class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- TODAY'S SCHEDULE -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Time Blocks -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Today's Schedule</h3>
            <div class="space-y-2 max-h-96 overflow-y-auto custom-scrollbar">
                <template x-for="block in timeBlocks" :key="block.id">
                    <div class="flex items-start space-x-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors"
                         :class="{ 'bg-blue-50 dark:bg-blue-900/20': block.isCurrent }">
                        <div class="flex-shrink-0">
                            <p class="text-sm font-mono text-slate-600 dark:text-slate-400" x-text="block.time"></p>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 rounded-full"
                                     :class="{ 'bg-blue-500': block.type === 'ticket', 'bg-green-500': block.type === 'meeting', 'bg-purple-500': block.type === 'break', 'bg-gray-400': block.type === 'available' }"></div>
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="block.title"></p>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" x-text="block.description"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="space-y-4">
            <!-- Today's Metrics -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Today's Metrics</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $data['counts']['todays_tickets'] ?? 0 }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Tickets</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $data['counts']['scheduled_tickets'] ?? 0 }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Scheduled</p>
                    </div>
                    <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $data['counts']['my_assigned_tickets'] ?? 0 }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Assigned</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $data['counts']['todays_invoices'] ?? 0 }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Invoices</p>
                    </div>
                </div>
            </div>

            <!-- Weather Widget (for field techs) -->
            <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Current Weather</p>
                        <p class="text-2xl font-bold" x-text="weather.temp"></p>
                        <p class="text-sm opacity-90" x-text="weather.condition"></p>
                    </div>
                    <div class="text-5xl" x-text="weather.icon"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes wave {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .animate-wave {
        background-size: 200% 200%;
        animation: wave 4s ease infinite;
    }
    
    .task-card {
        transition: all 0.3s ease;
    }
    
    .task-card:hover {
        transform: translateY(-2px);
    }
    
    .task-card.dragging {
        opacity: 0.5;
        transform: rotate(5deg);
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(59, 130, 246, 0.5);
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(59, 130, 246, 0.7);
    }
</style>

<script>
function todayDashboard() {
    return {
        focusMode: false,
        currentDate: '',
        tasksCompleted: 5,
        totalTasks: 12,
        progressPercentage: 0,
        timeTracked: '3h 42m',
        productivityScore: 82,
        encouragementMessage: '',
        timerMinutes: 25,
        timerSeconds: 0,
        timerRunning: false,
        timerInterval: null,
        pomodoroSession: 2,
        draggedTask: null,
        badges: [
            { id: 1, name: 'Early Bird', icon: '🌅', earned: true },
            { id: 2, name: 'Streak Master', icon: '🔥', earned: true },
            { id: 3, name: 'Focus Champion', icon: '🎯', earned: false },
            { id: 4, name: 'Team Player', icon: '🤝', earned: true },
            { id: 5, name: 'Speed Demon', icon: '⚡', earned: false }
        ],
        todoTasks: [],
        inProgressTasks: [],
        doneTasks: [],
        timeBlocks: [],
        weather: {
            temp: '72°F',
            condition: 'Partly Cloudy',
            icon: '⛅'
        },
        
        init() {
            this.setCurrentDate();
            this.calculateProgress();
            this.generateTasks();
            this.generateTimeBlocks();
            this.setEncouragementMessage();
            
            // Update progress animation
            setTimeout(() => {
                this.progressPercentage = (this.tasksCompleted / this.totalTasks) * 100;
            }, 100);
        },
        
        setCurrentDate() {
            const now = new Date();
            this.currentDate = now.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        },
        
        calculateProgress() {
            this.progressPercentage = (this.tasksCompleted / this.totalTasks) * 100;
        },
        
        generateTasks() {
            // Sample tasks - replace with real data
            this.todoTasks = [
                { id: 1, title: 'Review server logs', client: 'Acme Corp', priority: 'high' },
                { id: 2, title: 'Update firewall rules', client: 'TechStart', priority: 'medium' },
                { id: 3, title: 'Backup verification', client: 'Global Inc', priority: 'low' }
            ];
            
            this.inProgressTasks = [
                { id: 4, title: 'Troubleshoot VPN', client: 'Acme Corp', priority: 'high', timeSpent: '45m' },
                { id: 5, title: 'Deploy updates', client: 'StartupXYZ', priority: 'medium', timeSpent: '1h 20m' }
            ];
            
            this.doneTasks = [
                { id: 6, title: 'Reset passwords', client: 'Local Business', priority: 'low' },
                { id: 7, title: 'Install antivirus', client: 'Retail Plus', priority: 'medium' },
                { id: 8, title: 'Configure email', client: 'Law Firm LLC', priority: 'high' }
            ];
        },
        
        generateTimeBlocks() {
            const blocks = [
                { id: 1, time: '09:00', title: 'Daily Standup', description: 'Team sync meeting', type: 'meeting', isCurrent: false },
                { id: 2, time: '09:30', title: 'Server Maintenance', description: 'Acme Corp - Scheduled', type: 'ticket', isCurrent: false },
                { id: 3, time: '11:00', title: 'VPN Troubleshooting', description: 'High Priority', type: 'ticket', isCurrent: true },
                { id: 4, time: '12:00', title: 'Lunch Break', description: 'Recharge time', type: 'break', isCurrent: false },
                { id: 5, time: '13:00', title: 'Client Meeting', description: 'New project discussion', type: 'meeting', isCurrent: false },
                { id: 6, time: '14:30', title: 'Available', description: 'Open for urgent tasks', type: 'available', isCurrent: false },
                { id: 7, time: '16:00', title: 'Documentation', description: 'Update procedures', type: 'ticket', isCurrent: false }
            ];
            
            this.timeBlocks = blocks;
        },
        
        setEncouragementMessage() {
            const messages = [
                "Great progress! Keep up the momentum! 🚀",
                "You're crushing it today! 💪",
                "Fantastic work! Almost there! 🎯",
                "Productivity champion in action! ⭐",
                "Keep going, you're doing amazing! 🌟"
            ];
            this.encouragementMessage = messages[Math.floor(Math.random() * messages.length)];
        },
        
        toggleFocusMode() {
            this.focusMode = !this.focusMode;
        },
        
        formatTime(value) {
            return value.toString().padStart(2, '0');
        },
        
        startTimer() {
            this.timerRunning = true;
            this.timerInterval = setInterval(() => {
                if (this.timerSeconds === 0) {
                    if (this.timerMinutes === 0) {
                        this.pauseTimer();
                        this.playSound();
                        this.pomodoroSession++;
                        alert('Pomodoro session complete! Take a break.');
                        return;
                    }
                    this.timerMinutes--;
                    this.timerSeconds = 59;
                } else {
                    this.timerSeconds--;
                }
            }, 1000);
        },
        
        pauseTimer() {
            this.timerRunning = false;
            clearInterval(this.timerInterval);
        },
        
        resetTimer() {
            this.pauseTimer();
            this.timerMinutes = 25;
            this.timerSeconds = 0;
        },
        
        playSound() {
            // Play notification sound
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXr