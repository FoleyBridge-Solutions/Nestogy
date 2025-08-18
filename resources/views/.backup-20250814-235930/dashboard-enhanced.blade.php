@extends('layouts.app')

@section('content')
<div 
    x-data="executiveDashboard()" 
    x-init="init()"
    class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800"
>
    <!-- Dashboard Header -->
    <header class="sticky top-0 z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl shadow-sm border-b border-slate-200/50 dark:border-slate-700/50">
        <div class="px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <!-- Title & Status -->
                <div class="flex items-center space-x-6">
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400 bg-clip-text text-transparent">
                            Executive Dashboard
                        </h1>
                        <div class="flex items-center mt-1 text-sm text-slate-500 dark:text-slate-400">
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse mr-2"></span>
                                <span>Live Data</span>
                            </span>
                            <span class="mx-3">•</span>
                            <span x-text="lastUpdated"></span>
                        </div>
                    </div>
                </div>

                <!-- Header Controls -->
                <div class="flex items-center space-x-3">
                    <!-- Preset Selector -->
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open"
                            class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center space-x-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                            <span>Layouts</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div 
                            x-show="open" 
                            x-transition
                            @click.outside="open = false"
                            class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50"
                        >
                            <template x-for="preset in presets" :key="preset.id">
                                <button 
                                    @click="applyPreset(preset); open = false"
                                    class="w-full px-4 py-2 text-left text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                                >
                                    <div class="font-medium" x-text="preset.name"></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400" x-text="preset.description"></div>
                                </button>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Widget Library -->
                    <button 
                        @click="showWidgetLibrary = true"
                        class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Add Widget</span>
                    </button>
                    
                    <!-- Edit Mode Toggle -->
                    <button 
                        @click="toggleEditMode()"
                        :class="editMode ? 'bg-blue-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300'"
                        class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center space-x-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        <span x-text="editMode ? 'Done Editing' : 'Customize'"></span>
                    </button>
                    
                    <!-- Settings -->
                    <button 
                        @click="showSettings = true"
                        class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Widget Grid Container -->
    <main class="p-4 sm:p-6 lg:p-8">
        <div 
            id="widget-grid"
            class="grid gap-4"
            :style="`grid-template-columns: repeat(${gridColumns}, 1fr);`"
        >
            <template x-for="widget in widgets" :key="widget.id">
                <div 
                    :id="`widget-${widget.id}`"
                    :style="`grid-column: span ${widget.w}; grid-row: span ${widget.h};`"
                    class="widget-container mx-auto px-4 mx-auto px-4 relative group"
                    :class="editMode ? 'cursor-move' : ''"
                >
                    <!-- Widget Controls (visible in edit mode) -->
                    <div 
                        x-show="editMode"
                        x-transition
                        class="absolute top-2 right-2 z-10 flex space-x-1"
                    >
                        <button 
                            @click="configureWidget(widget)"
                            class="p-1 bg-white dark:bg-slate-800 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </button>
                        <button 
                            @click="removeWidget(widget)"
                            class="p-1 bg-white dark:bg-slate-800 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Widget Resize Handle (visible in edit mode) -->
                    <div 
                        x-show="editMode"
                        class="absolute bottom-0 right-0 w-4 h-4 cursor-se-resize opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                        </svg>
                    </div>
                    
                    <!-- Widget Content -->
                    <div 
                        class="h-full bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden"
                        :class="widget.loading ? 'animate-pulse' : ''"
                    >
                        <div x-html="renderWidget(widget)"></div>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Empty State -->
        <div 
            x-show="widgets.length === 0"
            class="flex flex-col items-center justify-center h-96 text-slate-500 dark:text-slate-400"
        >
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
            </svg>
            <h3 class="text-lg font-medium mb-2">No widgets added yet</h3>
            <p class="text-sm mb-4">Start by adding widgets from the library</p>
            <button 
                @click="showWidgetLibrary = true"
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
            >
                Add Your First Widget
            </button>
        </div>
    </main>

    <!-- Widget Library Modal -->
    <div 
        x-show="showWidgetLibrary"
        x-transition
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
        @click.self="showWidgetLibrary = false"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[80vh] overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Widget Library</h2>
                    <button 
                        @click="showWidgetLibrary = false"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="widgetType in availableWidgets" :key="widgetType.type">
                        <button 
                            @click="addWidget(widgetType)"
                            class="p-4 bg-slate-50 dark:bg-slate-700 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-600 transition-all hover:scale-105 text-left"
                        >
                            <div class="flex items-start space-x-3">
                                <div 
                                    class="w-10 h-10 rounded-lg flex items-center justify-center"
                                    :class="widgetType.color"
                                >
                                    <span x-html="widgetType.icon"></span>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium text-slate-900 dark:text-white" x-text="widgetType.name"></h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1" x-text="widgetType.description"></p>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div 
        x-show="showSettings"
        x-transition
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
        @click.self="showSettings = false"
    >
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Dashboard Settings</h2>
                    <button 
                        @click="showSettings = false"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6 space-y-4">
                <!-- Refresh Interval -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Refresh Interval
                    </label>
                    <select 
                        x-model="settings.refreshInterval"
                        @change="updateRefreshInterval()"
                        class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                    >
                        <option value="10">10 seconds</option>
                        <option value="30">30 seconds</option>
                        <option value="60">1 minute</option>
                        <option value="300">5 minutes</option>
                        <option value="0">Manual only</option>
                    </select>
                </div>
                
                <!-- Theme -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Theme
                    </label>
                    <div class="flex space-x-2">
                        <button 
                            @click="setTheme('light')"
                            :class="settings.theme === 'light' ? 'bg-blue-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                            class="flex-1 px-3 py-2 rounded-lg transition-colors"
                        >
                            Light
                        </button>
                        <button 
                            @click="setTheme('dark')"
                            :class="settings.theme === 'dark' ? 'bg-blue-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                            class="flex-1 px-3 py-2 rounded-lg transition-colors"
                        >
                            Dark
                        </button>
                        <button 
                            @click="setTheme('auto')"
                            :class="settings.theme === 'auto' ? 'bg-blue-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                            class="flex-1 px-3 py-2 rounded-lg transition-colors"
                        >
                            Auto
                        </button>
                    </div>
                </div>
                
                <!-- Grid Columns -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                        Grid Columns
                    </label>
                    <input 
                        type="range"
                        x-model="gridColumns"
                        min="6"
                        max="24"
                        step="2"
                        class="w-full"
                    >
                    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mt-1">
                        <span>6</span>
                        <span x-text="gridColumns"></span>
                        <span>24</span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="pt-4 space-y-2">
                    <button 
                        @click="exportConfiguration()"
                        class="w-full px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                    >
                        Export Configuration
                    </button>
                    <button 
                        @click="importConfiguration()"
                        class="w-full px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                    >
                        Import Configuration
                    </button>
                    <button 
                        @click="resetToDefault()"
                        class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                    >
                        Reset to Default
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="{{ asset('js/dashboard-widgets.js') }}"></script>
@endpush