@extends('layouts.settings')

@section('title', 'Knowledge Base Settings - Nestogy')

@section('settings-title', 'Knowledge Base Settings')
@section('settings-description', 'Configure knowledge base functionality, content management, and user engagement features')

@section('settings-content')
<div x-data="{ activeTab: 'core' }">
    <form method="POST" action="{{ route('settings.knowledge-base.update') }}">
        @csrf
        @method('PUT')

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6 pt-4 overflow-x-auto">
                <button type="button" 
                        @click="activeTab = 'core'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'core', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'core'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Core Settings
                </button>
                <button type="button" 
                        @click="activeTab = 'content'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'content', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'content'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Content Management
                </button>
                <button type="button" 
                        @click="activeTab = 'search'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'search', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'search'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Search & Discovery
                </button>
                <button type="button" 
                        @click="activeTab = 'engagement'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'engagement', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'engagement'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    User Engagement
                </button>
                <button type="button" 
                        @click="activeTab = 'ai'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'ai', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'ai'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    AI & Automation
                </button>
                <button type="button" 
                        @click="activeTab = 'analytics'"
                        :class="{'border-blue-500 text-blue-600 dark:text-blue-400': activeTab === 'analytics', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'analytics'}"
                        class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Analytics & Reporting
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Core Settings Tab -->
            <div x-show="activeTab === 'core'" x-transition>
                <div class="space-y-6">
                    <!-- Knowledge Base Core Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                                Knowledge Base Core Settings
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="knowledge_base_enabled" 
                                           name="knowledge_base_enabled" 
                                           value="1"
                                           {{ old('knowledge_base_enabled', $setting->knowledge_base_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Enable Knowledge Base</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="public_knowledge_base" 
                                           name="public_knowledge_base" 
                                           value="1"
                                           {{ old('public_knowledge_base', $setting->public_knowledge_base ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Make Knowledge Base Public</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="knowledge_base_moderation" 
                                           name="knowledge_base_moderation" 
                                           value="1"
                                           {{ old('knowledge_base_moderation', $setting->knowledge_base_moderation ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Enable Content Moderation</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Management Tab -->
            <div x-show="activeTab === 'content'" x-transition>
                <div class="space-y-6">
                    <!-- Content Management -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Content Management
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="article_versioning_enabled" 
                                           name="article_versioning_enabled" 
                                           value="1"
                                           {{ old('article_versioning_enabled', $setting->article_versioning_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Article Versioning</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="collaborative_editing" 
                                           name="collaborative_editing" 
                                           value="1"
                                           {{ old('collaborative_editing', $setting->collaborative_editing ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Collaborative Editing</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="content_approval_workflow" 
                                           name="content_approval_workflow" 
                                           value="1"
                                           {{ old('content_approval_workflow', $setting->content_approval_workflow ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Content Approval Workflow</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Discovery Tab -->
            <div x-show="activeTab === 'search'" x-transition>
                <div class="space-y-6">
                    <!-- Search & Discovery -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Search & Discovery
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="full_text_search_enabled" 
                                           name="full_text_search_enabled" 
                                           value="1"
                                           {{ old('full_text_search_enabled', $setting->full_text_search_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Full-Text Search</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="search_analytics_enabled" 
                                           name="search_analytics_enabled" 
                                           value="1"
                                           {{ old('search_analytics_enabled', $setting->search_analytics_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Search Analytics</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="auto_categorization_enabled" 
                                           name="auto_categorization_enabled" 
                                           value="1"
                                           {{ old('auto_categorization_enabled', $setting->auto_categorization_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Auto-Categorization</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Engagement Tab -->
            <div x-show="activeTab === 'engagement'" x-transition>
                <div class="space-y-6">
                    <!-- User Engagement -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                User Engagement
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="article_ratings_enabled" 
                                           name="article_ratings_enabled" 
                                           value="1"
                                           {{ old('article_ratings_enabled', $setting->article_ratings_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Article Ratings</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="comments_enabled" 
                                           name="comments_enabled" 
                                           value="1"
                                           {{ old('comments_enabled', $setting->comments_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Comments</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="social_sharing_enabled" 
                                           name="social_sharing_enabled" 
                                           value="1"
                                           {{ old('social_sharing_enabled', $setting->social_sharing_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Social Sharing</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI & Automation Tab -->
            <div x-show="activeTab === 'ai'" x-transition>
                <div class="space-y-6">
                    <!-- AI & Automation -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                AI & Automation
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="ai_content_suggestions" 
                                           name="ai_content_suggestions" 
                                           value="1"
                                           {{ old('ai_content_suggestions', $setting->ai_content_suggestions ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable AI Content Suggestions</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="auto_ticket_to_kb_conversion" 
                                           name="auto_ticket_to_kb_conversion" 
                                           value="1"
                                           {{ old('auto_ticket_to_kb_conversion', $setting->auto_ticket_to_kb_conversion ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Auto-Convert Tickets to KB Articles</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="smart_content_recommendations" 
                                           name="smart_content_recommendations" 
                                           value="1"
                                           {{ old('smart_content_recommendations', $setting->smart_content_recommendations ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Smart Content Recommendations</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics & Reporting Tab -->
            <div x-show="activeTab === 'analytics'" x-transition>
                <div class="space-y-6">
                    <!-- Analytics & Reporting -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Analytics & Reporting
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="usage_analytics_enabled" 
                                           name="usage_analytics_enabled" 
                                           value="1"
                                           {{ old('usage_analytics_enabled', $setting->usage_analytics_enabled ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Usage Analytics</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="content_performance_tracking" 
                                           name="content_performance_tracking" 
                                           value="1"
                                           {{ old('content_performance_tracking', $setting->content_performance_tracking ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Track Content Performance</span>
                                </label>
                                
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           id="knowledge_gap_analysis" 
                                           name="knowledge_gap_analysis" 
                                           value="1"
                                           {{ old('knowledge_gap_analysis', $setting->knowledge_gap_analysis ?? false) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-3 text-sm text-gray-700">Enable Knowledge Gap Analysis</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('settings.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 dark:bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-700">Save Settings</button>
        </div>
    </form>
</div>
@endsection
