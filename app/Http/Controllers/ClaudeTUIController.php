<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ClaudePTYService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClaudeTUIController extends Controller
{
    private ClaudePTYService $ptyService;

    public function __construct(ClaudePTYService $ptyService)
    {
        $this->ptyService = $ptyService;
        
        $this->middleware(function ($request, $next) {
            Log::info('Claude TUI access attempt', [
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name,
                'is_authenticated' => Auth::check(),
                'route' => $request->route()?->getName(),
                'method' => $request->method()
            ]);
            
            if (Auth::id() !== 1) {
                Log::warning('Claude TUI access denied', [
                    'user_id' => Auth::id(),
                    'required_user_id' => 1
                ]);
                abort(403, 'Unauthorized. Claude TUI is restricted to User ID = 1.');
            }
            return $next($request);
        });
    }

    /**
     * Start a new Claude session
     */
    public function startSession(Request $request)
    {
        $request->validate([
            'permission_mode' => 'nullable|string|in:default,plan,acceptEdits,bypassPermissions'
        ]);

        try {
            $permissionMode = $request->input('permission_mode', 'default');
            $sessionId = $this->ptyService->startClaudeSession($permissionMode);
            
            Log::info('Claude session started', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'permission_mode' => $permissionMode
            ]);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'permission_mode' => $permissionMode,
                'message' => 'Claude session started'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to start Claude session', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start Claude session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream Claude TUI output via Server-Sent Events
     */
    public function streamOutput(Request $request, string $sessionId)
    {
        if (!$this->ptyService->sessionExists($sessionId)) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        return new StreamedResponse(function () use ($sessionId) {
            $this->ptyService->streamOutput($sessionId, function ($data) {
                // Send data as Server-Sent Event
                echo "data: " . json_encode([
                    'type' => 'output',
                    'data' => $data,
                    'timestamp' => now()->toISOString()
                ]) . "\n\n";
                
                // Flush output immediately
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            });
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    /**
     * Send input to Claude session and get response
     */
    public function sendInput(Request $request, string $sessionId)
    {
        Log::info('Claude sendInput called', [
            'session_id' => $sessionId,
            'user_id' => Auth::id(),
            'has_input' => $request->has('input'),
            'request_method' => $request->method(),
            'request_headers' => $request->headers->all()
        ]);

        $request->validate([
            'input' => 'required|string'
        ]);

        if (!$this->ptyService->sessionExists($sessionId)) {
            Log::warning('Claude session not found', ['session_id' => $sessionId]);
            return response()->json(['error' => 'Session not found'], 404);
        }

        try {
            $output = $this->ptyService->executeClaudeCommand($sessionId, $request->input);

            return response()->json([
                'success' => true,
                'output' => $output,
                'message' => 'Command executed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to execute Claude command', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'input' => $request->input,
                'error' => $e->getMessage(),
                'trace' => explode("\n", $e->getTraceAsString(), 11) // First 10 lines
            ]);

            $jsonResponse = [
                'success' => false,
                'message' => 'Failed to execute command: ' . $e->getMessage(),
                'debug' => [
                    'input' => $request->input,
                    'session_id' => $sessionId,
                    'trace_preview' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10)
                ]
            ];
            
            Log::info('Returning JSON error response', ['response' => $jsonResponse]);
            
            return response()->json($jsonResponse, 500);
        }
    }

    /**
     * Resize Claude TUI terminal
     */
    public function resizeTerminal(Request $request, string $sessionId)
    {
        $request->validate([
            'cols' => 'required|integer|min:1|max:500',
            'rows' => 'required|integer|min:1|max:200'
        ]);

        if (!$this->ptyService->sessionExists($sessionId)) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        try {
            $this->ptyService->resizeTerminal($sessionId, $request->cols, $request->rows);

            return response()->json([
                'success' => true,
                'message' => 'Terminal resized successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to resize Claude TUI terminal', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resize terminal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop Claude TUI session
     */
    public function stopSession(Request $request, string $sessionId)
    {
        if (!$this->ptyService->sessionExists($sessionId)) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        try {
            $this->ptyService->stopSession($sessionId);

            Log::info('Claude TUI session stopped', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Claude TUI session stopped'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to stop Claude TUI session', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to stop session: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get session status and info
     */
    public function getSessionStatus(Request $request, string $sessionId)
    {
        if (!$this->ptyService->sessionExists($sessionId)) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        try {
            $status = $this->ptyService->getSessionStatus($sessionId);

            return response()->json([
                'success' => true,
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get session status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all active Claude TUI sessions for admin
     */
    public function listSessions()
    {
        try {
            $sessions = $this->ptyService->getActiveSessions();

            return response()->json([
                'success' => true,
                'sessions' => $sessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cleanup inactive/expired sessions
     */
    public function cleanupSessions()
    {
        try {
            $cleaned = $this->ptyService->cleanupExpiredSessions();

            Log::info('Claude TUI sessions cleaned up', [
                'user_id' => Auth::id(),
                'cleaned_count' => $cleaned
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$cleaned} expired sessions"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete all Claude sessions
     */
    public function deleteAllSessions()
    {
        try {
            $deletedCount = $this->ptyService->deleteAllSessions();

            Log::info('All Claude sessions deleted', [
                'user_id' => Auth::id(),
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} Claude session(s)",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete all Claude sessions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Claude sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Claude CLI integration directly
     */
    public function testClaude(Request $request)
    {
        try {
            // First test if Claude CLI is available
            $process = new \Symfony\Component\Process\Process(['which', 'claude']);
            $process->run();
            
            if (!$process->isSuccessful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Claude CLI not found in PATH',
                    'debug' => [
                        'path' => getenv('PATH'),
                        'which_output' => $process->getOutput(),
                        'which_error' => $process->getErrorOutput()
                    ]
                ], 500);
            }

            $claudePath = trim($process->getOutput());
            
            // Test basic Claude command
            $testProcess = new \Symfony\Component\Process\Process(['claude', '--version']);
            $testProcess->run();
            
            return response()->json([
                'success' => true,
                'claude_path' => $claudePath,
                'claude_version' => $testProcess->getOutput(),
                'message' => 'Claude CLI is available'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Claude test failed: ' . $e->getMessage()
            ], 500);
        }
    }
}