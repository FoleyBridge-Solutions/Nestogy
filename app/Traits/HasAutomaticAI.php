<?php

namespace App\Traits;

use App\Domains\Core\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Auth;

trait HasAutomaticAI
{
    // AI State
    public bool $aiEnabled = false;
    public bool $aiLoading = false;
    public ?string $aiError = null;
    public array $aiInsights = [];

    /**
     * Initialize AI - call this in mount()
     */
    protected function initializeAI($model)
    {
        $aiService = new OpenRouterService(Auth::user()->company);

        $this->aiEnabled = $aiService->isConfigured();

        if (!$this->aiEnabled) {
            return;
        }

        // Load cached insights immediately (fast)
        $this->loadCachedAIInsights($model);

        // Queue fresh analysis if needed (async)
        if ($this->shouldAnalyze($model)) {
            $this->queueAIAnalysis($model);
        }
    }

    /**
     * Load from cache or database
     */
    protected function loadCachedAIInsights($model)
    {
        $cacheKey = $this->getAICacheKey($model);
        $cached = cache()->get($cacheKey);

        if ($cached) {
            $this->aiInsights = $cached;
            return;
        }

        // Load from model if it has AI fields
        if (method_exists($model, 'getAIInsights')) {
            $this->aiInsights = $model->getAIInsights();
        }
    }

    /**
     * Check if model needs fresh AI analysis
     */
    protected function shouldAnalyze($model): bool
    {
        if (!method_exists($model, 'needsAIAnalysis')) {
            return true; // Default: always analyze if no method
        }

        return $model->needsAIAnalysis();
    }

    /**
     * Queue background job for AI analysis
     */
    protected function queueAIAnalysis($model)
    {
        $jobClass = $this->getAIJobClass();

        if (class_exists($jobClass)) {
            $analysisType = $this->getAIAnalysisType();
            dispatch(new $jobClass($model, $analysisType));
        }
    }

    /**
     * Get AI job class for this model type
     * Override in component if needed
     */
    protected function getAIJobClass(): string
    {
        return \App\Jobs\AnalyzeWithAI::class;
    }

    /**
     * Get analysis type for this component
     * Override in component: 'ticket', 'email', 'document', 'generic'
     */
    protected function getAIAnalysisType(): string
    {
        return 'generic';
    }

    /**
     * Get cache key for AI insights
     */
    protected function getAICacheKey($model): string
    {
        $modelClass = class_basename($model);
        return strtolower($modelClass).".{$model->id}.ai_insights";
    }

    /**
     * Refresh AI insights manually (optional button)
     */
    public function refreshAI()
    {
        $model = $this->getModel();

        if (!$model) {
            $this->aiError = 'No model to analyze';
            return;
        }

        $this->aiLoading = true;
        $this->aiError = null;

        try {
            $aiService = new OpenRouterService(Auth::user()->company);

            if (!$aiService->isConfigured()) {
                $this->aiError = 'AI not configured';
                return;
            }

            // Run AI analysis - override performAIAnalysis() in component
            $this->aiInsights = $this->performAIAnalysis($aiService);

            // Cache results
            cache()->put(
                $this->getAICacheKey($model),
                $this->aiInsights,
                now()->addHours(24)
            );

            // Update model if it has the method
            if (method_exists($model, 'markAsAIAnalyzed')) {
                $model->markAsAIAnalyzed($this->aiInsights);
            }

        } catch (\Exception $e) {
            $this->aiError = 'AI analysis failed';
            \Log::error('AI refresh failed', [
                'component' => get_class($this),
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->aiLoading = false;
        }
    }

    /**
     * Override this to define what AI analysis to perform
     */
    protected function performAIAnalysis($aiService): array
    {
        return [];
    }

    /**
     * Override this to return the model being analyzed
     */
    protected function getModel()
    {
        return null;
    }

    /**
     * Handle real-time AI updates from broadcasting
     */
    public function handleAIUpdate($event)
    {
        $this->aiInsights = array_merge($this->aiInsights, $event);
        $this->aiLoading = false;
    }
}
