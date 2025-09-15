@extends('layouts.app')

@section('title', $asset->name . ' - Asset Management')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4">
                    {{-- Status Indicator --}}
                    <div class="flex-shrink-0 mt-1">
                        <div class="relative">
                            <span class="flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                        </div>
                    </div>

                    {{-- Asset Info --}}
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $asset->name }}</h1>
                            <flux:badge color="blue">{{ $asset->type }}</flux:badge>
                            <flux:badge color="green">Online</flux:badge>
                        </div>
                        <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center">
                                <flux:icon.building-office class="w-4 h-4 mr-1" />
                                {{ $asset->client->name ?? 'No Client' }}
                            </span>
                            <span class="flex items-center">
                                <flux:icon.map-pin class="w-4 h-4 mr-1" />
                                {{ $asset->location->name ?? 'No Location' }}
                            </span>
                            <span class="flex items-center">
                                <flux:icon.clock class="w-4 h-4 mr-1" />
                                Last seen: 2 minutes ago
                            </span>
                            <span class="flex items-center">
                                <flux:icon.server class="w-4 h-4 mr-1" />
                                Agent v3.1.4
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex items-center space-x-2">
                    <flux:button variant="primary" size="sm" icon="arrow-top-right-on-square">
                        Remote Access
                    </flux:button>
                    <flux:button variant="ghost" size="sm" icon="arrow-path">
                        Reboot
                    </flux:button>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="command-line">Run Script</flux:menu.item>
                            <flux:menu.item icon="arrow-down-tray">Deploy Software</flux:menu.item>
                            <flux:menu.item icon="power">Shutdown</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="pencil">Edit Asset</flux:menu.item>
                            <flux:menu.item icon="qr-code">View QR Code</flux:menu.item>
                            <flux:menu.item icon="printer">Print Label</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item icon="archive-box">Archive</flux:menu.item>
                            <flux:menu.item icon="trash" variant="danger">Delete</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <flux:tabs default="overview">
                <flux:tab.list class="border-b border-gray-200 dark:border-gray-700">
                    <flux:tab name="overview">Overview</flux:tab>
                    <flux:tab name="monitoring">Monitoring</flux:tab>
                    <flux:tab name="software">Software</flux:tab>
                    <flux:tab name="hardware">Hardware</flux:tab>
                    <flux:tab name="security">Security</flux:tab>
                    <flux:tab name="tools">Tools</flux:tab>
                    <flux:tab name="automation">Automation</flux:tab>
                    <flux:tab name="history">History</flux:tab>
                </flux:tab.list>

                {{-- Overview Tab --}}
                <flux:tab.panel name="overview" class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- System Information --}}
                        <div class="lg:col-span-2 space-y-6">
                            <flux:card>
                                <flux:heading size="lg">System Information</flux:heading>
                                <div class="mt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Operating System</p>
                                        <p class="font-medium">{{ $asset->os ?? 'Windows 11 Pro' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Processor</p>
                                        <p class="font-medium">Intel Core i7-10700K @ 3.80GHz</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Memory</p>
                                        <p class="font-medium">32 GB DDR4</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Storage</p>
                                        <p class="font-medium">1 TB NVMe SSD</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">IP Address</p>
                                        <p class="font-medium">{{ $asset->ip ?? '192.168.1.100' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">MAC Address</p>
                                        <p class="font-medium">{{ $asset->mac ?? '00:1B:44:11:3A:B7' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Serial Number</p>
                                        <p class="font-medium">{{ $asset->serial ?? 'PC-2024-001' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Model</p>
                                        <p class="font-medium">{{ $asset->model ?? 'OptiPlex 7080' }}</p>
                                    </div>
                                </div>
                            </flux:card>

                            {{-- Recent Activity Timeline --}}
                            <flux:card>
                                <flux:heading size="lg">Recent Activity</flux:heading>
                                <div class="mt-4 flow-root">
                                    <ul role="list" class="-mb-8">
                                        <li>
                                            <div class="relative pb-8">
                                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                            <flux:icon.check class="h-5 w-5 text-white" />
                                                        </span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                        <div>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">Windows Updates installed successfully</p>
                                                        </div>
                                                        <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                            <time datetime="2024-01-15">1 hour ago</time>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="relative pb-8">
                                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                            <flux:icon.arrow-path class="h-5 w-5 text-white" />
                                                        </span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                        <div>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">System reboot completed</p>
                                                        </div>
                                                        <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                            <time datetime="2024-01-15">3 hours ago</time>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="relative pb-8">
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                            <flux:icon.exclamation-triangle class="h-5 w-5 text-white" />
                                                        </span>
                                                    </div>
                                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                        <div>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">Low disk space warning (C: drive at 85%)</p>
                                                        </div>
                                                        <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                            <time datetime="2024-01-15">5 hours ago</time>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </flux:card>
                        </div>

                        {{-- Performance Widgets --}}
                        <div class="space-y-6">
                            {{-- CPU Usage --}}
                            <flux:card>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">CPU Usage</span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">45%</span>
                                </div>
                                <div class="relative pt-1">
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                                        <div style="width:45%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    4 cores @ 3.80 GHz
                                </div>
                            </flux:card>

                            {{-- Memory Usage --}}
                            <flux:card>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memory Usage</span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">68%</span>
                                </div>
                                <div class="relative pt-1">
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                                        <div style="width:68%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    21.8 GB / 32 GB used
                                </div>
                            </flux:card>

                            {{-- Disk Usage --}}
                            <flux:card>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Disk Usage (C:)</span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">85%</span>
                                </div>
                                <div class="relative pt-1">
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                                        <div style="width:85%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-yellow-500"></div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    850 GB / 1 TB used
                                </div>
                            </flux:card>

                            {{-- Network Activity --}}
                            <flux:card>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Network Activity</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500 dark:text-gray-400">Download</span>
                                        <span class="font-medium text-gray-900 dark:text-white">2.4 MB/s</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-500 dark:text-gray-400">Upload</span>
                                        <span class="font-medium text-gray-900 dark:text-white">0.8 MB/s</span>
                                    </div>
                                </div>
                            </flux:card>

                            {{-- System Uptime --}}
                            <flux:card>
                                <div class="text-center">
                                    <flux:icon.clock class="h-8 w-8 text-gray-400 mx-auto mb-2" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">System Uptime</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">7 days, 14 hours</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Last boot: Jan 8, 2024</p>
                                </div>
                            </flux:card>
                        </div>
                    </div>
                </flux:tab.panel>

                {{-- Monitoring Tab --}}
                <flux:tab.panel name="monitoring" class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Active Monitors --}}
                        <flux:card>
                            <flux:heading size="lg">Active Monitors</flux:heading>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">CPU Temperature</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Threshold: > 80°C</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">65°C</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Normal</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex h-2 w-2 rounded-full bg-yellow-500"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Disk Space</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Threshold: < 20% free</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">15% free</p>
                                        <p class="text-xs text-yellow-600 dark:text-yellow-400">Warning</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Service Monitor</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Windows Update Service</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">Running</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Healthy</p>
                                    </div>
                                </div>
                            </div>
                        </flux:card>

                        {{-- Current Alerts --}}
                        <flux:card>
                            <flux:heading size="lg">Active Alerts</flux:heading>
                            <div class="mt-4 space-y-3">
                                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <flux:icon.exclamation-triangle class="h-5 w-5 text-yellow-400" />
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Low Disk Space</h3>
                                            <div class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                                                <p>C: drive has only 150 GB free (15% remaining)</p>
                                            </div>
                                            <div class="mt-2">
                                                <flux:button size="xs" variant="ghost">Acknowledge</flux:button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <flux:icon.information-circle class="h-5 w-5 text-blue-400" />
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Windows Updates Available</h3>
                                            <div class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                                                <p>3 important updates are ready to install</p>
                                            </div>
                                            <div class="mt-2">
                                                <flux:button size="xs" variant="ghost">View Updates</flux:button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </flux:card>

                        {{-- Performance Graph Placeholder --}}
                        <div class="lg:col-span-2">
                            <flux:card>
                                <flux:heading size="lg">Performance History</flux:heading>
                                <div class="mt-4 h-64 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                    <p class="text-gray-500 dark:text-gray-400">Performance graph would be rendered here</p>
                                </div>
                            </flux:card>
                        </div>
                    </div>
                </flux:tab.panel>

                {{-- Software Tab --}}
                <flux:tab.panel name="software" class="p-6">
                    <div class="space-y-6">
                        {{-- Software Actions Bar --}}
                        <div class="flex items-center justify-between">
                            <flux:input type="search" placeholder="Search installed software..." class="w-64" />
                            <div class="flex space-x-2">
                                <flux:button variant="primary" icon="arrow-down-tray">Deploy Software</flux:button>
                                <flux:button variant="ghost" icon="arrow-path">Check for Updates</flux:button>
                            </div>
                        </div>

                        {{-- Installed Applications Table --}}
                        <flux:card>
                            <flux:heading size="lg">Installed Applications</flux:heading>
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead>
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Version</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Publisher</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Install Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                                            <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Microsoft Office Professional</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">2021</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Microsoft Corporation</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Jan 5, 2024</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">2.8 GB</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <flux:button size="xs" variant="ghost">Uninstall</flux:button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Google Chrome</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">120.0.6099.130</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Google LLC</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Jan 10, 2024</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">385 MB</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <flux:button size="xs" variant="ghost">Uninstall</flux:button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Adobe Acrobat Reader</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">2023.008.20470</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Adobe Inc.</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Dec 15, 2023</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">580 MB</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <flux:button size="xs" variant="ghost">Uninstall</flux:button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </flux:card>

                        {{-- Windows Updates --}}
                        <flux:card>
                            <flux:heading size="lg">Windows Updates</flux:heading>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">2024-01 Cumulative Update for Windows 11</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">KB5034123 • 680 MB • Important</p>
                                    </div>
                                    <flux:button size="sm" variant="primary">Install</flux:button>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">.NET Framework 4.8.1 Update</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">KB5034124 • 120 MB • Recommended</p>
                                    </div>
                                    <flux:button size="sm" variant="primary">Install</flux:button>
                                </div>
                            </div>
                        </flux:card>
                    </div>
                </flux:tab.panel>

                {{-- Hardware Tab --}}
                <flux:tab.panel name="hardware" class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Device Categories --}}
                        <flux:card>
                            <flux:heading size="lg">Hardware Components</flux:heading>
                            <div class="mt-4 space-y-2">
                                <details class="group">
                                    <summary class="flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Processors</span>
                                        <flux:icon.chevron-right class="h-4 w-4 text-gray-400 group-open:rotate-90 transition-transform" />
                                    </summary>
                                    <div class="ml-4 mt-2 space-y-1">
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <flux:icon.cpu-chip class="h-4 w-4" />
                                            <span>Intel Core i7-10700K @ 3.80GHz</span>
                                        </div>
                                    </div>
                                </details>
                                <details class="group">
                                    <summary class="flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Memory</span>
                                        <flux:icon.chevron-right class="h-4 w-4 text-gray-400 group-open:rotate-90 transition-transform" />
                                    </summary>
                                    <div class="ml-4 mt-2 space-y-1">
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <flux:icon.rectangle-stack class="h-4 w-4" />
                                            <span>32 GB DDR4 3200MHz (2x16GB)</span>
                                        </div>
                                    </div>
                                </details>
                                <details class="group">
                                    <summary class="flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Storage</span>
                                        <flux:icon.chevron-right class="h-4 w-4 text-gray-400 group-open:rotate-90 transition-transform" />
                                    </summary>
                                    <div class="ml-4 mt-2 space-y-1">
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <flux:icon.circle-stack class="h-4 w-4" />
                                            <span>Samsung 980 PRO NVMe SSD 1TB</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <flux:icon.circle-stack class="h-4 w-4" />
                                            <span>WD Blue 2TB SATA HDD</span>
                                        </div>
                                    </div>
                                </details>
                                <details class="group">
                                    <summary class="flex items-center justify-between cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Network Adapters</span>
                                        <flux:icon.chevron-right class="h-4 w-4 text-gray-400 group-open:rotate-90 transition-transform" />
                                    </summary>
                                    <div class="ml-4 mt-2 space-y-1">
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <flux:icon.wifi class="h-4 w-4" />
                                            <span>Intel Wi-Fi 6 AX200</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                            <flux:icon.globe-alt class="h-4 w-4" />
                                            <span>Realtek PCIe GbE Family Controller</span>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </flux:card>

                        {{-- Storage Details --}}
                        <flux:card>
                            <flux:heading size="lg">Storage Drives</flux:heading>
                            <div class="mt-4 space-y-4">
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">C: (System)</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">850 GB / 1 TB</span>
                                    </div>
                                    <div class="relative pt-1">
                                        <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-600">
                                            <div style="width:85%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-yellow-500"></div>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <span>NTFS • Samsung 980 PRO</span>
                                        <span>150 GB free</span>
                                    </div>
                                </div>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">D: (Data)</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">1.2 TB / 2 TB</span>
                                    </div>
                                    <div class="relative pt-1">
                                        <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-600">
                                            <div style="width:60%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <span>NTFS • WD Blue HDD</span>
                                        <span>800 GB free</span>
                                    </div>
                                </div>
                            </div>
                        </flux:card>
                    </div>
                </flux:tab.panel>

                {{-- Security Tab --}}
                <flux:tab.panel name="security" class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {{-- Protection Status Cards --}}
                        <flux:card class="text-center">
                            <flux:icon.shield-check class="h-12 w-12 text-green-500 mx-auto mb-2" />
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Antivirus</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Windows Defender</p>
                            <flux:badge color="green" class="mt-2">Protected</flux:badge>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Last scan: 2 hours ago</p>
                        </flux:card>

                        <flux:card class="text-center">
                            <flux:icon.fire class="h-12 w-12 text-green-500 mx-auto mb-2" />
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Firewall</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Windows Firewall</p>
                            <flux:badge color="green" class="mt-2">Enabled</flux:badge>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">All profiles active</p>
                        </flux:card>

                        <flux:card class="text-center">
                            <flux:icon.lock-closed class="h-12 w-12 text-green-500 mx-auto mb-2" />
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">BitLocker</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Disk Encryption</p>
                            <flux:badge color="green" class="mt-2">Encrypted</flux:badge>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">C: drive protected</p>
                        </flux:card>

                        {{-- Security Scan Results --}}
                        <div class="lg:col-span-3">
                            <flux:card>
                                <flux:heading size="lg">Security Assessment</flux:heading>
                                <div class="mt-4 space-y-3">
                                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                                            <span class="text-sm text-gray-900 dark:text-white">All security updates installed</span>
                                        </div>
                                        <span class="text-sm text-green-600 dark:text-green-400">Passed</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <flux:icon.check-circle class="h-5 w-5 text-green-500" />
                                            <span class="text-sm text-gray-900 dark:text-white">No malware detected</span>
                                        </div>
                                        <span class="text-sm text-green-600 dark:text-green-400">Passed</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <flux:icon.exclamation-triangle class="h-5 w-5 text-yellow-500" />
                                            <span class="text-sm text-gray-900 dark:text-white">UAC set to default level</span>
                                        </div>
                                        <span class="text-sm text-yellow-600 dark:text-yellow-400">Warning</span>
                                    </div>
                                </div>
                            </flux:card>
                        </div>
                    </div>
                </flux:tab.panel>

                {{-- Tools Tab --}}
                <flux:tab.panel name="tools" class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        {{-- Quick Action Buttons --}}
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.command-line class="h-8 w-8 mb-2" />
                            <span>Command Prompt</span>
                        </flux:button>
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.code-bracket class="h-8 w-8 mb-2" />
                            <span>PowerShell</span>
                        </flux:button>
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.folder-open class="h-8 w-8 mb-2" />
                            <span>File Browser</span>
                        </flux:button>
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.rectangle-stack class="h-8 w-8 mb-2" />
                            <span>Task Manager</span>
                        </flux:button>
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.cog-6-tooth class="h-8 w-8 mb-2" />
                            <span>Services</span>
                        </flux:button>
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.clipboard-document-list class="h-8 w-8 mb-2" />
                            <span>Event Viewer</span>
                        </flux:button>
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.key class="h-8 w-8 mb-2" />
                            <span>Registry Editor</span>
                        </flux:button>
                        <flux:button variant="outline" class="h-24 flex-col">
                            <flux:icon.cpu-chip class="h-8 w-8 mb-2" />
                            <span>Device Manager</span>
                        </flux:button>
                    </div>

                    {{-- Script Execution --}}
                    <flux:card class="mt-6">
                        <flux:heading size="lg">Run Script</flux:heading>
                        <div class="mt-4 space-y-4">
                            <flux:select placeholder="Select a script to run">
                                <option>Clear Temp Files</option>
                                <option>Reset Network Stack</option>
                                <option>Update Group Policy</option>
                                <option>Run System File Checker</option>
                                <option>Collect System Information</option>
                            </flux:select>
                            <flux:textarea placeholder="Script parameters (optional)" rows="3" />
                            <flux:button variant="primary" icon="play">Execute Script</flux:button>
                        </div>
                    </flux:card>
                </flux:tab.panel>

                {{-- Automation Tab --}}
                <flux:tab.panel name="automation" class="p-6">
                    <div class="space-y-6">
                        {{-- Scheduled Tasks --}}
                        <flux:card>
                            <div class="flex items-center justify-between mb-4">
                                <flux:heading size="lg">Scheduled Tasks</flux:heading>
                                <flux:button variant="primary" size="sm" icon="plus">Add Task</flux:button>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Weekly Disk Cleanup</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Every Sunday at 2:00 AM</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <flux:badge color="green">Active</flux:badge>
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                            <flux:menu>
                                                <flux:menu.item icon="pencil">Edit</flux:menu.item>
                                                <flux:menu.item icon="pause">Disable</flux:menu.item>
                                                <flux:menu.item icon="trash" variant="danger">Delete</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Windows Updates</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Every Wednesday at 3:00 AM</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <flux:badge color="green">Active</flux:badge>
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="xs" icon="ellipsis-horizontal" />
                                            <flux:menu>
                                                <flux:menu.item icon="pencil">Edit</flux:menu.item>
                                                <flux:menu.item icon="pause">Disable</flux:menu.item>
                                                <flux:menu.item icon="trash" variant="danger">Delete</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    </div>
                                </div>
                            </div>
                        </flux:card>

                        {{-- Maintenance Windows --}}
                        <flux:card>
                            <flux:heading size="lg">Maintenance Windows</flux:heading>
                            <div class="mt-4">
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Production Blackout</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Monday-Friday: 8:00 AM - 6:00 PM</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">No automated reboots or major updates during business hours</p>
                                        </div>
                                        <flux:toggle checked />
                                    </div>
                                </div>
                            </div>
                        </flux:card>
                    </div>
                </flux:tab.panel>

                {{-- History Tab --}}
                <flux:tab.panel name="history" class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">Asset History</flux:heading>
                            <flux:select class="w-48">
                                <option>Last 7 days</option>
                                <option>Last 30 days</option>
                                <option>Last 90 days</option>
                                <option>All time</option>
                            </flux:select>
                        </div>

                        {{-- History Timeline --}}
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach(range(1, 5) as $index)
                                <li>
                                    <div class="relative pb-8">
                                        @if($index < 5)
                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                    <flux:icon.clock class="h-5 w-5 text-white" />
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        System event logged
                                                    </p>
                                                </div>
                                                <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                    <time>{{ now()->subDays($index)->format('M d, Y H:i') }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </flux:tab.panel>
            </flux:tabs>
        </div>
    </div>
</div>
@endsection