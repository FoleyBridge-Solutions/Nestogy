import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import { SearchAddon } from '@xterm/addon-search';
import { WebLinksAddon } from '@xterm/addon-web-links';
import '@xterm/xterm/css/xterm.css';

/**
 * Admin Terminal Component
 * Professional xterm.js-based terminal for Laravel admin console
 */
export function adminTerminal() {
    return {
        // Terminal instances
        terminal: null,
        fitAddon: null,
        searchAddon: null,
        
        // State management
        isExecuting: false,
        currentInput: '',
        currentCommand: '',
        commandHistory: [],
        historyIndex: -1,
        
        // Claude TUI state
        claudeSessionId: null,
        claudeEventSource: null,
        claudeMode: false,
        
        // UI state
        isFullscreen: false,
        connectionStatus: 'connected',
        terminalInfo: 'Ready',
        
        // Claude thinking display
        showThinking: true,
        thinkingContent: [],
        
        // Command completions by type
        completions: {
            artisan: [
                'about', 'cache:clear', 'config:clear', 'config:cache', 
                'route:clear', 'route:cache', 'view:clear', 'view:cache',
                'migrate', 'migrate:status', 'migrate:rollback', 'migrate:fresh',
                'queue:work', 'queue:restart', 'queue:failed', 'queue:retry',
                'optimize:clear', 'optimize', 'storage:link', 'key:generate',
                'tinker', 'list', 'help', 'env', 'inspire'
            ],
            shell: [
                'ls -la', 'ls -lah', 'pwd', 'whoami', 'date', 'uptime',
                'df -h', 'free -h', 'ps aux', 'top', 'htop',
                'netstat -tulpn', 'ss -tulpn', 'tail -f', 'head -20',
                'grep -r', 'find . -name', 'du -sh', 'wc -l'
            ],
            db: [
                'SELECT COUNT(*) FROM users',
                'SELECT COUNT(*) FROM companies', 
                'SELECT COUNT(*) FROM clients',
                'SELECT COUNT(*) FROM tickets',
                'SELECT * FROM settings LIMIT 5',
                'SELECT * FROM users LIMIT 10',
                'SHOW TABLES',
                'DESCRIBE users',
                'DESCRIBE settings',
                'SHOW PROCESSLIST'
            ],
            claude: [
                'help', 'explain this error:', 'optimize this code:', 'debug issue:',
                'write a function to', 'create a migration for', 'suggest improvements for',
                'analyze this query:', 'review my code:', 'how do I',
                'what is the best way to', 'troubleshoot connection issues',
                'performance analysis', 'security review', 'code explanation',
                'generate documentation for', 'create tests for', 'refactor this:'
            ]
        },

        /**
         * Initialize the terminal component
         */
        init() {
            this.loadCommandHistory();
            this.initializeTerminal();
            this.setupEventListeners();
            this.updateHistoryCount();
        },

        /**
         * Initialize xterm.js terminal with optimal configuration
         */
        initializeTerminal() {
            // Create terminal with professional configuration
            this.terminal = new Terminal({
                cursorBlink: true,
                cursorStyle: 'block',
                fontSize: 14,
                fontFamily: '"Cascadia Code", "SF Mono", Monaco, Menlo, "Ubuntu Mono", Consolas, monospace',
                fontWeight: 'normal',
                fontWeightBold: 'bold',
                lineHeight: 1.2,
                letterSpacing: 0,
                theme: {
                    background: '#0d1117',
                    foreground: '#c9d1d9',
                    cursor: '#f0f6fc',
                    cursorAccent: '#0d1117',
                    selection: '#58a6ff40',
                    black: '#484f58',
                    red: '#ff7b72',
                    green: '#7ee787',
                    yellow: '#f2cc60',
                    blue: '#79c0ff',
                    magenta: '#d2a8ff',
                    cyan: '#56d4dd',
                    white: '#f0f6fc',
                    brightBlack: '#6e7681',
                    brightRed: '#ffa198',
                    brightGreen: '#56d364',
                    brightYellow: '#e3b341',
                    brightBlue: '#58a6ff',
                    brightMagenta: '#bc8cff',
                    brightCyan: '#39c5cf',
                    brightWhite: '#f0f6fc'
                },
                allowTransparency: false,
                convertEol: true,
                scrollback: 2000,
                macOptionIsMeta: true,
                allowProposedApi: true
            });

            // Load addons
            this.fitAddon = new FitAddon();
            this.searchAddon = new SearchAddon();
            const webLinksAddon = new WebLinksAddon();

            this.terminal.loadAddon(this.fitAddon);
            this.terminal.loadAddon(this.searchAddon);
            this.terminal.loadAddon(webLinksAddon);

            // Mount terminal to DOM
            this.terminal.open(this.$refs.terminal);
            
            // Fit terminal to container
            setTimeout(() => this.fitAddon.fit(), 100);

            // Setup terminal event handlers
            this.terminal.onData(this.handleTerminalInput.bind(this));
            this.terminal.onBinary(this.handleBinaryData.bind(this));
            this.terminal.onSelectionChange(() => this.handleSelectionChange());

            // Show welcome message and initial prompt
            this.showWelcomeMessage();
            this.showPrompt();
        },

        /**
         * Setup event listeners
         */
        setupEventListeners() {
            // Window resize handler
            window.addEventListener('resize', () => {
                if (this.fitAddon) {
                    setTimeout(() => this.fitAddon.fit(), 100);
                }
            });

            // Command type change handler
            this.$watch('$refs.commandType.value', () => {
                if (!this.isExecuting) {
                    this.clearCurrentLine();
                    this.terminal.writeln('');
                    this.showPrompt();
                }
            });

            // Global keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.target.closest('#terminal') || e.target.closest('.terminal-container')) {
                    this.handleGlobalShortcuts(e);
                }
            });

            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                this.cleanup();
            });
        },

        /**
         * Handle global keyboard shortcuts
         */
        handleGlobalShortcuts(e) {
            // Ctrl+Shift+F for search
            if (e.ctrlKey && e.shiftKey && e.key === 'F') {
                e.preventDefault();
                this.openSearch();
            }
            
            // Ctrl+Shift+C for copy (if text selected)
            if (e.ctrlKey && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                this.copySelection();
            }
            
            // F11 for fullscreen toggle
            if (e.key === 'F11') {
                e.preventDefault();
                this.toggleFullscreen();
            }
        },

        /**
         * Show welcome message
         */
        showWelcomeMessage() {
            const lines = [
                '\x1b[1;36m╔══════════════════════════════════════════════════════════════╗\x1b[0m',
                '\x1b[1;36m║\x1b[0m                 \x1b[1;32mNestogy Admin Console v2.0\x1b[0m                 \x1b[1;36m║\x1b[0m',
                '\x1b[1;36m╚══════════════════════════════════════════════════════════════╝\x1b[0m',
                '',
                `\x1b[33m● User:\x1b[0m ${window.CURRENT_USER?.name || 'Admin'} \x1b[90m|\x1b[0m \x1b[33m● Company:\x1b[0m ${window.CURRENT_USER?.company_id || 'System'}`,
                `\x1b[33m● Time:\x1b[0m ${new Date().toLocaleString()} \x1b[90m|\x1b[0m \x1b[33m● Session:\x1b[0m Interactive`,
                '',
                '\x1b[36m📋 Quick Commands:\x1b[0m',
                '  \x1b[90m›\x1b[0m \x1b[32martisan cache:clear\x1b[0m  - Clear application cache',
                '  \x1b[90m›\x1b[0m \x1b[32martisan queue:restart\x1b[0m - Restart queue workers', 
                '  \x1b[90m›\x1b[0m \x1b[32mdf -h\x1b[0m                - Check disk usage',
                '  \x1b[90m›\x1b[0m \x1b[32mSELECT COUNT(*) FROM users\x1b[0m - Count database records',
                '',
                '\x1b[36m🤖 Claude AI Integration:\x1b[0m',
                '  \x1b[90m›\x1b[0m \x1b[96mSelect "Claude" type and permission mode\x1b[0m - Choose Plan/Edit/Default mode',
                '  \x1b[90m›\x1b[0m \x1b[96mType any command or question\x1b[0m - Get AI assistance with development',
                '  \x1b[90m›\x1b[0m \x1b[96mType "exit" in Claude mode\x1b[0m - Return to regular terminal',
                '',
                '\x1b[36m⌨️  Keyboard Shortcuts:\x1b[0m',
                '  \x1b[90m›\x1b[0m \x1b[33mTab\x1b[0m       - Auto-complete commands',
                '  \x1b[90m›\x1b[0m \x1b[33m↑/↓\x1b[0m       - Navigate command history',
                '  \x1b[90m›\x1b[0m \x1b[33mCtrl+L\x1b[0m    - Clear terminal',
                '  \x1b[90m›\x1b[0m \x1b[33mCtrl+C\x1b[0m    - Cancel current command',
                '  \x1b[90m›\x1b[0m \x1b[33mF11\x1b[0m       - Toggle fullscreen',
                '',
                '\x1b[32m✨ Terminal ready! Select command type and start typing...\x1b[0m',
                ''
            ];

            lines.forEach(line => this.terminal.writeln(line));
        },

        /**
         * Show command prompt
         */
        showPrompt() {
            // Don't show prompt in Claude mode - Claude TUI handles its own prompts
            if (this.claudeMode) {
                return;
            }

            const commandType = this.$refs.commandType?.value || 'artisan';
            const promptConfig = {
                artisan: { color: '\x1b[1;34m', symbol: '⚡' },
                shell: { color: '\x1b[1;32m', symbol: '$' },
                db: { color: '\x1b[1;35m', symbol: '🗄️' },
                claude: { color: '\x1b[1;36m', symbol: '🤖' }
            };

            const config = promptConfig[commandType] || promptConfig.artisan;
            this.terminal.write(`${config.color}nestogy:${commandType}\x1b[0m ${config.symbol} `);
            this.currentInput = '';
        },

        /**
         * Handle terminal input
         */
        handleTerminalInput(data) {
            if (this.isExecuting) return;

            const ord = data.charCodeAt(0);

            switch (ord) {
                case 13: // Enter
                    this.handleEnterKey();
                    break;
                case 127: // Backspace  
                case 8:   // Backspace (alternative)
                    this.handleBackspace();
                    break;
                case 3: // Ctrl+C
                    this.handleCtrlC();
                    break;
                case 12: // Ctrl+L
                    this.clearTerminal();
                    break;
                case 9: // Tab
                    this.handleTabCompletion();
                    break;
                case 27: // Escape sequences (arrows, etc.)
                    this.handleEscapeSequence(data);
                    break;
                default:
                    if (ord >= 32 && ord <= 126) { // Printable characters
                        this.currentInput += data;
                        this.terminal.write(data);
                    }
            }

            this.updateTerminalInfo();
        },

        /**
         * Handle Enter key press
         */
        handleEnterKey() {
            if (this.claudeMode && this.claudeSessionId) {
                // In Claude mode, send input directly to Claude
                this.sendClaudeInput(this.currentInput);
                this.currentInput = '';
            } else if (this.currentInput.trim()) {
                // Regular command execution
                this.executeCommand(this.currentInput.trim());
            } else {
                this.terminal.writeln('');
                this.showPrompt();
            }
        },

        /**
         * Handle backspace
         */
        handleBackspace() {
            if (this.currentInput.length > 0) {
                this.currentInput = this.currentInput.slice(0, -1);
                this.terminal.write('\b \b');
            }
        },

        /**
         * Handle Ctrl+C
         */
        handleCtrlC() {
            this.terminal.writeln('\x1b[31m^C\x1b[0m');
            this.currentInput = '';
            
            if (this.isExecuting) {
                this.cancelExecution();
            }
            
            this.showPrompt();
        },

        /**
         * Handle escape sequences (arrow keys, etc.)
         */
        handleEscapeSequence(data) {
            if (data === '\x1b[A') { // Up arrow
                this.navigateHistory(-1);
            } else if (data === '\x1b[B') { // Down arrow
                this.navigateHistory(1);
            } else if (data === '\x1b[C') { // Right arrow
                // Could implement cursor movement
            } else if (data === '\x1b[D') { // Left arrow
                // Could implement cursor movement
            }
        },

        /**
         * Handle tab completion
         */
        handleTabCompletion() {
            const commandType = this.$refs.commandType?.value || 'artisan';
            const completions = this.completions[commandType] || [];
            
            const matches = completions.filter(cmd => cmd.startsWith(this.currentInput));
            
            if (matches.length === 1) {
                // Complete the command
                this.clearCurrentLine();
                this.currentInput = matches[0];
                this.terminal.write(this.currentInput);
            } else if (matches.length > 1) {
                // Show available completions
                this.terminal.writeln('');
                this.terminal.writeln('\x1b[33m💡 Available completions:\x1b[0m');
                
                matches.forEach((match, index) => {
                    const icon = commandType === 'db' ? '🗄️' : commandType === 'shell' ? '💻' : '⚡';
                    this.terminal.writeln(`  ${icon} \x1b[36m${match}\x1b[0m`);
                });
                
                this.terminal.writeln('');
                this.showPrompt();
                this.terminal.write(this.currentInput);
            } else {
                // No matches, show hint
                this.terminal.write('\x1b[31m?\x1b[0m');
                setTimeout(() => {
                    this.terminal.write('\b ');
                }, 200);
            }
        },

        /**
         * Navigate command history
         */
        navigateHistory(direction) {
            if (this.commandHistory.length === 0) return;

            this.clearCurrentLine();

            if (direction === -1) { // Up
                if (this.historyIndex > 0) {
                    this.historyIndex--;
                } else {
                    this.historyIndex = this.commandHistory.length - 1;
                }
            } else { // Down
                if (this.historyIndex < this.commandHistory.length - 1) {
                    this.historyIndex++;
                } else {
                    this.historyIndex = 0;
                }
            }

            this.currentInput = this.commandHistory[this.historyIndex] || '';
            this.terminal.write(this.currentInput);
        },

        /**
         * Clear current input line
         */
        clearCurrentLine() {
            for (let i = 0; i < this.currentInput.length; i++) {
                this.terminal.write('\b \b');
            }
            this.currentInput = '';
        },

        /**
         * Execute command
         */
        async executeCommand(command) {
            this.terminal.writeln('');
            
            // Add to history
            if (this.commandHistory[this.commandHistory.length - 1] !== command) {
                this.commandHistory.push(command);
                this.saveCommandHistory();
                this.updateHistoryCount();
            }
            this.historyIndex = this.commandHistory.length;
            
            const commandType = this.$refs.commandType?.value || 'artisan';
            this.currentCommand = command;
            this.isExecuting = true;
            
            this.updateTerminalInfo('Executing command...');
            this.updateConnectionStatus('executing');
            
            // Handle Claude TUI commands differently
            if (commandType === 'claude') {
                await this.handleClaudeCommand(command);
                return;
            }
            
            // Show execution indicator for regular commands
            this.terminal.writeln(`\x1b[33m⚡ Executing: \x1b[36m${commandType}\x1b[0m \x1b[32m${command}\x1b[0m`);
            this.terminal.writeln('\x1b[90m' + '─'.repeat(60) + '\x1b[0m');
            
            try {
                const response = await fetch('/admin/console/command', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        command: command,
                        type: commandType
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.terminal.writeln('\x1b[32m✅ Command completed successfully\x1b[0m');
                    if (data.output) {
                        this.terminal.writeln('');
                        this.formatAndDisplayOutput(data.output, commandType);
                    }
                } else {
                    this.terminal.writeln('\x1b[31m❌ Command failed\x1b[0m');
                    if (data.output) {
                        this.terminal.writeln('');
                        this.terminal.writeln('\x1b[31m' + data.output + '\x1b[0m');
                    }
                }
            } catch (error) {
                this.terminal.writeln('\x1b[31m🚫 Network error: ' + error.message + '\x1b[0m');
            } finally {
                this.terminal.writeln('');
                this.terminal.writeln('\x1b[90m' + '─'.repeat(60) + '\x1b[0m');
                this.isExecuting = false;
                this.currentCommand = '';
                this.updateTerminalInfo('Ready');
                this.updateConnectionStatus('connected');
                this.showPrompt();
            }
        },

        /**
         * Handle Claude TUI commands
         */
        async handleClaudeCommand(command) {
            // Special commands for Claude TUI mode
            if (command === 'exit' && this.claudeMode) {
                await this.stopClaudeSession();
                return;
            }

            if (command === 'start' || (!this.claudeMode && command !== 'exit')) {
                await this.startClaudeSession();
                return;
            }

            // If in Claude mode, send input to Claude session
            if (this.claudeMode && this.claudeSessionId) {
                await this.sendClaudeInput(command);
            }
        },

        /**
         * Start Claude session
         */
        async startClaudeSession() {
            try {
                this.terminal.writeln('\x1b[36m🤖 Starting Claude session...\x1b[0m');
                this.updateTerminalInfo('Starting Claude...');
                
                const permissionMode = this.$refs.claudeMode?.value || 'default';
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                console.log('CSRF Token for start session:', csrfToken);
                
                const response = await fetch('/admin/claude/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        permission_mode: permissionMode
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    this.claudeSessionId = data.session_id;
                    this.claudeMode = true;
                    this.isExecuting = false;
                    
                    const modeLabels = {
                        'default': 'Default',
                        'plan': 'Plan Mode',
                        'acceptEdits': 'Edit Mode',
                        'bypassPermissions': 'Bypass Permissions'
                    };
                    const modeLabel = modeLabels[data.permission_mode] || data.permission_mode;
                    
                    this.terminal.writeln('\x1b[32m✅ Claude session started\x1b[0m');
                    this.terminal.writeln(`\x1b[36m🔧 Permission Mode: \x1b[1;37m${modeLabel}\x1b[0m`);
                    this.terminal.writeln('\x1b[33m💡 You are now in Claude mode. Type your questions or commands. Type "exit" to return to regular terminal.\x1b[0m');
                    this.terminal.writeln('\x1b[90m' + '─'.repeat(60) + '\x1b[0m');
                    
                    this.updateTerminalInfo('Claude session active');
                    this.updateConnectionStatus('claude');
                    
                    // Show Claude prompt
                    this.showClaudePrompt();
                } else {
                    throw new Error(data.message || 'Failed to start Claude session');
                }
            } catch (error) {
                this.terminal.writeln('\x1b[31m❌ Failed to start Claude: ' + error.message + '\x1b[0m');
                this.isExecuting = false;
                this.updateTerminalInfo('Ready');
                this.updateConnectionStatus('connected');
                this.showPrompt();
            }
        },


        /**
         * Send input to Claude session and display response with streaming
         */
        async sendClaudeInput(input) {
            try {
                this.isExecuting = true;
                this.updateTerminalInfo('Asking Claude...');
                this.terminal.writeln(`\n\x1b[96m❯ ${input}\x1b[0m`);
                this.terminal.writeln('\x1b[90m' + '─'.repeat(60) + '\x1b[0m');
                
                // Clear previous thinking content
                this.thinkingContent = [];
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                console.log('CSRF Token:', csrfToken);
                
                const response = await fetch(`/admin/claude/input/${this.claudeSessionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ input: input })
                });

                const data = await response.json();
                
                if (data.success && data.output) {
                    // Parse streaming output if available
                    const output = this.parseClaudeOutput(data.output);
                    
                    this.terminal.writeln('\n\x1b[36m🤖 Claude:\x1b[0m');
                    
                    if (output.thinking && output.thinking.length > 0) {
                        // Display thinking process in sidebar
                        this.thinkingContent = output.thinking;
                        this.terminal.writeln('\x1b[33m💭 Thinking process displayed in sidebar\x1b[0m');
                    }
                    
                    // Display final response
                    const finalOutput = output.response || output.raw_output || data.output;
                    this.formatAndDisplayClaudeOutput(finalOutput);
                } else {
                    this.terminal.writeln('\x1b[31m❌ ' + (data.message || 'Failed to get response from Claude') + '\x1b[0m');
                }
                
                this.terminal.writeln('\x1b[90m' + '─'.repeat(60) + '\x1b[0m');
                this.showClaudePrompt();
            } catch (error) {
                this.terminal.writeln('\x1b[31m🚫 Network error: ' + error.message + '\x1b[0m');
                this.showClaudePrompt();
            } finally {
                this.isExecuting = false;
                this.updateTerminalInfo('Claude session active');
            }
        },

        /**
         * Parse Claude output to extract thinking and response
         */
        parseClaudeOutput(output) {
            try {
                // Try to parse as JSON (streaming output)
                const parsed = JSON.parse(output);
                if (parsed.thinking && parsed.response) {
                    return parsed;
                }
            } catch (e) {
                // Not JSON, treat as plain text
            }
            
            // Return as plain response
            return {
                thinking: [],
                response: output,
                raw_output: output
            };
        },

        /**
         * Format and display Claude output with enhanced formatting
         */
        formatAndDisplayClaudeOutput(output) {
            const lines = output.split('\n');
            
            lines.forEach(line => {
                if (line.trim() === '') {
                    this.terminal.writeln('');
                    return;
                }
                
                // Code block detection
                if (line.startsWith('```') || line.includes('```')) {
                    this.terminal.writeln('\x1b[90m' + line + '\x1b[0m');
                } else if (line.startsWith('#') && line.includes('#')) {
                    // Headers
                    this.terminal.writeln('\x1b[1;32m' + line + '\x1b[0m');
                } else if (line.startsWith('- ') || line.startsWith('* ')) {
                    // Lists
                    this.terminal.writeln('\x1b[36m' + line + '\x1b[0m');
                } else if (line.toLowerCase().includes('error') || line.toLowerCase().includes('fail')) {
                    // Errors
                    this.terminal.writeln('\x1b[31m' + line + '\x1b[0m');
                } else if (line.toLowerCase().includes('success') || line.toLowerCase().includes('done')) {
                    // Success
                    this.terminal.writeln('\x1b[32m' + line + '\x1b[0m');
                } else if (line.toLowerCase().includes('warning') || line.toLowerCase().includes('caution')) {
                    // Warnings
                    this.terminal.writeln('\x1b[33m' + line + '\x1b[0m');
                } else {
                    // Regular text
                    this.terminal.writeln('\x1b[37m' + line + '\x1b[0m');
                }
            });
        },

        /**
         * Stop Claude session
         */
        async stopClaudeSession() {
            try {
                if (this.claudeSessionId) {
                    await fetch(`/admin/claude/stop/${this.claudeSessionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                }

                this.claudeSessionId = null;
                this.claudeMode = false;
                this.isExecuting = false;
                
                this.terminal.writeln('\n\x1b[36m🤖 Claude session ended\x1b[0m');
                this.terminal.writeln('\x1b[90m' + '─'.repeat(60) + '\x1b[0m');
                
                this.updateTerminalInfo('Ready');
                this.updateConnectionStatus('connected');
                this.showPrompt();
            } catch (error) {
                console.error('Error stopping Claude session:', error);
                // Force reset state
                this.claudeSessionId = null;
                this.claudeMode = false;
                this.isExecuting = false;
                this.showPrompt();
            }
        },

        /**
         * Show Claude prompt
         */
        showClaudePrompt() {
            this.terminal.write('\n\x1b[1;36mclaude\x1b[0m \x1b[96m🤖\x1b[0m ');
            this.currentInput = '';
        },

        /**
         * Format and display command output with syntax highlighting
         */
        formatAndDisplayOutput(output, commandType) {
            const lines = output.split('\n');
            
            lines.forEach(line => {
                if (commandType === 'db' && line.includes('|')) {
                    // Database table formatting
                    this.terminal.writeln('\x1b[36m' + line + '\x1b[0m');
                } else if (line.toLowerCase().includes('error') || line.toLowerCase().includes('fail') || line.toLowerCase().includes('exception')) {
                    // Error highlighting
                    this.terminal.writeln('\x1b[31m' + line + '\x1b[0m');
                } else if (line.toLowerCase().includes('success') || line.toLowerCase().includes('done') || line.toLowerCase().includes('completed')) {
                    // Success highlighting
                    this.terminal.writeln('\x1b[32m' + line + '\x1b[0m');
                } else if (line.startsWith('INFO') || line.includes('INFO')) {
                    // Info highlighting
                    this.terminal.writeln('\x1b[34m' + line + '\x1b[0m');
                } else if (line.startsWith('WARNING') || line.includes('WARNING')) {
                    // Warning highlighting
                    this.terminal.writeln('\x1b[33m' + line + '\x1b[0m');
                } else if (commandType === 'shell' && (line.includes('drwx') || line.includes('-rw'))) {
                    // File listing highlighting
                    this.terminal.writeln('\x1b[36m' + line + '\x1b[0m');
                } else {
                    this.terminal.writeln(line);
                }
            });
        },

        /**
         * Handle binary data (paste, etc.)
         */
        handleBinaryData(data) {
            if (data.length > 1) {
                // Handle pasted content
                this.currentInput += data;
                this.terminal.write(data);
            }
        },

        /**
         * Handle selection change
         */
        handleSelectionChange() {
            // Could implement selection-based features here
        },

        /**
         * Clear terminal
         */
        clearTerminal() {
            this.terminal.clear();
            this.showWelcomeMessage();
            this.showPrompt();
            this.updateTerminalInfo('Terminal cleared');
        },

        /**
         * Toggle fullscreen mode
         */
        toggleFullscreen() {
            const terminalCard = this.$refs.terminalCard;
            const toggleBtn = this.$refs.fullscreenBtn;
            
            if (this.isFullscreen) {
                terminalCard.classList.remove('terminal-fullscreen');
                toggleBtn.innerHTML = '<i class="fas fa-expand fa-xs"></i>';
                this.isFullscreen = false;
            } else {
                terminalCard.classList.add('terminal-fullscreen');
                toggleBtn.innerHTML = '<i class="fas fa-compress fa-xs"></i>';
                this.isFullscreen = true;
            }
            
            setTimeout(() => this.fitAddon.fit(), 200);
        },

        /**
         * Copy selected text or entire output
         */
        copySelection() {
            const selection = this.terminal.getSelection();
            if (selection) {
                navigator.clipboard.writeText(selection).then(() => {
                    this.updateTerminalInfo('Copied to clipboard');
                    setTimeout(() => this.updateTerminalInfo('Ready'), 2000);
                }).catch(() => {
                    this.updateTerminalInfo('Copy failed');
                    setTimeout(() => this.updateTerminalInfo('Ready'), 2000);
                });
            } else {
                this.updateTerminalInfo('No text selected');
                setTimeout(() => this.updateTerminalInfo('Ready'), 2000);
            }
        },

        /**
         * Open search functionality
         */
        openSearch() {
            // For now, show info about search
            this.updateTerminalInfo('Search: Use Ctrl+F in browser or select text to copy');
            setTimeout(() => this.updateTerminalInfo('Ready'), 3000);
        },

        /**
         * Execute predefined quick command
         */
        executeQuickCommand(type, command) {
            this.$refs.commandType.value = type;
            this.clearCurrentLine();
            this.currentInput = command;
            this.terminal.write(command);
            this.executeCommand(command);
        },

        /**
         * Cancel command execution
         */
        cancelExecution() {
            this.isExecuting = false;
            this.currentCommand = '';
            this.updateTerminalInfo('Command cancelled');
            this.updateConnectionStatus('connected');
        },

        /**
         * Update terminal info display
         */
        updateTerminalInfo(message = 'Ready') {
            this.terminalInfo = message;
        },

        /**
         * Update connection status
         */
        updateConnectionStatus(status = 'connected') {
            this.connectionStatus = status;
        },

        /**
         * Update history count display
         */
        updateHistoryCount() {
            // This will be reactive in Alpine.js
        },

        /**
         * Save command history to localStorage
         */
        saveCommandHistory() {
            try {
                localStorage.setItem('nestogy_admin_console_history', JSON.stringify(this.commandHistory.slice(-100)));
            } catch (e) {
                console.warn('Could not save command history');
            }
        },

        /**
         * Load command history from localStorage
         */
        loadCommandHistory() {
            try {
                const saved = localStorage.getItem('nestogy_admin_console_history');
                if (saved) {
                    this.commandHistory = JSON.parse(saved);
                    this.historyIndex = this.commandHistory.length;
                }
            } catch (e) {
                console.warn('Could not load command history');
                this.commandHistory = [];
                this.historyIndex = 0;
            }
        },

        /**
         * Refresh system info (for the info panel)
         */
        refreshSystemInfo() {
            location.reload();
        },

        /**
         * Delete all Claude sessions
         */
        async deleteAllClaudeSessions() {
            if (!confirm('Are you sure you want to delete ALL Claude sessions? This will stop all active Claude sessions.')) {
                return;
            }

            try {
                this.updateTerminalInfo('Deleting all Claude sessions...');
                
                const response = await fetch('/admin/claude/sessions', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    // Reset current session if it was deleted
                    if (this.claudeSessionId) {
                        this.claudeSessionId = null;
                        this.claudeMode = false;
                        this.isExecuting = false;
                        this.thinkingContent = [];
                    }
                    
                    this.terminal.writeln('\n\x1b[32m✅ ' + data.message + '\x1b[0m');
                    this.updateTerminalInfo(`Deleted ${data.deleted_count} sessions`);
                    
                    // Show prompt if we were in Claude mode
                    if (!this.claudeMode) {
                        this.showPrompt();
                    }
                    
                    setTimeout(() => this.updateTerminalInfo('Ready'), 3000);
                } else {
                    this.terminal.writeln('\n\x1b[31m❌ Failed to delete Claude sessions: ' + data.message + '\x1b[0m');
                    this.updateTerminalInfo('Delete failed');
                    setTimeout(() => this.updateTerminalInfo('Ready'), 3000);
                }
            } catch (error) {
                this.terminal.writeln('\n\x1b[31m🚫 Network error: ' + error.message + '\x1b[0m');
                this.updateTerminalInfo('Network error');
                setTimeout(() => this.updateTerminalInfo('Ready'), 3000);
            }
        },

        /**
         * Cleanup Claude session on page unload
         */
        cleanup() {
            if (this.claudeMode && this.claudeSessionId) {
                // Use sendBeacon for reliable cleanup on page unload
                navigator.sendBeacon(
                    `/admin/claude/stop/${this.claudeSessionId}`,
                    new URLSearchParams({ '_token': document.querySelector('meta[name="csrf-token"]').content })
                );
            }
        }
    };
}