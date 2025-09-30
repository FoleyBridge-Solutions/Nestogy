<?php

namespace App\Domains\Financial\Services\TaxEngine;

use App\Models\TaxApiQueryCache;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sentiment Analysis Service using API Ninjas
 *
 * Provides sentiment analysis for tickets and replies using the API Ninjas
 * Sentiment API. Follows the established BaseApiClient pattern for consistency
 * with caching, rate limiting, and error handling.
 */
class SentimentAnalysisService extends BaseApiClient
{
    protected string $baseUrl = 'https://api.api-ninjas.com/v1';

    protected string $apiKey;

    public function __construct(int $companyId, array $config = [])
    {
        $this->apiKey = config('services.api_ninjas.key') ?? config('services.api_ninjas.key');

        if (! $this->apiKey) {
            throw new Exception('API Ninjas API key not configured for sentiment analysis');
        }

        parent::__construct($companyId, 'api_ninjas_sentiment', $config);
    }

    /**
     * Get rate limits for API Ninjas Sentiment API
     */
    protected function getRateLimits(): array
    {
        return [
            'sentiment_analysis' => [
                'max_requests' => 100, // API Ninjas allows good rate limits
                'window' => 60, // per minute
            ],
            'batch_analysis' => [
                'max_requests' => 50,
                'window' => 60,
            ],
        ];
    }

    /**
     * Analyze sentiment for a single text
     */
    public function analyzeSentiment(string $text): array
    {
        if (empty(trim($text))) {
            return $this->getEmptyTextResponse();
        }

        // Truncate text to API limit (2000 characters)
        $text = substr(trim($text), 0, 2000);

        $parameters = ['text' => $text];

        return $this->makeRequest(
            'sentiment_analysis',
            $parameters,
            function () use ($text) {
                return $this->callSentimentApi($text);
            },
            7 // Cache sentiment analysis for 7 days
        );
    }

    /**
     * Analyze sentiment for multiple texts in batch
     */
    public function analyzeBatchSentiment(array $texts): array
    {
        $results = [];

        foreach ($texts as $key => $text) {
            try {
                $results[$key] = $this->analyzeSentiment($text);
            } catch (Exception $e) {
                Log::warning('Batch sentiment analysis failed for text', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                    'company_id' => $this->companyId,
                ]);

                $results[$key] = $this->getErrorResponse($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Call the API Ninjas Sentiment API
     */
    protected function callSentimentApi(string $text): array
    {
        $response = Http::withHeaders([
            'X-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->config['timeout'])
            ->retry($this->config['retry_attempts'], $this->config['retry_delay'])
            ->get($this->baseUrl.'/sentiment', [
                'text' => $text,
            ]);

        if (! $response->successful()) {
            throw new Exception("API Ninjas Sentiment API request failed: HTTP {$response->status()}");
        }

        $data = $response->json();

        if (! isset($data['score']) || ! isset($data['sentiment'])) {
            throw new Exception('Invalid response from API Ninjas Sentiment API');
        }

        return $this->formatSentimentResponse($data, $text);
    }

    /**
     * Format the sentiment response into standardized format
     */
    protected function formatSentimentResponse(array $apiData, string $originalText): array
    {
        $score = (float) $apiData['score'];
        $sentiment = strtoupper($apiData['sentiment']);

        // Map API Ninjas sentiment labels to our enum values
        $mappedSentiment = $this->mapSentimentLabel($sentiment);

        // Calculate confidence based on absolute score value
        $confidence = abs($score);

        return [
            'success' => true,
            'sentiment_score' => round($score, 2),
            'sentiment_label' => $mappedSentiment,
            'sentiment_confidence' => round($confidence, 2),
            'original_sentiment' => $sentiment,
            'text_length' => strlen($originalText),
            'analyzed_at' => now()->toISOString(),
            'provider' => 'api_ninjas',
            'api_response' => $apiData,
        ];
    }

    /**
     * Map API Ninjas sentiment labels to our enum values
     */
    protected function mapSentimentLabel(string $apiSentiment): string
    {
        return match ($apiSentiment) {
            'POSITIVE' => 'POSITIVE',
            'WEAK_POSITIVE' => 'WEAK_POSITIVE',
            'NEUTRAL' => 'NEUTRAL',
            'WEAK_NEGATIVE' => 'WEAK_NEGATIVE',
            'NEGATIVE' => 'NEGATIVE',
            default => 'NEUTRAL'
        };
    }

    /**
     * Get response for empty text
     */
    protected function getEmptyTextResponse(): array
    {
        return [
            'success' => true,
            'sentiment_score' => 0.0,
            'sentiment_label' => 'NEUTRAL',
            'sentiment_confidence' => 0.0,
            'original_sentiment' => 'NEUTRAL',
            'text_length' => 0,
            'analyzed_at' => now()->toISOString(),
            'provider' => 'api_ninjas',
            'message' => 'Empty text provided',
        ];
    }

    /**
     * Get error response
     */
    protected function getErrorResponse(string $error): array
    {
        return [
            'success' => false,
            'sentiment_score' => null,
            'sentiment_label' => null,
            'sentiment_confidence' => null,
            'error' => $error,
            'analyzed_at' => now()->toISOString(),
            'provider' => 'api_ninjas',
        ];
    }

    /**
     * Test the API connection
     */
    public function testConnection(): array
    {
        try {
            $testText = 'This is a test message to verify the sentiment analysis API is working correctly.';
            $result = $this->analyzeSentiment($testText);

            return [
                'success' => $result['success'],
                'test_text' => $testText,
                'sentiment_result' => $result,
                'api_status' => 'API Ninjas Sentiment API connection successful',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'api_status' => 'API Ninjas Sentiment API connection failed',
            ];
        }
    }

    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        return [
            'configured' => ! empty($this->apiKey),
            'service' => 'API Ninjas Sentiment Analysis',
            'coverage' => 'Text sentiment analysis with confidence scores',
            'cost' => 'Paid API service - cost-effective sentiment analysis',
            'features' => [
                'Real-time sentiment analysis',
                'Confidence scoring',
                'Multiple sentiment levels (5-point scale)',
                'Fast response times',
                'High accuracy',
                'Batch processing support',
            ],
            'rate_limits' => $this->rateLimits,
            'cache_enabled' => $this->config['enable_caching'],
            'api_key_configured' => ! empty($this->apiKey),
        ];
    }

    /**
     * Get sentiment statistics for the company
     */
    public function getSentimentStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $stats = TaxApiQueryCache::where('company_id', $this->companyId)
            ->where('api_provider', $this->provider)
            ->where('query_type', 'sentiment_analysis')
            ->whereBetween('api_called_at', [$startDate, $endDate])
            ->where('status', TaxApiQueryCache::STATUS_SUCCESS)
            ->get();

        $totalAnalyzed = $stats->count();
        $avgResponseTime = $stats->avg('response_time_ms');

        // Extract sentiment results from cached responses
        $sentiments = [];
        foreach ($stats as $stat) {
            $response = $stat->api_response;
            if (isset($response['sentiment_label'])) {
                $sentiments[] = $response['sentiment_label'];
            }
        }

        $sentimentCounts = array_count_values($sentiments);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_analyzed' => $totalAnalyzed,
            'avg_response_time_ms' => round($avgResponseTime ?? 0, 2),
            'sentiment_distribution' => [
                'POSITIVE' => $sentimentCounts['POSITIVE'] ?? 0,
                'WEAK_POSITIVE' => $sentimentCounts['WEAK_POSITIVE'] ?? 0,
                'NEUTRAL' => $sentimentCounts['NEUTRAL'] ?? 0,
                'WEAK_NEGATIVE' => $sentimentCounts['WEAK_NEGATIVE'] ?? 0,
                'NEGATIVE' => $sentimentCounts['NEGATIVE'] ?? 0,
            ],
            'sentiment_percentages' => $totalAnalyzed > 0 ? [
                'POSITIVE' => round((($sentimentCounts['POSITIVE'] ?? 0) / $totalAnalyzed) * 100, 1),
                'WEAK_POSITIVE' => round((($sentimentCounts['WEAK_POSITIVE'] ?? 0) / $totalAnalyzed) * 100, 1),
                'NEUTRAL' => round((($sentimentCounts['NEUTRAL'] ?? 0) / $totalAnalyzed) * 100, 1),
                'WEAK_NEGATIVE' => round((($sentimentCounts['WEAK_NEGATIVE'] ?? 0) / $totalAnalyzed) * 100, 1),
                'NEGATIVE' => round((($sentimentCounts['NEGATIVE'] ?? 0) / $totalAnalyzed) * 100, 1),
            ] : [],
        ];
    }

    /**
     * Get sentiment score interpretation
     */
    public static function interpretSentimentScore(float $score): array
    {
        $absScore = abs($score);

        if ($score > 0.5) {
            $interpretation = 'Very Positive';
            $color = '#10b981'; // emerald-500
        } elseif ($score > 0.1) {
            $interpretation = 'Positive';
            $color = '#84cc16'; // lime-500
        } elseif ($score > -0.1) {
            $interpretation = 'Neutral';
            $color = '#64748b'; // slate-500
        } elseif ($score > -0.5) {
            $interpretation = 'Negative';
            $color = '#f97316'; // orange-500
        } else {
            $interpretation = 'Very Negative';
            $color = '#ef4444'; // red-500
        }

        return [
            'interpretation' => $interpretation,
            'color' => $color,
            'confidence_level' => $absScore > 0.7 ? 'High' : ($absScore > 0.3 ? 'Medium' : 'Low'),
        ];
    }
}
