@extends('layouts.app')

@section('title', 'Admin Console')

@section('content')
<div class="w-full px-4 px-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="h3 mb-0 text-red-600">
            <i class="fas fa-terminal mr-2"></i>
            Admin Console
            <span class="badge bg-danger ml-2">RESTRICTED</span>
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" onclick="clearTerminal()">
                <i class="fas fa-trash mr-1"></i> Clear
            </button>
            <button type="button" class="btn btn-outline-info" @click="$dispatch('open-modal', 'modal-id')" data-bs-target="#helpModal">
                <i class="fas fa-question-circle me-1"></i> Help
            </button>
        </div>
    </div>

    @if(auth()->id() !== 1)
        <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Access Denied:</strong> Admin Console is restricted to User ID = 1 only.
        </div>
    @else
        <div class="px-4 py-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-700">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Caution:</strong> You have administrative access to system commands. Use with extreme care.
        </div>

        <div class="flex flex-wrap -mx-4">
            <!-- Terminal Interface -->
            <div class="col-lg-8">
                <div class="bg-white rounded-lg shadow-md overflow-hidden terminal-fullscreen-container mx-auto px-4 mx-auto px-4" x-data="adminTerminal" x-ref="terminalCard">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 bg-gray-900 text-white">
                        <h5 class="mb-0 flex items-center justify-between">
                            <span>
                                <i class="fas fa-terminal me-2"></i>
                                Professional Terminal
                            </span>
                            <div class="d-flex align-items-center">
                                <span :class="connectionStatus === 'connected' ? 'badge bg-success' : connectionStatus === 'executing' ? 'badge bg-warning' : 'badge bg-danger'" x-text="connectionStatus.charAt(0).toUpperCase() + connectionStatus.slice(1)"></span>
                                <span class="ml-2 small">{{ auth()->user()->name }}@nestogy</span>
                            </div>
                        </h5>
                    </div>
                    <div class="p-6 p-0">
                        <!-- Terminal Toolbar -->
                        <div class="command-toolbar d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <select x-ref="commandType" @change="$nextTick(() => { $refs.claudeMode.style.display = $refs.commandType.value === 'claude' ? 'block' : 'none'; })" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm-sm me-2" style="width: 120px;">
                                    <option value="artisan">⚡ Artisan</option>
                                    <option value="shell">💻 Shell</option>
                                    <option value="db">🗄️ Database</option>
                                    <option value="claude">🤖 Claude</option>
                                </select>
                                <select x-ref="claudeMode" style="width: 130px; display: none;" class="form-select form-select-sm me-2" title="Claude Permission Mode">
                                    <option value="default">📝 Default (Prompts)</option>
                                    <option value="plan">📋 Plan Mode (Analyze Only)</option>
                                    <option value="acceptEdits">✏️ Edit Mode (Auto-Accept)</option>
                                    <option value="bypassPermissions">🔓 Bypass (No Prompts)</option>
                                </select>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-light btn-sm" @click="clearTerminal()" title="Clear Terminal (Ctrl+L)">
                                        <i class="fas fa-trash fa-xs"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-light btn-sm" @click="toggleFullscreen()" x-ref="fullscreenBtn" title="Toggle Fullscreen (F11)">
                                        <i class="fas fa-expand fa-xs"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-light btn-sm" @click="copySelection()" title="Copy Selection (Ctrl+Shift+C)">
                                        <i class="fas fa-copy fa-xs"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-light btn-sm" @click="openSearch()" title="Search (Ctrl+Shift+F)">
                                        <i class="fas fa-search fa-xs"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-light btn-sm" @click="showThinking = !showThinking" :class="showThinking ? 'btn-warning' : 'btn-outline-light'" title="Toggle Claude Thinking Display">
                                        <i class="fas fa-brain fa-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="text-light small d-flex align-items-center">
                                <span class="me-3">History: <span x-text="commandHistory.length"></span></span>
                                <span x-text="terminalInfo"></span>
                            </div>
                        </div>
                        
                        <!-- xterm.js Terminal Container -->
                        <div x-ref="terminal" class="terminal-container"></div>
                        
                        <!-- Claude Thinking Display -->
                        <div x-show="showThinking && thinkingContent.length > 0" class="thinking-container">
                            <div class="thinking-header">
                                <h6 class="mb-1">
                                    <i class="fas fa-brain me-2"></i>Claude's Thinking Process
                                    <button type="button" class="btn btn-sm btn-outline-light ms-2" @click="showThinking = false">
                                        <i class="fas fa-times fa-xs"></i>
                                    </button>
                                </h6>
                            </div>
                            <div class="thinking-content">
                                <template x-for="(thought, index) in thinkingContent" :key="index">
                                    <div class="thinking-block">
                                        <div class="thinking-step">
                                            <span class="step-number" x-text="index + 1"></span>
                                            <div class="step-content" x-text="thought"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Terminal Status Bar -->
                        <div class="terminal-status d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="me-3">
                                    <i class="fas fa-circle text-green-600 fa-xs"></i>
                                    <span x-text="isExecuting ? 'Executing...' : 'Ready'"></span>
                                </span>
                                <span class="me-3" x-show="currentCommand">
                                    Command: <code x-text="currentCommand"></code>
                                </span>
                            </div>
                            <div class="d-flex align-items-center small">
                                <kbd>Tab</kbd> Complete &nbsp;
                                <kbd>↑↓</kbd> History &nbsp;
                                <kbd>Ctrl+L</kbd> Clear &nbsp;
                                <kbd>F11</kbd> Fullscreen
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="col-lg-4">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            System Information
                        </h5>
                    </div>
                    <div class="p-6">
                        <div class="system-info">
                            @if(isset($systemInfo['error']))
                                <div class="px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                                    {{ $systemInfo['error'] }}
                                </div>
                            @else
                                <div class="flex flex-wrap -mx-4 mb-3">
                                    <div class="col-12">
                                        <h6 class="text-blue-600">Environment</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>PHP:</strong> {{ $systemInfo['php_version'] ?? 'N/A' }}</li>
                                            <li><strong>Laravel:</strong> {{ $systemInfo['laravel_version'] ?? 'N/A' }}</li>
                                            <li><strong>Environment:</strong> {{ $systemInfo['environment'] ?? 'N/A' }}</li>
                                            <li><strong>Debug:</strong> {{ $systemInfo['debug_mode'] ?? 'N/A' }}</li>
                                            <li><strong>Timezone:</strong> {{ $systemInfo['timezone'] ?? 'N/A' }}</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h6 class="text-green-600">Database & Cache</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>Database:</strong> {{ $systemInfo['database_connection'] ?? 'N/A' }}</li>
                                            <li><strong>Cache:</strong> {{ $systemInfo['cache_driver'] ?? 'N/A' }}</li>
                                            <li><strong>Queue:</strong> {{ $systemInfo['queue_driver'] ?? 'N/A' }}</li>
                                            <li><strong>Mail:</strong> {{ $systemInfo['mail_driver'] ?? 'N/A' }}</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h6 class="text-warning">System Resources</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>Memory Limit:</strong> {{ $systemInfo['memory_limit'] ?? 'N/A' }}</li>
                                            <li><strong>Execution Time:</strong> {{ $systemInfo['max_execution_time'] ?? 'N/A' }}s</li>
                                            <li><strong>Disk Free:</strong> {{ $systemInfo['disk_free_space'] ?? 'N/A' }}</li>
                                            <li><strong>Disk Total:</strong> {{ $systemInfo['disk_total_space'] ?? 'N/A' }}</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-info">Application Data</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>Users:</strong> {{ number_format($systemInfo['users_count'] ?? 0) }}</li>
                                            <li><strong>Companies:</strong> {{ number_format($systemInfo['companies_count'] ?? 0) }}</li>
                                            <li><strong>Clients:</strong> {{ number_format($systemInfo['clients_count'] ?? 0) }}</li>
                                            <li><strong>Tickets:</strong> {{ number_format($systemInfo['tickets_count'] ?? 0) }}</li>
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="refreshSystemInfo()">
                                <i class="fas fa-sync me-1"></i> Refresh Info
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2" x-data="{ terminal: null }" x-init="setTimeout(() => { const el = document.querySelector('[x-data=adminTerminal]'); terminal = el?.__x?.$data; }, 100)">
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="terminal?.executeQuickCommand('artisan', 'cache:clear')">
                                <i class="fas fa-trash me-1"></i> Clear Cache
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm" @click="terminal?.executeQuickCommand('artisan', 'config:cache')">
                                <i class="fas fa-cog me-1"></i> Cache Config
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" @click="terminal?.executeQuickCommand('artisan', 'migrate:status')">
                                <i class="fas fa-database me-1"></i> Migration Status
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" @click="terminal?.executeQuickCommand('artisan', 'queue:restart')">
                                <i class="fas fa-redo me-1"></i> Restart Queue
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="terminal?.executeQuickCommand('shell', 'df -h')">
                                <i class="fas fa-hdd me-1"></i> Disk Usage
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm" @click="terminal?.executeQuickCommand('db', 'SELECT COUNT(*) FROM users')">
                                <i class="fas fa-users me-1"></i> Count Users
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" @click="terminal?.deleteAllClaudeSessions()" title="Delete all Claude sessions">
                                <i class="fas fa-trash-alt me-1"></i> Clear Claude Sessions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Console Help</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-modal')"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="md:w-1/3 px-4">
                        <h6 class="text-blue-600">Artisan Commands</h6>
                        <ul class="list-unstyled small">
                            <li><code>cache:clear</code> - Clear cache</li>
                            <li><code>config:clear</code> - Clear config cache</li>
                            <li><code>migrate:status</code> - Check migrations</li>
                            <li><code>queue:restart</code> - Restart queue workers</li>
                            <li><code>optimize:clear</code> - Clear all caches</li>
                            <li><code>about</code> - Show app information</li>
                        </ul>
                    </div>
                    <div class="md:w-1/3 px-4">
                        <h6 class="text-success">Shell Commands</h6>
                        <ul class="list-unstyled small">
                            <li><code>ls -la</code> - List files</li>
                            <li><code>pwd</code> - Current directory</li>
                            <li><code>df -h</code> - Disk usage</li>
                            <li><code>free -h</code> - Memory usage</li>
                            <li><code>ps aux</code> - Running processes</li>
                            <li><code>uptime</code> - System uptime</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-warning">Database Queries</h6>
                        <ul class="list-unstyled small">
                            <li><code>SELECT COUNT(*) FROM users</code></li>
                            <li><code>SELECT * FROM settings LIMIT 5</code></li>
                            <li><code>SHOW TABLES</code></li>
                            <li><code>DESCRIBE users</code></li>
                            <li><em>Only SELECT queries allowed</em></li>
                        </ul>
                    </div>
                </div>
                
                <div class="px-4 py-3 rounded bg-cyan-100 border border-cyan-400 text-cyan-700 mt-3">
                    <h6><i class="fas fa-keyboard me-1"></i> Keyboard Shortcuts</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li><kbd>Enter</kbd> - Execute command</li>
                        <li><kbd>Ctrl+L</kbd> - Clear terminal</li>
                        <li><kbd>↑</kbd>/<kbd>↓</kbd> - Navigate command history</li>
                        <li><kbd>Tab</kbd> - Auto-complete (basic)</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" @click="$dispatch('close-modal')">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.terminal-container {
    height: 500px;
    background-color: #0d1117;
    border-radius: 0;
    overflow: hidden;
    position: relative;
}

.xterm {
    height: 100%;
    padding: 8px;
}

.xterm-viewport {
    background-color: transparent !important;
}

.xterm .xterm-screen {
    padding: 8px;
}

.command-toolbar {
    background: linear-gradient(135deg, #21262d 0%, #30363d 100%);
    border-bottom: 1px solid #21262d;
    padding: 8px 16px;
}

.command-toolbar .btn {
    font-size: 12px;
    padding: 4px 8px;
    margin-right: 4px;
    border: 1px solid #30363d;
}

.command-toolbar .btn:hover {
    background-color: #30363d;
    border-color: #8b949e;
}

.terminal-status {
    font-size: 11px;
    color: #8b949e;
    background-color: #21262d;
    padding: 6px 12px;
    border-top: 1px solid #30363d;
}

.system-info ul {
    margin-bottom: 0;
}

.system-info li {
    padding: 3px 0;
    border-bottom: 1px solid #eee;
    font-size: 0.875rem;
}

.system-info li:last-child {
    border-bottom: none;
}

kbd {
    font-size: 0.7rem;
    background-color: #30363d;
    color: #f0f6fc;
    border: 1px solid #21262d;
    padding: 2px 4px;
    border-radius: 3px;
}

/* Fullscreen mode */
.terminal-fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 9999 !important;
    margin: 0 !important;
    border-radius: 0 !important;
}

.terminal-fullscreen .terminal-container {
    height: calc(100vh - 120px) !important;
}

.terminal-fullscreen-container {
    transition: all 0.3s ease;
}

/* Professional terminal styling */
.card-header.bg-dark {
    background: linear-gradient(135deg, #161b22 0%, #21262d 100%) !important;
    border-bottom: 2px solid #30363d;
}

.terminal-fullscreen .card-header {
    background: linear-gradient(135deg, #0d1117 0%, #161b22 100%) !important;
}

/* Enhanced badges */
.badge.bg-success {
    background: linear-gradient(135deg, #238636, #2ea043) !important;
    box-shadow: 0 1px 3px rgba(35, 134, 54, 0.3);
}

.badge.bg-warning {
    background: linear-gradient(135deg, #9a6700, #bf8700) !important;
    box-shadow: 0 1px 3px rgba(154, 103, 0, 0.3);
}

.badge.bg-danger {
    background: linear-gradient(135deg, #da3633, #f85149) !important;
    box-shadow: 0 1px 3px rgba(218, 54, 51, 0.3);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .terminal-container {
        height: 350px;
    }
    
    .terminal-fullscreen .terminal-container {
        height: calc(100vh - 140px) !important;
    }
    
    .command-toolbar {
        padding: 6px 12px;
        flex-direction: column;
        gap: 8px;
    }
    
    .command-toolbar .btn {
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .terminal-status {
        padding: 4px 8px;
        flex-direction: column;
        gap: 4px;
        text-align: center;
    }
    
    .system-info h6 {
        font-size: 0.9rem;
    }
    
    .system-info li {
        font-size: 0.8rem;
        padding: 2px 0;
    }
}

/* Loading animation */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.terminal-executing .fas.fa-circle {
    animation: pulse 1s infinite;
}

/* Improved scrollbars for terminal */
.xterm .xterm-viewport::-webkit-scrollbar {
    width: 8px;
}

.xterm .xterm-viewport::-webkit-scrollbar-track {
    background: #21262d;
}

.xterm .xterm-viewport::-webkit-scrollbar-thumb {
    background: #30363d;
    border-radius: 4px;
}

.xterm .xterm-viewport::-webkit-scrollbar-thumb:hover {
    background: #484f58;
}

/* Card improvements */
.card {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
}

.terminal-fullscreen-container.card {
    border: 2px solid #30363d;
}

/* Claude Thinking Display */
.thinking-container {
    background: linear-gradient(135deg, #1a1f25 0%, #2d3742 100%);
    border-top: 1px solid #30363d;
    max-height: 300px;
    overflow-y: auto;
}

.thinking-header {
    background: linear-gradient(135deg, #0f1419 0%, #1c2128 100%);
    padding: 8px 16px;
    border-bottom: 1px solid #21262d;
    display: flex;
    justify-content: between;
    align-items: center;
}

.thinking-header h6 {
    color: #f0f6fc;
    font-size: 0.85rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.thinking-content {
    padding: 12px 16px;
    max-height: 250px;
    overflow-y: auto;
}

.thinking-block {
    margin-bottom: 12px;
}

.thinking-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.step-number {
    background: linear-gradient(135deg, #238636, #2ea043);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
    margin-top: 2px;
}

.step-content {
    background: rgba(240, 246, 252, 0.05);
    border: 1px solid #30363d;
    border-radius: 6px;
    padding: 8px 12px;
    color: #e6edf3;
    font-size: 0.875rem;
    line-height: 1.4;
    flex: 1;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Thinking container scrollbar */
.thinking-content::-webkit-scrollbar {
    width: 6px;
}

.thinking-content::-webkit-scrollbar-track {
    background: #21262d;
}

.thinking-content::-webkit-scrollbar-thumb {
    background: #30363d;
    border-radius: 3px;
}

.thinking-content::-webkit-scrollbar-thumb:hover {
    background: #484f58;
}

/* Animation for new thinking blocks */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.thinking-block {
    animation: fadeInUp 0.3s ease-out;
}
</style>
@endpush

@push('scripts')
<script>
// Fallback for browsers without modern module support
if (!window.Alpine) {
    document.addEventListener('DOMContentLoaded', function() {
        const terminalContainer = document.querySelector('.terminal-container');
        if (terminalContainer && !terminalContainer.querySelector('.xterm')) {
            terminalContainer.innerHTML = `
                <div style="padding: 20px; color: #f0f6fc; text-align: center;">
                    <h4>⚠️ Terminal Unavailable</h4>
                    <p>This browser doesn't support the modern terminal interface.</p>
                    <p>Please use a modern browser like Chrome, Firefox, or Safari.</p>
                </div>
            `;
        }
    });
}

// Global function for refresh system info
function refreshSystemInfo() {
    location.reload();
}

// Global function to delete all Claude sessions
function deleteAllClaudeSessions() {
    const terminalElement = document.querySelector('[x-data="adminTerminal"]');
    if (terminalElement && terminalElement.__x && terminalElement.__x.$data) {
        terminalElement.__x.$data.deleteAllClaudeSessions();
    } else {
        alert('Terminal not initialized. Please refresh the page and try again.');
    }
}
</script>
@endpush