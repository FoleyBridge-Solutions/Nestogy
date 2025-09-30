<?php

namespace App\Jobs;

use App\Domains\Financial\Services\TaxEngine\SentimentAnalysisService;
use App\Domains\Ticket\Models\Ticket;
use App\Models\TicketReply;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Background job to analyze sentiment for tickets and replies
 *
 * This job processes sentiment analysis for individual tickets or replies
 * using the SentimentAnalysisService. It handles batching, error recovery,
 * and performance optimization.
 */
class AnalyzeTicketSentiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $companyId;

    protected ?int $ticketId;

    protected ?int $replyId;

    protected string $analysisType;

    public int $timeout = 120; // 2 minutes timeout

    public int $tries = 3; // Retry up to 3 times

    public int $backoff = 30; // Wait 30 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $companyId,
        string $analysisType = 'ticket',
        ?int $ticketId = null,
        ?int $replyId = null
    ) {
        $this->companyId = $companyId;
        $this->analysisType = $analysisType;
        $this->ticketId = $ticketId;
        $this->replyId = $replyId;

        // Set queue name based on analysis type
        $this->onQueue($analysisType === 'batch' ? 'sentiment-batch' : 'sentiment');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $service = new SentimentAnalysisService($this->companyId);

            switch ($this->analysisType) {
                case 'ticket':
                    $this->analyzeTicket($service);
                    break;
                case 'reply':
                    $this->analyzeReply($service);
                    break;
                case 'batch':
                    $this->analyzeBatch($service);
                    break;
                default:
                    throw new Exception("Unknown analysis type: {$this->analysisType}");
            }

        } catch (Exception $e) {
            Log::error('Sentiment analysis job failed', [
                'company_id' => $this->companyId,
                'analysis_type' => $this->analysisType,
                'ticket_id' => $this->ticketId,
                'reply_id' => $this->replyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Analyze sentiment for a single ticket
     */
    protected function analyzeTicket(SentimentAnalysisService $service): void
    {
        if (! $this->ticketId) {
            throw new Exception('Ticket ID is required for ticket analysis');
        }

        $ticket = Ticket::where('company_id', $this->companyId)
            ->where('id', $this->ticketId)
            ->first();

        if (! $ticket) {
            Log::warning('Ticket not found for sentiment analysis', [
                'company_id' => $this->companyId,
                'ticket_id' => $this->ticketId,
            ]);

            return;
        }

        // Skip if already analyzed recently (within 24 hours)
        if ($ticket->sentiment_analyzed_at && $ticket->sentiment_analyzed_at->gt(now()->subHours(24))) {
            Log::info('Skipping ticket sentiment analysis - recently analyzed', [
                'ticket_id' => $ticket->id,
                'last_analyzed' => $ticket->sentiment_analyzed_at,
            ]);

            return;
        }

        $text = $ticket->getSentimentAnalysisText();
        if (empty($text)) {
            Log::info('Skipping ticket sentiment analysis - no text content', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $result = $service->analyzeSentiment($text);

        if ($result['success']) {
            $ticket->update([
                'sentiment_score' => $result['sentiment_score'],
                'sentiment_label' => $result['sentiment_label'],
                'sentiment_confidence' => $result['sentiment_confidence'],
                'sentiment_analyzed_at' => now(),
            ]);

            Log::info('Ticket sentiment analysis completed', [
                'ticket_id' => $ticket->id,
                'sentiment_label' => $result['sentiment_label'],
                'sentiment_score' => $result['sentiment_score'],
                'confidence' => $result['sentiment_confidence'],
            ]);

        } else {
            Log::warning('Ticket sentiment analysis failed', [
                'ticket_id' => $ticket->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Analyze sentiment for a single reply
     */
    protected function analyzeReply(SentimentAnalysisService $service): void
    {
        if (! $this->replyId) {
            throw new Exception('Reply ID is required for reply analysis');
        }

        $reply = TicketReply::where('company_id', $this->companyId)
            ->where('id', $this->replyId)
            ->first();

        if (! $reply) {
            Log::warning('Reply not found for sentiment analysis', [
                'company_id' => $this->companyId,
                'reply_id' => $this->replyId,
            ]);

            return;
        }

        // Skip if already analyzed recently (within 24 hours)
        if ($reply->sentiment_analyzed_at && $reply->sentiment_analyzed_at->gt(now()->subHours(24))) {
            Log::info('Skipping reply sentiment analysis - recently analyzed', [
                'reply_id' => $reply->id,
                'last_analyzed' => $reply->sentiment_analyzed_at,
            ]);

            return;
        }

        $text = $reply->getSentimentAnalysisText();
        if (empty($text)) {
            Log::info('Skipping reply sentiment analysis - no text content', [
                'reply_id' => $reply->id,
            ]);

            return;
        }

        $result = $service->analyzeSentiment($text);

        if ($result['success']) {
            $reply->update([
                'sentiment_score' => $result['sentiment_score'],
                'sentiment_label' => $result['sentiment_label'],
                'sentiment_confidence' => $result['sentiment_confidence'],
                'sentiment_analyzed_at' => now(),
            ]);

            Log::info('Reply sentiment analysis completed', [
                'reply_id' => $reply->id,
                'ticket_id' => $reply->ticket_id,
                'sentiment_label' => $result['sentiment_label'],
                'sentiment_score' => $result['sentiment_score'],
                'confidence' => $result['sentiment_confidence'],
            ]);

        } else {
            Log::warning('Reply sentiment analysis failed', [
                'reply_id' => $reply->id,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Analyze sentiment for batch of unanalyzed tickets/replies
     */
    protected function analyzeBatch(SentimentAnalysisService $service): void
    {
        $batchSize = 10; // Process 10 items at a time to respect rate limits

        // Get unanalyzed tickets
        $tickets = Ticket::where('company_id', $this->companyId)
            ->whereNull('sentiment_analyzed_at')
            ->limit($batchSize)
            ->get();

        foreach ($tickets as $ticket) {
            try {
                // Dispatch individual job for each ticket
                AnalyzeTicketSentiment::dispatch($this->companyId, 'ticket', $ticket->id)
                    ->delay(now()->addSeconds(2)); // Small delay to respect rate limits
            } catch (Exception $e) {
                Log::error('Failed to dispatch ticket sentiment analysis job', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Get unanalyzed replies
        $replies = TicketReply::where('company_id', $this->companyId)
            ->whereNull('sentiment_analyzed_at')
            ->limit($batchSize)
            ->get();

        foreach ($replies as $reply) {
            try {
                // Dispatch individual job for each reply
                AnalyzeTicketSentiment::dispatch($this->companyId, 'reply', null, $reply->id)
                    ->delay(now()->addSeconds(3)); // Small delay to respect rate limits
            } catch (Exception $e) {
                Log::error('Failed to dispatch reply sentiment analysis job', [
                    'reply_id' => $reply->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Batch sentiment analysis dispatched', [
            'company_id' => $this->companyId,
            'tickets_queued' => $tickets->count(),
            'replies_queued' => $replies->count(),
        ]);

        // If there are more items to process, schedule another batch job
        $remainingTickets = Ticket::where('company_id', $this->companyId)
            ->whereNull('sentiment_analyzed_at')
            ->count();

        $remainingReplies = TicketReply::where('company_id', $this->companyId)
            ->whereNull('sentiment_analyzed_at')
            ->count();

        if ($remainingTickets > 0 || $remainingReplies > 0) {
            AnalyzeTicketSentiment::dispatch($this->companyId, 'batch')
                ->delay(now()->addMinutes(2)); // Process next batch in 2 minutes
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        Log::error('Sentiment analysis job permanently failed', [
            'company_id' => $this->companyId,
            'analysis_type' => $this->analysisType,
            'ticket_id' => $this->ticketId,
            'reply_id' => $this->replyId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts,
        ]);
    }

    /**
     * Get unique job ID for preventing duplicates
     */
    public function uniqueId(): string
    {
        return "sentiment_analysis_{$this->companyId}_{$this->analysisType}_{$this->ticketId}_{$this->replyId}";
    }

    /**
     * Static method to queue sentiment analysis for a ticket
     */
    public static function queueTicketAnalysis(int $companyId, int $ticketId): void
    {
        self::dispatch($companyId, 'ticket', $ticketId);
    }

    /**
     * Static method to queue sentiment analysis for a reply
     */
    public static function queueReplyAnalysis(int $companyId, int $replyId): void
    {
        self::dispatch($companyId, 'reply', null, $replyId);
    }

    /**
     * Static method to queue batch sentiment analysis
     */
    public static function queueBatchAnalysis(int $companyId): void
    {
        self::dispatch($companyId, 'batch');
    }
}
