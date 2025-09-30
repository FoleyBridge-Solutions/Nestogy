<?php

namespace App\Console\Commands;

use App\Domains\Ticket\Models\Ticket;
use App\Jobs\AnalyzeTicketSentiment;
use App\Models\TicketReply;
use Illuminate\Console\Command;

class AnalyzeSentimentCommand extends Command
{
    private const DEFAULT_PAGE_SIZE = 50;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sentiment:analyze
                            {--company-id= : Analyze sentiment for specific company}
                            {--batch-size=50 : Number of items to process per batch}
                            {--type=all : Type to analyze (tickets, replies, all)}
                            {--force : Force re-analysis of already analyzed items}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze sentiment for tickets and replies using background jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company-id');
        $batchSize = (int) $this->option('batch-size');
        $type = $this->option('type');
        $force = $this->option('force');

        $this->info('Starting sentiment analysis...');

        if ($companyId) {
            // Analyze for specific company
            $this->analyzeForCompany((int) $companyId, $batchSize, $type, $force);
        } else {
            // Analyze for all companies
            $this->analyzeForAllCompanies($batchSize, $type, $force);
        }

        $this->info('Sentiment analysis jobs have been queued.');

        return self::SUCCESS;
    }

    /**
     * Analyze sentiment for a specific company
     */
    protected function analyzeForCompany(int $companyId, int $batchSize, string $type, bool $force): void
    {
        $this->info("Analyzing sentiment for company ID: {$companyId}");

        if (in_array($type, ['tickets', 'all'])) {
            $this->analyzeTicketsForCompany($companyId, $batchSize, $force);
        }

        if (in_array($type, ['replies', 'all'])) {
            $this->analyzeRepliesForCompany($companyId, $batchSize, $force);
        }
    }

    /**
     * Analyze sentiment for all companies
     */
    protected function analyzeForAllCompanies(int $batchSize, string $type, bool $force): void
    {
        $this->info('Analyzing sentiment for all companies...');

        // Get all company IDs that have tickets or replies
        $companyIds = collect();

        if (in_array($type, ['tickets', 'all'])) {
            $ticketCompanies = Ticket::distinct('company_id')->pluck('company_id');
            $companyIds = $companyIds->merge($ticketCompanies);
        }

        if (in_array($type, ['replies', 'all'])) {
            $replyCompanies = TicketReply::distinct('company_id')->pluck('company_id');
            $companyIds = $companyIds->merge($replyCompanies);
        }

        $companyIds = $companyIds->unique()->filter();

        $this->info("Found {$companyIds->count()} companies to process");

        foreach ($companyIds as $companyId) {
            $this->analyzeForCompany($companyId, $batchSize, $type, $force);
        }
    }

    /**
     * Analyze tickets for a company
     */
    protected function analyzeTicketsForCompany(int $companyId, int $batchSize, bool $force): void
    {
        $query = Ticket::where('company_id', $companyId);

        if (! $force) {
            $query->whereNull('sentiment_analyzed_at');
        }

        $totalTickets = $query->count();

        if ($totalTickets === 0) {
            $this->line("No tickets to analyze for company {$companyId}");

            return;
        }

        $this->info("Queueing {$totalTickets} tickets for sentiment analysis for company {$companyId}");

        $bar = $this->output->createProgressBar($totalTickets);
        $bar->start();

        // Process tickets in chunks
        $query->chunk($batchSize, function ($tickets) use ($bar) {
            foreach ($tickets as $ticket) {
                AnalyzeTicketSentiment::queueTicketAnalysis($ticket->company_id, $ticket->id);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->line('');
    }

    /**
     * Analyze replies for a company
     */
    protected function analyzeRepliesForCompany(int $companyId, int $batchSize, bool $force): void
    {
        $query = TicketReply::where('company_id', $companyId);

        if (! $force) {
            $query->whereNull('sentiment_analyzed_at');
        }

        $totalReplies = $query->count();

        if ($totalReplies === 0) {
            $this->line("No replies to analyze for company {$companyId}");

            return;
        }

        $this->info("Queueing {$totalReplies} replies for sentiment analysis for company {$companyId}");

        $bar = $this->output->createProgressBar($totalReplies);
        $bar->start();

        // Process replies in chunks
        $query->chunk($batchSize, function ($replies) use ($bar) {
            foreach ($replies as $reply) {
                AnalyzeTicketSentiment::queueReplyAnalysis($reply->company_id, $reply->id);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->line('');
    }
}
