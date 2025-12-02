<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasAIAnalysis
{
    /**
     * Get AI insights from model
     */
    public function getAIInsights(): array
    {
        return array_filter([
            'summary' => $this->ai_summary ?? null,
            'sentiment' => $this->ai_sentiment ?? null,
            'category' => $this->ai_category ?? null,
            'confidence' => $this->ai_category_confidence ?? null,
            'priority' => $this->ai_priority_suggestion ?? null,
            'suggestions' => $this->ai_suggestions ?? null,
            'analyzed_at' => $this->ai_analyzed_at ?? null,
        ]);
    }

    /**
     * Check if needs fresh AI analysis
     */
    public function needsAIAnalysis(): bool
    {
        // Never analyzed
        if (!$this->ai_analyzed_at) {
            return true;
        }

        // Analysis older than 24 hours
        if ($this->ai_analyzed_at->lt(now()->subHours(24))) {
            return true;
        }

        // Model updated after last analysis
        if ($this->updated_at && $this->updated_at->gt($this->ai_analyzed_at)) {
            return true;
        }

        return false;
    }

    /**
     * Mark as analyzed
     */
    public function markAsAIAnalyzed(array $insights): void
    {
        $updateData = [
            'ai_analyzed_at' => now(),
        ];

        if (isset($insights['summary'])) {
            $updateData['ai_summary'] = $insights['summary'];
        }

        if (isset($insights['sentiment'])) {
            $updateData['ai_sentiment'] = $insights['sentiment'];
        }

        if (isset($insights['category'])) {
            $updateData['ai_category'] = $insights['category'];
        }

        if (isset($insights['confidence'])) {
            $updateData['ai_category_confidence'] = $insights['confidence'];
        }

        if (isset($insights['priority'])) {
            $updateData['ai_priority_suggestion'] = $insights['priority'];
        }

        if (isset($insights['suggestions'])) {
            $updateData['ai_suggestions'] = is_array($insights['suggestions'])
                ? json_encode($insights['suggestions'])
                : $insights['suggestions'];
        }

        $this->update($updateData);
    }

    /**
     * Scope: needs AI analysis
     */
    public function scopeNeedsAIAnalysis(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('ai_analyzed_at')
                ->orWhere('ai_analyzed_at', '<', now()->subHours(24))
                ->orWhereColumn('updated_at', '>', 'ai_analyzed_at');
        });
    }

    /**
     * Scope: recently analyzed
     */
    public function scopeRecentlyAnalyzed(Builder $query): Builder
    {
        return $query->whereNotNull('ai_analyzed_at')
            ->where('ai_analyzed_at', '>=', now()->subHours(24));
    }

    /**
     * Check if was analyzed recently (within 24 hours)
     */
    public function wasAnalyzedRecently(): bool
    {
        return $this->ai_analyzed_at && $this->ai_analyzed_at->gte(now()->subHours(24));
    }
}
