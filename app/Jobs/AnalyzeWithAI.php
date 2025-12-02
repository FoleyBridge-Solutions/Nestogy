<?php

namespace App\Jobs;

use App\Domains\Core\Services\AI\OpenRouterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeWithAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $model;
    public string $analysisType;

    /**
     * Create a new job instance.
     */
    public function __construct($model, string $analysisType = 'generic')
    {
        $this->model = $model;
        $this->analysisType = $analysisType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get company from model
        if (!property_exists($this->model, 'company_id') && !method_exists($this->model, 'company')) {
            Log::warning('Model does not have company relationship', [
                'model' => get_class($this->model),
                'id' => $this->model->id,
            ]);
            return;
        }

        $company = $this->model->company ?? \App\Domains\Company\Models\Company::find($this->model->company_id);

        if (!$company) {
            Log::warning('Could not find company for AI analysis', [
                'model' => get_class($this->model),
                'id' => $this->model->id,
            ]);
            return;
        }

        $aiService = new OpenRouterService($company);

        if (!$aiService->isConfigured()) {
            Log::info('AI not configured for company', [
                'company_id' => $company->id,
                'model' => get_class($this->model),
            ]);
            return;
        }

        try {
            $insights = [];

            // Get the text to analyze
            $text = $this->getAnalysisText();

            if (empty($text)) {
                Log::warning('No text to analyze', [
                    'model' => get_class($this->model),
                    'id' => $this->model->id,
                ]);
                return;
            }

            // Perform analysis based on type
            switch ($this->analysisType) {
                case 'ticket':
                    $insights = $this->analyzeTicket($aiService, $text);
                    break;

                case 'client':
                    $insights = $this->analyzeClient($aiService, $text);
                    break;

                case 'project':
                    $insights = $this->analyzeProject($aiService, $text);
                    break;

                case 'email_message':
                    $insights = $this->analyzeEmailMessage($aiService, $text);
                    break;

                case 'kb_article':
                    $insights = $this->analyzeKbArticle($aiService, $text);
                    break;

                case 'contract':
                    $insights = $this->analyzeContract($aiService, $text);
                    break;

                case 'lead':
                    $insights = $this->analyzeLead($aiService, $text);
                    break;

                case 'email':
                    $insights = $this->analyzeEmail($aiService, $text);
                    break;

                case 'document':
                    $insights = $this->analyzeDocument($aiService, $text);
                    break;

                default:
                    $insights = $this->analyzeGeneric($aiService, $text);
            }

            // Update model if it has the trait
            if (method_exists($this->model, 'markAsAIAnalyzed')) {
                $this->model->markAsAIAnalyzed($insights);
            }

            // Cache results
            $cacheKey = $this->getCacheKey();
            cache()->put($cacheKey, $insights, now()->addHours(24));

            // Broadcast update
            $this->broadcastUpdate($insights);

            Log::info('AI analysis completed', [
                'model' => get_class($this->model),
                'id' => $this->model->id,
                'type' => $this->analysisType,
            ]);

        } catch (\Exception $e) {
            Log::error('AI analysis job failed', [
                'model' => get_class($this->model),
                'id' => $this->model->id,
                'type' => $this->analysisType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Analyze ticket
     */
    protected function analyzeTicket($aiService, $text): array
    {
        $subject = $this->model->subject ?? '';
        $fullContext = "Subject: {$subject}\n\n{$text}";

        $classification = $aiService->classify($text, [
            'Hardware Issue',
            'Software Issue',
            'Network Problem',
            'Email Issue',
            'Password Reset',
            'Account Access',
            'Performance Issue',
            'Other',
        ]);

        return [
            'summary' => $aiService->summarize($text, 200),
            'sentiment' => $aiService->analyze($text, 'sentiment')['content'],
            'category' => $classification['category'],
            'confidence' => $classification['confidence'],
            'suggestions' => $aiService->suggest($fullContext, 'troubleshooting', 5),
            'priority' => $this->extractPriority(
                $aiService->analyze($text, 'ticket')['content']
            ),
        ];
    }

    /**
     * Analyze client
     */
    protected function analyzeClient($aiService, $text): array
    {
        $notes = $this->model->notes ?? $text;
        
        $healthAnalysis = $aiService->analyze($notes, 'client_health');
        
        return [
            'summary' => $aiService->summarize($notes, 200),
            'health_score' => $this->extractHealthScore($healthAnalysis['content']),
            'risk_level' => $this->extractRiskLevel($healthAnalysis['content']),
            'client_type' => $this->extractClientType($healthAnalysis['content']),
            'insights' => $aiService->suggest($notes, 'client_improvements', 5),
        ];
    }

    /**
     * Analyze project
     */
    protected function analyzeProject($aiService, $text): array
    {
        $description = $this->model->description ?? $text;
        
        $riskAnalysis = $aiService->analyze($description, 'project_risk');
        
        return [
            'summary' => $aiService->summarize($description, 200),
            'risk_level' => $this->extractRiskLevel($riskAnalysis['content']),
            'progress_assessment' => $this->extractProgressAssessment($riskAnalysis['content']),
            'recommendations' => $aiService->suggest($description, 'project_improvements', 5),
        ];
    }

    /**
     * Analyze email message
     */
    protected function analyzeEmailMessage($aiService, $text): array
    {
        $subject = $this->model->subject ?? '';
        $fullContext = "Subject: {$subject}\n\n{$text}";
        
        $sentiment = $aiService->analyze($text, 'sentiment');
        
        return [
            'summary' => $aiService->summarize($text, 150),
            'sentiment' => $sentiment['content'],
            'priority' => $this->extractEmailPriority($sentiment['content'], $subject),
            'suggested_reply' => $aiService->generate('email_reply', [
                'email_content' => $text,
                'sender' => $this->model->from_name ?? $this->model->from_address,
            ]),
            'action_items' => $aiService->extract($text, 'action_items'),
        ];
    }

    /**
     * Analyze knowledge base article
     */
    protected function analyzeKbArticle($aiService, $text): array
    {
        $title = $this->model->title ?? '';
        $fullContext = "Title: {$title}\n\n{$text}";
        
        return [
            'summary' => $aiService->summarize($text, 200),
            'suggested_title' => $aiService->suggest($title, 'better_title', 1)[0] ?? $title,
            'suggested_tags' => $aiService->extract($text, 'tags'),
            'related_topics' => $aiService->suggest($text, 'related_topics', 5),
            'improvements' => $aiService->suggest($text, 'article_improvements', 5),
            'reading_time' => $this->estimateReadingTime($text),
        ];
    }

    /**
     * Analyze contract
     */
    protected function analyzeContract($aiService, $text): array
    {
        $title = $this->model->title ?? '';
        $fullContext = "Contract Title: {$title}\n\n{$text}";
        
        return [
            'summary' => $aiService->summarize($text, 300),
            'key_terms' => $aiService->extract($text, 'key_contract_terms'),
            'risk_factors' => $aiService->extract($text, 'contract_risks'),
            'compliance_status' => $aiService->analyze($text, 'contract_compliance')['content'],
            'renewal_recommendation' => $aiService->analyze($text, 'renewal_recommendation')['content'],
            'important_dates' => $aiService->extract($text, 'important_dates'),
        ];
    }

    /**
     * Analyze lead
     */
    protected function analyzeLead($aiService, $text): array
    {
        $notes = $this->model->notes ?? $text;
        $context = "Lead: {$this->model->first_name} {$this->model->last_name} from {$this->model->company_name}\n\nNotes: {$notes}";
        
        return [
            'summary' => $aiService->summarize($context, 150),
            'quality_score' => $this->extractQualityScore($aiService->analyze($context, 'lead_quality')['content']),
            'conversion_likelihood' => $this->extractConversionLikelihood($aiService->analyze($context, 'conversion_likelihood')['content']),
            'suggested_approach' => $aiService->suggest($context, 'sales_approach', 3),
            'key_insights' => $aiService->extract($context, 'lead_insights'),
        ];
    }

    /**
     * Analyze email
     */
    protected function analyzeEmail($aiService, $text): array
    {
        $sentiment = $aiService->analyze($text, 'sentiment');

        return [
            'summary' => $aiService->summarize($text, 150),
            'sentiment' => $sentiment['content'],
            'priority' => $this->extractPriority($sentiment['content']),
        ];
    }

    /**
     * Analyze document
     */
    protected function analyzeDocument($aiService, $text): array
    {
        return [
            'summary' => $aiService->summarize($text, 300),
            'key_points' => $aiService->suggest($text, 'key_points', 5),
        ];
    }

    /**
     * Generic analysis
     */
    protected function analyzeGeneric($aiService, $text): array
    {
        return [
            'summary' => $aiService->summarize($text, 200),
            'analysis' => $aiService->analyze($text, 'general')['content'],
        ];
    }

    /**
     * Get text to analyze from model
     */
    protected function getAnalysisText(): string
    {
        // Try common text fields
        $fields = ['description', 'content', 'body', 'message', 'text'];

        foreach ($fields as $field) {
            if (isset($this->model->$field) && !empty($this->model->$field)) {
                return $this->model->$field;
            }
        }

        // For tickets, combine subject and description
        if (isset($this->model->subject) && isset($this->model->description)) {
            return $this->model->subject."\n\n".$this->model->description;
        }

        return '';
    }

    /**
     * Extract priority from AI analysis
     */
    protected function extractPriority(string $analysis): string
    {
        $analysis = strtolower($analysis);

        if (
            str_contains($analysis, 'critical') ||
            str_contains($analysis, 'urgent') ||
            str_contains($analysis, 'emergency')
        ) {
            return 'high';
        }

        if (
            str_contains($analysis, 'medium') ||
            str_contains($analysis, 'moderate')
        ) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Extract health score from AI analysis
     */
    protected function extractHealthScore(string $analysis): string
    {
        $analysis = strtolower($analysis);

        if (str_contains($analysis, 'excellent') || str_contains($analysis, 'great')) {
            return 'Excellent';
        }
        if (str_contains($analysis, 'good') || str_contains($analysis, 'healthy')) {
            return 'Good';
        }
        if (str_contains($analysis, 'fair') || str_contains($analysis, 'okay')) {
            return 'Fair';
        }
        if (str_contains($analysis, 'poor') || str_contains($analysis, 'at risk')) {
            return 'Poor';
        }

        return 'Good';
    }

    /**
     * Extract risk level from AI analysis
     */
    protected function extractRiskLevel(string $analysis): string
    {
        $analysis = strtolower($analysis);

        if (str_contains($analysis, 'high risk') || str_contains($analysis, 'critical')) {
            return 'High';
        }
        if (str_contains($analysis, 'medium risk') || str_contains($analysis, 'moderate')) {
            return 'Medium';
        }

        return 'Low';
    }

    /**
     * Extract client type from AI analysis
     */
    protected function extractClientType(string $analysis): string
    {
        $analysis = strtolower($analysis);

        if (str_contains($analysis, 'enterprise')) {
            return 'Enterprise';
        }
        if (str_contains($analysis, 'small business') || str_contains($analysis, 'smb')) {
            return 'Small Business';
        }
        if (str_contains($analysis, 'startup')) {
            return 'Startup';
        }

        return 'Standard';
    }

    /**
     * Extract progress assessment from AI analysis
     */
    protected function extractProgressAssessment(string $analysis): string
    {
        $analysis = strtolower($analysis);

        if (str_contains($analysis, 'ahead of schedule') || str_contains($analysis, 'excellent progress')) {
            return 'Ahead of Schedule';
        }
        if (str_contains($analysis, 'on track') || str_contains($analysis, 'on schedule')) {
            return 'On Track';
        }
        if (str_contains($analysis, 'behind') || str_contains($analysis, 'delayed')) {
            return 'Behind Schedule';
        }
        if (str_contains($analysis, 'at risk') || str_contains($analysis, 'critical')) {
            return 'At Risk';
        }

        return 'On Track';
    }

    /**
     * Extract email priority from AI analysis
     */
    protected function extractEmailPriority(string $analysis, string $subject): string
    {
        $analysis = strtolower($analysis);
        $subject = strtolower($subject);

        // High priority indicators
        $highPriorityWords = ['urgent', 'asap', 'immediately', 'critical', 'emergency', 'breaking', 'deadline'];
        foreach ($highPriorityWords as $word) {
            if (str_contains($analysis, $word) || str_contains($subject, $word)) {
                return 'High';
            }
        }

        // Medium priority indicators
        $mediumPriorityWords = ['soon', 'today', 'tomorrow', 'week', 'meeting', 'review'];
        foreach ($mediumPriorityWords as $word) {
            if (str_contains($analysis, $word) || str_contains($subject, $word)) {
                return 'Medium';
            }
        }

        return 'Low';
    }

    /**
     * Estimate reading time for text
     */
    protected function estimateReadingTime(string $text): int
    {
        $wordCount = str_word_count(strip_tags($text));
        $wordsPerMinute = 200; // Average reading speed
        return ceil($wordCount / $wordsPerMinute);
    }

    /**
     * Extract quality score from AI analysis
     */
    protected function extractQualityScore(string $analysis): string
    {
        $analysis = strtolower($analysis);

        if (str_contains($analysis, 'excellent') || str_contains($analysis, 'high quality')) {
            return 'Excellent';
        }
        if (str_contains($analysis, 'good') || str_contains($analysis, 'decent')) {
            return 'Good';
        }
        if (str_contains($analysis, 'fair') || str_contains($analysis, 'average')) {
            return 'Fair';
        }
        if (str_contains($analysis, 'poor') || str_contains($analysis, 'low quality')) {
            return 'Poor';
        }

        return 'Good';
    }

    /**
     * Extract conversion likelihood from AI analysis
     */
    protected function extractConversionLikelihood(string $analysis): string
    {
        $analysis = strtolower($analysis);

        if (str_contains($analysis, 'very likely') || str_contains($analysis, 'high')) {
            return 'Very Likely';
        }
        if (str_contains($analysis, 'likely') || str_contains($analysis, 'good chance')) {
            return 'Likely';
        }
        if (str_contains($analysis, 'unlikely') || str_contains($analysis, 'low')) {
            return 'Unlikely';
        }
        if (str_contains($analysis, 'very unlikely') || str_contains($analysis, 'poor')) {
            return 'Very Unlikely';
        }

        return 'Moderate';
    }

    /**
     * Get cache key
     */
    protected function getCacheKey(): string
    {
        $class = class_basename($this->model);
        return strtolower($class).".{$this->model->id}.ai_insights";
    }

    /**
     * Broadcast update event
     */
    protected function broadcastUpdate($insights): void
    {
        $class = class_basename($this->model);
        $eventClass = "App\\Events\\{$class}AIAnalyzed";

        if (class_exists($eventClass)) {
            try {
                broadcast(new $eventClass($this->model, $insights));
            } catch (\Exception $e) {
                Log::warning('Failed to broadcast AI update', [
                    'event' => $eventClass,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
