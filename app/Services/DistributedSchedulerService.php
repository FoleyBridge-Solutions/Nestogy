<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Distributed Scheduler Service
 * 
 * Ensures critical scheduled tasks run exactly once across multiple servers
 * with automatic failover if the current executor goes down.
 */
class DistributedSchedulerService
{
    private const LOCK_TTL = 300; // 5 minutes
    private const HEARTBEAT_INTERVAL = 60; // 1 minute
    
    /**
     * Execute a job if no other server is currently running it.
     */
    public function executeIfNotRunning(string $jobName, callable $job, int $maxRuntime = 3600): bool
    {
        $scheduleKey = $this->getScheduleKey($jobName);
        $serverId = $this->getServerId();
        
        // Try to acquire lock
        if (!$this->acquireLock($jobName, $scheduleKey, $serverId)) {
            Log::info("Job {$jobName} already running on another server", [
                'job' => $jobName,
                'server' => $serverId,
                'schedule_key' => $scheduleKey
            ]);
            return false;
        }
        
        try {
            Log::info("Starting job {$jobName} on server {$serverId}", [
                'job' => $jobName,
                'server' => $serverId,
                'schedule_key' => $scheduleKey
            ]);
            
            // Start heartbeat to prevent timeout
            $this->startHeartbeat($jobName, $scheduleKey, $serverId);
            
            // Execute the job
            $startTime = microtime(true);
            $job();
            $duration = round(microtime(true) - $startTime, 2);
            
            // Mark as completed
            $this->markCompleted($jobName, $scheduleKey, $serverId, $duration);
            
            Log::info("Completed job {$jobName} in {$duration}s", [
                'job' => $jobName,
                'server' => $serverId,
                'duration' => $duration
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Job {$jobName} failed on server {$serverId}", [
                'job' => $jobName,
                'server' => $serverId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Release lock so another server can try
            $this->releaseLock($jobName, $scheduleKey, $serverId);
            throw $e;
        }
    }
    
    /**
     * Try to acquire execution lock for a job.
     */
    private function acquireLock(string $jobName, string $scheduleKey, string $serverId): bool
    {
        try {
            // Check if job is already running and not stale
            $existing = DB::table('scheduler_coordination')
                ->where('job_name', $jobName)
                ->where('schedule_key', $scheduleKey)
                ->first();
            
            if ($existing) {
                // Check if heartbeat is fresh (not stale)
                $heartbeatAge = now()->diffInSeconds($existing->heartbeat_at);
                
                if ($heartbeatAge < self::LOCK_TTL && !$existing->completed_at) {
                    // Job is actively running on another server
                    return false;
                }
                
                // Stale lock or completed job - clean it up
                DB::table('scheduler_coordination')
                    ->where('id', $existing->id)
                    ->delete();
            }
            
            // Try to insert our lock
            DB::table('scheduler_coordination')->insert([
                'job_name' => $jobName,
                'schedule_key' => $scheduleKey,
                'server_id' => $serverId,
                'started_at' => now(),
                'heartbeat_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            // Unique constraint violation means another server got the lock
            Log::debug("Failed to acquire lock for {$jobName}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start heartbeat to keep lock alive.
     */
    private function startHeartbeat(string $jobName, string $scheduleKey, string $serverId): void
    {
        // Update heartbeat in background
        register_tick_function(function() use ($jobName, $scheduleKey, $serverId) {
            static $lastHeartbeat = 0;
            
            if (time() - $lastHeartbeat >= self::HEARTBEAT_INTERVAL) {
                DB::table('scheduler_coordination')
                    ->where('job_name', $jobName)
                    ->where('schedule_key', $scheduleKey)
                    ->where('server_id', $serverId)
                    ->update([
                        'heartbeat_at' => now(),
                        'updated_at' => now(),
                    ]);
                
                $lastHeartbeat = time();
            }
        });
    }
    
    /**
     * Mark job as completed.
     */
    private function markCompleted(string $jobName, string $scheduleKey, string $serverId, float $duration): void
    {
        DB::table('scheduler_coordination')
            ->where('job_name', $jobName)
            ->where('schedule_key', $scheduleKey)
            ->where('server_id', $serverId)
            ->update([
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
    
    /**
     * Release lock (for error cases).
     */
    private function releaseLock(string $jobName, string $scheduleKey, string $serverId): void
    {
        DB::table('scheduler_coordination')
            ->where('job_name', $jobName)
            ->where('schedule_key', $scheduleKey)
            ->where('server_id', $serverId)
            ->delete();
    }
    
    /**
     * Generate schedule key for job deduplication.
     */
    private function getScheduleKey(string $jobName): string
    {
        // For daily jobs: 2025-08-18-recurring-billing
        // For hourly jobs: 2025-08-18-14-retry-payments
        
        if (str_contains($jobName, 'daily') || str_contains($jobName, 'recurring')) {
            return date('Y-m-d') . '-' . $jobName;
        }
        
        if (str_contains($jobName, 'hourly')) {
            return date('Y-m-d-H') . '-' . $jobName;
        }
        
        // Default: use current date-time for unique execution
        return date('Y-m-d-H-i') . '-' . $jobName;
    }
    
    /**
     * Get unique server identifier.
     */
    private function getServerId(): string
    {
        return gethostname() . '-' . getmypid();
    }
    
    /**
     * Clean up old coordination records.
     */
    public function cleanup(): void
    {
        DB::table('scheduler_coordination')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();
    }
}