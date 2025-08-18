<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class AdminConsoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Auth::id() !== 1) {
                abort(403, 'Unauthorized. Admin Console is restricted to User ID = 1.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $systemInfo = $this->getSystemInfo();
        return view('admin.console', compact('systemInfo'));
    }

    public function executeCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'type' => 'required|string|in:artisan,shell,db,claude'
        ]);

        $command = trim($request->command);
        $type = $request->type;
        $output = '';
        $success = false;

        try {
            switch ($type) {
                case 'artisan':
                    $output = $this->executeArtisanCommand($command);
                    $success = true;
                    break;
                    
                case 'shell':
                    $output = $this->executeShellCommand($command);
                    $success = true;
                    break;
                    
                case 'db':
                    $output = $this->executeDatabaseQuery($command);
                    $success = true;
                    break;
                    
                case 'claude':
                    $output = 'Claude TUI commands are handled via WebSocket. Please use the interactive Claude mode.';
                    $success = false;
                    break;
                    
                default:
                    $output = 'Invalid command type';
                    $success = false;
            }
        } catch (\Exception $e) {
            $output = 'Error: ' . $e->getMessage();
            $success = false;
            Log::error('Admin Console Command Error', [
                'user_id' => Auth::id(),
                'command' => $command,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => $success,
            'output' => $output,
            'command' => $command,
            'type' => $type,
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
    }

    private function executeArtisanCommand(string $command): string
    {
        // Remove 'artisan' prefix if present
        $command = preg_replace('/^artisan\s+/', '', $command);
        
        // Whitelist of allowed Artisan commands for security
        $allowedCommands = [
            'migrate', 'migrate:status', 'migrate:rollback', 'migrate:refresh',
            'cache:clear', 'config:clear', 'route:clear', 'view:clear',
            'optimize:clear', 'queue:work', 'queue:restart', 'schedule:run',
            'config:cache', 'route:cache', 'view:cache', 'optimize',
            'tinker', 'db:seed', 'storage:link', 'key:generate',
            'make:migration', 'make:model', 'make:controller', 'make:request',
            'list', 'help', 'about', 'env', 'inspire'
        ];

        $commandParts = explode(' ', $command);
        $baseCommand = $commandParts[0] ?? '';

        if (!in_array($baseCommand, $allowedCommands)) {
            return "Command '$baseCommand' is not allowed for security reasons.\nAllowed commands: " . implode(', ', $allowedCommands);
        }

        try {
            Artisan::call($command);
            return Artisan::output() ?: "Command executed successfully (no output)";
        } catch (\Exception $e) {
            return "Artisan command failed: " . $e->getMessage();
        }
    }

    private function executeShellCommand(string $command): string
    {
        // Whitelist of allowed shell commands for security
        $allowedCommands = [
            'ls', 'pwd', 'whoami', 'date', 'uptime', 'df', 'free',
            'ps', 'top', 'htop', 'netstat', 'ss', 'tail', 'head',
            'cat', 'grep', 'find', 'du', 'wc', 'sort', 'uniq'
        ];

        $commandParts = explode(' ', $command);
        $baseCommand = $commandParts[0] ?? '';

        if (!in_array($baseCommand, $allowedCommands)) {
            return "Shell command '$baseCommand' is not allowed for security reasons.\nAllowed commands: " . implode(', ', $allowedCommands);
        }

        try {
            $process = new Process(explode(' ', $command));
            $process->setTimeout(30); // 30 second timeout
            $process->run();

            if (!$process->isSuccessful()) {
                return "Command failed with exit code " . $process->getExitCode() . ":\n" . $process->getErrorOutput();
            }

            return $process->getOutput() ?: "Command executed successfully (no output)";
        } catch (\Exception $e) {
            return "Shell command failed: " . $e->getMessage();
        }
    }

    private function executeDatabaseQuery(string $query): string
    {
        // Only allow SELECT statements for security
        $query = trim($query);
        if (!preg_match('/^SELECT\s+/i', $query)) {
            return "Only SELECT queries are allowed for security reasons.";
        }

        try {
            $results = DB::select($query);
            
            if (empty($results)) {
                return "Query executed successfully. No results returned.";
            }

            // Format results as table
            $output = "Query executed successfully. Results:\n\n";
            
            // Get column headers from first row
            $firstRow = (array) $results[0];
            $headers = array_keys($firstRow);
            
            // Calculate column widths
            $widths = [];
            foreach ($headers as $header) {
                $widths[$header] = max(strlen($header), 10);
            }
            
            foreach ($results as $row) {
                $rowArray = (array) $row;
                foreach ($rowArray as $key => $value) {
                    $widths[$key] = max($widths[$key], strlen((string) $value));
                }
            }
            
            // Build table header
            $headerRow = '| ';
            $separator = '|-';
            foreach ($headers as $header) {
                $headerRow .= str_pad($header, $widths[$header]) . ' | ';
                $separator .= str_repeat('-', $widths[$header]) . '-|-';
            }
            $output .= $headerRow . "\n" . $separator . "\n";
            
            // Build table rows
            foreach ($results as $row) {
                $rowArray = (array) $row;
                $dataRow = '| ';
                foreach ($headers as $header) {
                    $value = $rowArray[$header] ?? '';
                    $dataRow .= str_pad((string) $value, $widths[$header]) . ' | ';
                }
                $output .= $dataRow . "\n";
            }
            
            $output .= "\n(" . count($results) . " rows returned)";
            
            return $output;
        } catch (\Exception $e) {
            return "Database query failed: " . $e->getMessage();
        }
    }

    private function getSystemInfo(): array
    {
        try {
            return [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->format('Y-m-d H:i:s T'),
                'timezone' => config('app.timezone'),
                'environment' => app()->environment(),
                'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
                'database_connection' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
                'mail_driver' => config('mail.default'),
                'storage_driver' => config('filesystems.default'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'disk_free_space' => $this->formatBytes(disk_free_space('/')),
                'disk_total_space' => $this->formatBytes(disk_total_space('/')),
                'server_load' => sys_getloadavg(),
                'users_count' => DB::table('users')->count(),
                'companies_count' => DB::table('companies')->count(),
                'clients_count' => DB::table('clients')->count(),
                'tickets_count' => DB::table('tickets')->count(),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to gather system information: ' . $e->getMessage()];
        }
    }

    private function formatBytes($size, $precision = 2): string
    {
        if ($size <= 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $base = log($size, 1024);
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }
}