<?php

namespace App\Console\Commands;

use App\Domains\Email\Services\UnifiedMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMailQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:process-queue 
                            {--limit=100 : Maximum number of emails to process}
                            {--retry : Also retry failed emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending emails in the mail queue';

    protected UnifiedMailService $mailService;
    
    /**
     * Create a new command instance.
     */
    public function __construct(UnifiedMailService $mailService)
    {
        parent::__construct();
        $this->mailService = $mailService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing mail queue...');
        
        $limit = (int) $this->option('limit');
        
        // Process pending emails
        $processed = $this->mailService->processPending($limit);
        
        if ($processed > 0) {
            $this->info("Processed {$processed} pending email(s).");
            
            Log::info('Mail queue processed', [
                'processed' => $processed,
            ]);
        } else {
            $this->info('No pending emails to process.');
        }
        
        // Retry failed emails if requested
        if ($this->option('retry')) {
            $this->info('Retrying failed emails...');
            
            $retried = $this->mailService->retryFailed();
            
            if ($retried > 0) {
                $this->info("Retried {$retried} failed email(s).");
                
                Log::info('Failed emails retried', [
                    'retried' => $retried,
                ]);
            } else {
                $this->info('No failed emails to retry.');
            }
        }
        
        return Command::SUCCESS;
    }
}
