<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ClaudePTYService
{
    private array $sessions = [];
    private const SESSION_TIMEOUT = 3600; // 1 hour
    private const MAX_SESSIONS = 5; // Limit concurrent sessions
    private const SESSION_STORAGE_FILE = 'claude_sessions.json';

    public function __construct()
    {
        $this->loadSessions();
    }

    /**
     * Start a new Claude session
     */
    public function startClaudeSession(string $permissionMode = 'default'): string
    {
        // Check session limit
        $this->cleanupExpiredSessions();
        
        if (count($this->sessions) >= self::MAX_SESSIONS) {
            throw new \Exception('Maximum number of Claude sessions reached');
        }

        $sessionId = Str::uuid()->toString();
        
        try {
            // Store session information
            $this->sessions[$sessionId] = [
                'created_at' => time(),
                'last_activity' => time(),
                'status' => 'active',
                'permission_mode' => $permissionMode,
                'conversation_history' => []
            ];

            Log::info('Claude session started', [
                'session_id' => $sessionId
            ]);

            $this->saveSessions();

            return $sessionId;
        } catch (\Exception $e) {
            Log::error('Failed to start Claude session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if session exists and is active
     */
    public function sessionExists(string $sessionId): bool
    {
        if (!isset($this->sessions[$sessionId])) {
            return false;
        }

        $session = $this->sessions[$sessionId];

        // Check for timeout
        if (time() - $session['last_activity'] > self::SESSION_TIMEOUT) {
            $this->stopSession($sessionId);
            return false;
        }

        return true;
    }

    /**
     * Execute Claude command and get response
     */
    public function executeClaudeCommand(string $sessionId, string $input, bool $streaming = false): string
    {
        if (!$this->sessionExists($sessionId)) {
            Log::error('Claude session not found', [
                'session_id' => $sessionId,
                'available_sessions' => array_keys($this->sessions),
                'session_count' => count($this->sessions)
            ]);
            throw new \Exception('Session not found or expired');
        }

        $session = &$this->sessions[$sessionId];
        
        Log::info('Claude session found', [
            'session_id' => $sessionId,
            'permission_mode' => $session['permission_mode'] ?? 'unknown',
            'streaming' => $streaming,
            'created_at' => $session['created_at'],
            'last_activity' => $session['last_activity']
        ]);

        try {
            // Add to conversation history
            $session['conversation_history'][] = [
                'type' => 'user',
                'content' => $input,
                'timestamp' => time()
            ];

            // Build Claude command with session's permission mode
            $permissionMode = $session['permission_mode'] ?? 'default';
            $command = [
                'claude', 
                '--print',
                '--permission-mode', $permissionMode
            ];

            // Add streaming JSON output format if requested
            if ($streaming) {
                $command[] = '--output-format';
                $command[] = 'stream-json';
                $command[] = '--verbose';
            }

            // Add the input as the last argument
            $command[] = $input;

            // Execute Claude command in webapp directory
            $process = new Process($command, base_path());
            
            $process->setTimeout(120); // 2 minute timeout for individual commands
            
            // Set environment variables - Claude will use webapp base directory
            $process->setEnv([
                'HOME' => base_path(),
                'PATH' => getenv('PATH'),
                'TERM' => 'xterm-256color',
                'XDG_CONFIG_HOME' => base_path(),
                'USER' => 'www-data',
                'CLAUDE_CODE_OAUTH_TOKEN' => config('services.claude.oauth_token')
            ]);
            
            // Note: Symfony Process doesn't have setUser() method
            // The process will inherit the web server user (www-data)
            
            Log::info('Executing Claude command', [
                'command' => $command,
                'working_directory' => base_path(),
                'streaming' => $streaming,
                'env_path' => getenv('PATH')
            ]);
            
            if ($streaming) {
                return $this->executeStreamingCommand($process, $sessionId, $session);
            } else {
                return $this->executeStandardCommand($process, $sessionId, $session);
            }
        } catch (\Exception $e) {
            Log::error('Failed to execute Claude command', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Execute standard (non-streaming) Claude command
     */
    private function executeStandardCommand(Process $process, string $sessionId, array &$session): string
    {
        $process->run();

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();
            Log::error('Claude command failed', [
                'session_id' => $sessionId,
                'exit_code' => $process->getExitCode(),
                'error_output' => $error,
                'stdout' => $process->getOutput()
            ]);
            throw new \Exception('Claude command failed: ' . $error);
        }

        $output = $process->getOutput();
        
        // Add response to conversation history
        $session['conversation_history'][] = [
            'type' => 'assistant',
            'content' => $output,
            'timestamp' => time()
        ];

        // Update last activity
        $session['last_activity'] = time();
        
        // Save sessions after updating
        $this->saveSessions();

        Log::debug('Claude command executed', [
            'session_id' => $sessionId,
            'output_length' => strlen($output)
        ]);

        return $output;
    }

    /**
     * Execute streaming Claude command with real-time output processing
     */
    private function executeStreamingCommand(Process $process, string $sessionId, array &$session): string
    {
        $fullOutput = '';
        $thinkingData = [];
        $finalResponse = '';
        
        // Start the process asynchronously
        $process->start();
        
        while ($process->isRunning()) {
            // Read incremental output
            $incrementalOutput = $process->getIncrementalOutput();
            
            if (!empty($incrementalOutput)) {
                $fullOutput .= $incrementalOutput;
                
                // Parse streaming JSON lines
                $lines = explode("\n", $incrementalOutput);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $parsedData = $this->parseStreamingLine($line);
                    if ($parsedData) {
                        if ($parsedData['type'] === 'thinking') {
                            $thinkingData[] = $parsedData['content'];
                        } elseif ($parsedData['type'] === 'response') {
                            $finalResponse = $parsedData['content'];
                        }
                    }
                }
            }
            
            // Small sleep to prevent excessive CPU usage
            usleep(50000); // 50ms
        }
        
        // Wait for process to complete
        $process->wait();
        
        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $fullOutput;
            Log::error('Claude streaming command failed', [
                'session_id' => $sessionId,
                'exit_code' => $process->getExitCode(),
                'error_output' => $error
            ]);
            throw new \Exception('Claude streaming command failed: ' . $error);
        }
        
        // Combine thinking and final response for storage
        $combinedOutput = [
            'thinking' => $thinkingData,
            'response' => $finalResponse,
            'raw_output' => $fullOutput
        ];
        
        // Add response to conversation history
        $session['conversation_history'][] = [
            'type' => 'assistant',
            'content' => json_encode($combinedOutput),
            'timestamp' => time(),
            'streaming' => true
        ];

        // Update last activity
        $session['last_activity'] = time();
        
        // Save sessions after updating
        $this->saveSessions();

        Log::debug('Claude streaming command executed', [
            'session_id' => $sessionId,
            'thinking_blocks' => count($thinkingData),
            'output_length' => strlen($fullOutput)
        ]);

        return json_encode($combinedOutput);
    }

    /**
     * Parse a single line from streaming JSON output
     */
    private function parseStreamingLine(string $line): ?array
    {
        try {
            $data = json_decode($line, true);
            
            if (!is_array($data)) {
                return null;
            }
            
            // Check for thinking content
            if (isset($data['type']) && $data['type'] === 'thinking' && isset($data['content'])) {
                return [
                    'type' => 'thinking',
                    'content' => $data['content']
                ];
            }
            
            // Check for final response
            if (isset($data['type']) && $data['type'] === 'text' && isset($data['text'])) {
                return [
                    'type' => 'response',
                    'content' => $data['text']
                ];
            }
            
            // Check for tool calls or other structured data
            if (isset($data['type'])) {
                return [
                    'type' => $data['type'],
                    'content' => $data
                ];
            }
            
        } catch (\Exception $e) {
            // Invalid JSON, ignore this line
            Log::debug('Failed to parse streaming line', [
                'line' => $line,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }


    /**
     * Resize terminal for session
     */
    public function resizeTerminal(string $sessionId, int $cols, int $rows): void
    {
        if (!$this->sessionExists($sessionId)) {
            throw new \Exception('Session not found or expired');
        }

        $session = &$this->sessions[$sessionId];
        
        // Update session dimensions
        $session['cols'] = $cols;
        $session['rows'] = $rows;
        $session['last_activity'] = time();

        try {
            // Send resize signal to process (if supported)
            $process = $session['process'];
            if ($process->isRunning()) {
                // Note: PHP doesn't have direct PTY resize support
                // This would typically require a more advanced PTY library
                // For now, we update environment variables for future reference
                Log::debug('Terminal resize requested', [
                    'session_id' => $sessionId,
                    'cols' => $cols,
                    'rows' => $rows
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to resize terminal', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            // Don't throw here as resize is not critical
        }
    }

    /**
     * Stop Claude session
     */
    public function stopSession(string $sessionId): void
    {
        if (!isset($this->sessions[$sessionId])) {
            return;
        }

        $session = $this->sessions[$sessionId];

        try {
            Log::info('Claude session stopped', [
                'session_id' => $sessionId,
                'duration' => time() - $session['created_at'],
                'conversation_count' => count($session['conversation_history'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error stopping Claude session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->cleanupSession($sessionId);
        }
    }

    /**
     * Get session status and information
     */
    public function getSessionStatus(string $sessionId): array
    {
        if (!isset($this->sessions[$sessionId])) {
            throw new \Exception('Session not found');
        }

        $session = $this->sessions[$sessionId];

        return [
            'session_id' => $sessionId,
            'status' => $session['status'],
            'created_at' => $session['created_at'],
            'last_activity' => $session['last_activity'],
            'duration' => time() - $session['created_at'],
            'conversation_count' => count($session['conversation_history'])
        ];
    }

    /**
     * Get all active sessions
     */
    public function getActiveSessions(): array
    {
        $activeSessions = [];
        
        foreach ($this->sessions as $sessionId => $session) {
            if ($this->sessionExists($sessionId)) {
                $activeSessions[] = $this->getSessionStatus($sessionId);
            }
        }

        return $activeSessions;
    }

    /**
     * Cleanup expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        $cleaned = 0;
        $currentTime = time();

        foreach ($this->sessions as $sessionId => $session) {
            // Check for timeout
            if ($currentTime - $session['last_activity'] > self::SESSION_TIMEOUT) {
                $this->stopSession($sessionId);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            Log::info('Cleaned up expired Claude sessions', ['count' => $cleaned]);
        }

        return $cleaned;
    }

    /**
     * Delete all Claude sessions
     */
    public function deleteAllSessions(): int
    {
        $sessionCount = count($this->sessions);
        
        // Stop all active sessions
        foreach (array_keys($this->sessions) as $sessionId) {
            $this->stopSession($sessionId);
        }
        
        // Clear the sessions array and save
        $this->sessions = [];
        $this->saveSessions();
        
        Log::info('Deleted all Claude sessions', ['count' => $sessionCount]);
        
        return $sessionCount;
    }

    /**
     * Cleanup a specific session
     */
    private function cleanupSession(string $sessionId): void
    {
        unset($this->sessions[$sessionId]);
        $this->saveSessions();
    }

    /**
     * Load sessions from storage
     */
    private function loadSessions(): void
    {
        $storageFile = storage_path('app/' . self::SESSION_STORAGE_FILE);
        
        if (file_exists($storageFile)) {
            try {
                $data = json_decode(file_get_contents($storageFile), true);
                if (is_array($data)) {
                    $this->sessions = $data;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to load Claude sessions', ['error' => $e->getMessage()]);
                $this->sessions = [];
            }
        }
    }

    /**
     * Save sessions to storage
     */
    private function saveSessions(): void
    {
        $storageFile = storage_path('app/' . self::SESSION_STORAGE_FILE);
        
        try {
            file_put_contents($storageFile, json_encode($this->sessions, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::warning('Failed to save Claude sessions', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Destructor - cleanup all sessions
     */
    public function __destruct()
    {
        $this->saveSessions();
    }
}