<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ClaudePTYService;

class ClaudeSessionCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'claude:cleanup {--force : Force cleanup all sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired Claude TUI sessions';

    /**
     * Execute the console command.
     */
    public function handle(ClaudePTYService $ptyService)
    {
        $this->info('🤖 Starting Claude session cleanup...');

        try {
            if ($this->option('force')) {
                $this->warn('⚠️  Force cleanup mode - stopping ALL Claude sessions');
                $sessions = $ptyService->getActiveSessions();
                $cleanedCount = count($sessions);

                foreach ($sessions as $session) {
                    $ptyService->stopSession($session['session_id']);
                }

                $this->info("✅ Force cleaned {$cleanedCount} sessions");
            } else {
                $cleanedCount = $ptyService->cleanupExpiredSessions();
                $this->info("✅ Cleaned up {$cleanedCount} expired sessions");
            }

            $activeSessions = $ptyService->getActiveSessions();
            $activeCount = count($activeSessions);

            if ($activeCount > 0) {
                $this->line("📊 {$activeCount} active sessions remaining:");
                foreach ($activeSessions as $session) {
                    $duration = gmdate('H:i:s', $session['duration']);
                    $this->line("   • {$session['session_id']} (PID: {$session['pid']}, Duration: {$duration})");
                }
            } else {
                $this->line('📊 No active sessions');
            }

        } catch (\Exception $e) {
            $this->error('❌ Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
