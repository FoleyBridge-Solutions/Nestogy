@extends('layouts.app')

@section('content')
<div class="container-fluid px-6 py-4">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Knowledge Base</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Solutions and articles for common issues</p>
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <flux:icon name="plus" class="w-4 h-4 inline mr-1"/> New Article
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <div class="relative">
            <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400"/>
            <input type="text" 
                   placeholder="Search knowledge base articles..." 
                   class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
            <div class="absolute right-3 top-1/2 -translate-y-1/2">
                <kbd class="px-2 py-1 text-xs font-semibold text-gray-500 bg-gray-100 dark:bg-gray-600 dark:text-gray-300 rounded">⌘K</kbd>
            </div>
        </div>
        
        <!-- Quick Filters -->
        <div class="flex gap-2 mt-4">
            <span class="text-sm text-gray-600 dark:text-gray-400">Popular:</span>
            <button class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                Password Reset
            </button>
            <button class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                Email Setup
            </button>
            <button class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                VPN Connection
            </button>
            <button class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                Printer Issues
            </button>
            <button class="px-3 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                Software Installation
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Categories Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-white mb-4">Categories</h3>
                <nav class="space-y-1">
                    @php
                        $categories = [
                            ['name' => 'All Articles', 'count' => 245, 'icon' => 'book-open', 'active' => true],
                            ['name' => 'Getting Started', 'count' => 12, 'icon' => 'rocket-launch'],
                            ['name' => 'Account & Billing', 'count' => 28, 'icon' => 'credit-card'],
                            ['name' => 'Email & Communication', 'count' => 34, 'icon' => 'envelope'],
                            ['name' => 'Security', 'count' => 19, 'icon' => 'shield-check'],
                            ['name' => 'Networking', 'count' => 42, 'icon' => 'wifi'],
                            ['name' => 'Software & Applications', 'count' => 56, 'icon' => 'computer-desktop'],
                            ['name' => 'Hardware', 'count' => 31, 'icon' => 'cpu-chip'],
                            ['name' => 'Mobile Devices', 'count' => 23, 'icon' => 'device-phone-mobile'],
                        ];
                    @endphp
                    @foreach($categories as $category)
                        <a href="#" class="flex items-center justify-between px-3 py-2 text-sm rounded-lg 
                           {{ $category['active'] ?? false ? 'bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <div class="flex items-center">
                                <flux:icon name="{{ $category['icon'] }}" class="h-4 w-4 mr-2"/>
                                {{ $category['name'] }}
                            </div>
                            <span class="text-xs {{ $category['active'] ?? false ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400' }}">
                                {{ $category['count'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <!-- Quick Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 mt-4 border border-gray-200 dark:border-gray-700">
                <h3 class="font-medium text-gray-900 dark:text-white mb-4">Statistics</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Articles Used Today</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">142</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Tickets Deflected</p>
                        <p class="text-xl font-semibold text-green-600 dark:text-green-400">38</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Avg. Helpfulness</p>
                        <div class="flex items-center">
                            <p class="text-xl font-semibold text-gray-900 dark:text-white mr-2">4.6</p>
                            <div class="flex">
                                @for($i = 0; $i < 5; $i++)
                                    <flux:icon name="star" class="h-4 w-4 {{ $i < 4 ? 'text-yellow-400' : 'text-gray-300' }}"/>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Articles Grid -->
        <div class="lg:col-span-3">
            <!-- Featured Articles -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Featured Solutions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $featuredArticles = [
                            [
                                'title' => 'How to Reset Your Password',
                                'category' => 'Account & Billing',
                                'description' => 'Step-by-step guide to resetting your account password securely',
                                'views' => 1842,
                                'helpful' => 95,
                                'icon' => 'key'
                            ],
                            [
                                'title' => 'Setting Up Email on Mobile Devices',
                                'category' => 'Email & Communication',
                                'description' => 'Configure your email account on iOS and Android devices',
                                'views' => 1523,
                                'helpful' => 92,
                                'icon' => 'device-phone-mobile'
                            ]
                        ];
                    @endphp
                    @foreach($featuredArticles as $article)
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border border-blue-200 dark:border-gray-600">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                        <flux:icon name="{{ $article['icon'] }}" class="h-6 w-6 text-blue-600 dark:text-blue-400"/>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">{{ $article['title'] }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $article['category'] }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ $article['description'] }}</p>
                                    <div class="flex items-center mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        <flux:icon name="eye" class="h-3 w-3 mr-1"/>
                                        {{ number_format($article['views']) }} views
                                        <span class="mx-2">•</span>
                                        <flux:icon name="hand-thumb-up" class="h-3 w-3 mr-1"/>
                                        {{ $article['helpful'] }}% helpful
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Articles -->
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">All Articles</h2>
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $articles = [
                            ['title' => 'Troubleshooting VPN Connection Issues', 'category' => 'Networking', 'updated' => '2 hours ago', 'author' => 'John Smith'],
                            ['title' => 'Understanding Two-Factor Authentication', 'category' => 'Security', 'updated' => '5 hours ago', 'author' => 'Sarah Johnson'],
                            ['title' => 'Microsoft Office Installation Guide', 'category' => 'Software & Applications', 'updated' => '1 day ago', 'author' => 'Mike Wilson'],
                            ['title' => 'Printer Driver Installation and Setup', 'category' => 'Hardware', 'updated' => '2 days ago', 'author' => 'Emily Davis'],
                            ['title' => 'Email Signature Configuration', 'category' => 'Email & Communication', 'updated' => '3 days ago', 'author' => 'Tom Brown'],
                            ['title' => 'Remote Desktop Connection Setup', 'category' => 'Networking', 'updated' => '4 days ago', 'author' => 'Lisa Anderson'],
                            ['title' => 'Backup and Recovery Best Practices', 'category' => 'Security', 'updated' => '5 days ago', 'author' => 'Chris Martinez'],
                            ['title' => 'Managing Calendar Permissions', 'category' => 'Email & Communication', 'updated' => '1 week ago', 'author' => 'Jessica Taylor'],
                        ];
                    @endphp
                    @foreach($articles as $article)
                        <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $article['title'] }}
                                    </h3>
                                    <div class="flex items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ $article['category'] }}
                                        </span>
                                        <span class="mx-2">•</span>
                                        <span>Updated {{ $article['updated'] }}</span>
                                        <span class="mx-2">•</span>
                                        <span>by {{ $article['author'] }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                        <flux:icon name="link" class="h-4 w-4"/>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400">
                                        <flux:icon name="document-duplicate" class="h-4 w-4"/>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Showing 1 to 8 of 245 articles
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50" disabled>
                            Previous
                        </button>
                        <button class="px-3 py-1 text-sm text-white bg-blue-600 rounded hover:bg-blue-700">
                            1
                        </button>
                        <button class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                            2
                        </button>
                        <button class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                            3
                        </button>
                        <button class="px-3 py-1 text-sm text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection