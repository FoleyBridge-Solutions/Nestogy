<?php

namespace App\Domains\Core\Services\AI;

use App\Domains\Company\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenRouterService
{
    protected Company $company;
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected array $defaultHeaders;
    protected array $aiSettings;

    /**
     * Create a new OpenRouterService instance
     *
     * @param Company $company The company whose AI settings to use
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
        $this->aiSettings = $company->ai_settings ?? [];
        
        // Get API key from company settings
        $this->apiKey = $this->aiSettings['openrouter_api_key'] ?? null;
        
        // Base URL is static, not per-company
        $this->baseUrl = config('openrouter.base_url', 'https://openrouter.ai/api/v1');
        
        // Get default model from company settings or fallback to config
        $this->defaultModel = $this->aiSettings['default_model'] ?? config('openrouter.default_model', 'openai/gpt-3.5-turbo');
        
        $this->defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name') . ' - ' . $this->company->name,
        ];
    }

    /**
     * Send a request to OpenRouter API
     *
     * @param array $messages Array of message objects with 'role' and 'content'
     * @param string|null $model Override default model
     * @param array $options Additional options (temperature, max_tokens, etc.)
     * @return array Raw API response
     * @throws Exception
     */
    public function sendRequest(array $messages, ?string $model = null, array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('OpenRouter AI service is not configured for this company. Please add your API key in company settings.');
        }

        $model = $model ?? $this->defaultModel;

        // Merge company-specific defaults with request options
        $defaultOptions = [
            'temperature' => $this->aiSettings['temperature'] ?? 0.7,
            'max_tokens' => $this->aiSettings['max_tokens'] ?? 1000,
        ];

        $payload = array_merge(
            $defaultOptions,
            [
                'model' => $model,
                'messages' => $messages,
            ],
            $options
        );

        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->timeout(config('openrouter.timeout', 60))
                ->post($this->baseUrl . '/chat/completions', $payload);

            if ($response->failed()) {
                Log::error('OpenRouter API request failed', [
                    'company_id' => $this->company->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('OpenRouter API request failed: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('OpenRouter API exception', [
                'company_id' => $this->company->id,
                'message' => $e->getMessage(),
                'model' => $model,
            ]);
            throw $e;
        }
    }

    /**
     * Generate a simple text completion
     *
     * @param string $prompt The user prompt
     * @param string|null $systemPrompt Optional system message
     * @param string|null $model Override default model
     * @return string The AI response text
     */
    public function complete(string $prompt, ?string $systemPrompt = null, ?string $model = null): string
    {
        $messages = [];
        
        if ($systemPrompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $this->sendRequest($messages, $model);
        
        return $this->formatAsText($response);
    }

    /**
     * Analyze text and return structured analysis
     *
     * @param string $text Text to analyze
     * @param string $analysisType Type of analysis (sentiment, summary, etc.)
     * @param array $options Additional options
     * @return array Structured analysis results
     */
    public function analyze(string $text, string $analysisType = 'general', array $options = []): array
    {
        $systemPrompt = $this->getAnalysisSystemPrompt($analysisType);
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text],
        ];

        $response = $this->sendRequest($messages, $options['model'] ?? null, $options);
        
        return $this->formatAsAnalysis($response, $analysisType);
    }

    /**
     * Summarize long text
     *
     * @param string $text Text to summarize
     * @param int $maxLength Maximum summary length
     * @return string Summary text
     */
    public function summarize(string $text, int $maxLength = 200): string
    {
        $systemPrompt = "You are a helpful assistant that creates concise summaries. Summarize the following text in approximately {$maxLength} characters or less.";
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text],
        ];

        $response = $this->sendRequest($messages, null, [
            'max_tokens' => ceil($maxLength / 3), // Rough estimate: 1 token ≈ 3-4 chars
        ]);
        
        return $this->formatAsText($response);
    }

    /**
     * Generate suggestions or recommendations
     *
     * @param string $context Context for suggestions
     * @param string $type Type of suggestions needed
     * @param int $count Number of suggestions
     * @return array List of suggestions
     */
    public function suggest(string $context, string $type = 'general', int $count = 5): array
    {
        $systemPrompt = "You are a helpful assistant that provides practical suggestions. Generate exactly {$count} suggestions based on the context provided. Return only a numbered list.";
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Type: {$type}\nContext: {$context}"],
        ];

        $response = $this->sendRequest($messages);
        
        return $this->formatAsList($response);
    }

    /**
     * Classify text into categories
     *
     * @param string $text Text to classify
     * @param array $categories Available categories
     * @return array Classification results with confidence
     */
    public function classify(string $text, array $categories): array
    {
        $categoriesList = implode(', ', $categories);
        $systemPrompt = "You are a text classifier. Classify the following text into one of these categories: {$categoriesList}. Respond with only the category name and a confidence score (0-100).";
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text],
        ];

        $response = $this->sendRequest($messages);
        
        return $this->formatAsClassification($response, $categories);
    }

    /**
     * Extract structured information from text
     *
     * @param string $text Text to extract from
     * @param array $fields Fields to extract
     * @return array Extracted data
     */
    public function extract(string $text, array $fields): array
    {
        $fieldsList = implode(', ', $fields);
        $systemPrompt = "You are a data extraction assistant. Extract the following fields from the text: {$fieldsList}. Return the results in a structured format using 'Field: Value' pairs.";
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text],
        ];

        $response = $this->sendRequest($messages);
        
        return $this->formatAsExtraction($response, $fields);
    }

    /**
     * Generate content based on a template
     *
     * @param string $template Template type (email, report, etc.)
     * @param array $data Data to fill the template
     * @return string Generated content
     */
    public function generate(string $template, array $data): string
    {
        $systemPrompt = $this->getGenerationSystemPrompt($template);
        $dataString = json_encode($data, JSON_PRETTY_PRINT);
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => "Generate content using this data:\n{$dataString}"],
        ];

        $response = $this->sendRequest($messages);
        
        return $this->formatAsText($response);
    }

    /**
     * Chat with conversation history
     *
     * @param array $conversationHistory Array of previous messages
     * @param string $newMessage New user message
     * @return string AI response
     */
    public function chat(array $conversationHistory, string $newMessage): string
    {
        $messages = $conversationHistory;
        $messages[] = [
            'role' => 'user',
            'content' => $newMessage,
        ];

        $response = $this->sendRequest($messages);
        
        return $this->formatAsText($response);
    }

    // ========== Response Formatters ==========

    /**
     * Format response as plain text
     */
    protected function formatAsText(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Format response as analysis results
     */
    protected function formatAsAnalysis(array $response, string $analysisType): array
    {
        $content = $this->formatAsText($response);
        
        return [
            'type' => $analysisType,
            'content' => $content,
            'raw_response' => $response,
            'model' => $response['model'] ?? null,
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
        ];
    }

    /**
     * Format response as list
     */
    protected function formatAsList(array $response): array
    {
        $content = $this->formatAsText($response);
        
        // Parse numbered list
        $lines = explode("\n", trim($content));
        $items = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Remove numbering (1. 2. etc.) or bullet points
            $cleaned = preg_replace('/^[\d\-\*\•]+\.?\s*/', '', $line);
            if (!empty($cleaned)) {
                $items[] = $cleaned;
            }
        }
        
        return $items;
    }

    /**
     * Format response as classification
     */
    protected function formatAsClassification(array $response, array $categories): array
    {
        $content = $this->formatAsText($response);
        
        // Try to extract category and confidence
        preg_match('/([^\d]+)\s*(\d+)/', $content, $matches);
        
        $category = isset($matches[1]) ? trim($matches[1]) : $content;
        $confidence = isset($matches[2]) ? (int)$matches[2] : 0;
        
        // Validate category
        if (!in_array($category, $categories)) {
            // Find closest match
            foreach ($categories as $cat) {
                if (stripos($content, $cat) !== false) {
                    $category = $cat;
                    break;
                }
            }
        }
        
        return [
            'category' => $category,
            'confidence' => $confidence,
            'raw_response' => $content,
        ];
    }

    /**
     * Format response as extracted data
     */
    protected function formatAsExtraction(array $response, array $fields): array
    {
        $content = $this->formatAsText($response);
        $extracted = [];
        
        foreach ($fields as $field) {
            // Try to find "Field: Value" pattern
            if (preg_match('/' . preg_quote($field, '/') . '\s*:?\s*(.+?)(?=\n|$)/i', $content, $matches)) {
                $extracted[$field] = trim($matches[1]);
            } else {
                $extracted[$field] = null;
            }
        }
        
        return $extracted;
    }

    // ========== Helper Methods ==========

    /**
     * Get system prompt for analysis type
     */
    protected function getAnalysisSystemPrompt(string $type): string
    {
        $prompts = [
            'sentiment' => 'You are a sentiment analysis expert. Analyze the sentiment of the following text and provide a detailed assessment including overall sentiment (positive, negative, neutral), key emotions, and intensity.',
            'summary' => 'You are an expert at creating concise, accurate summaries. Summarize the main points of the following text.',
            'general' => 'You are an analytical assistant. Provide a thorough analysis of the following text.',
            'ticket' => 'You are a support ticket analyzer. Analyze the following ticket and identify: urgency level, main issue, customer sentiment, and suggested priority.',
        ];
        
        return $prompts[$type] ?? $prompts['general'];
    }

    /**
     * Get system prompt for content generation
     */
    protected function getGenerationSystemPrompt(string $template): string
    {
        $prompts = [
            'email' => 'You are a professional email writer. Generate a well-formatted, professional email based on the provided data.',
            'report' => 'You are a business report writer. Generate a clear, structured report based on the provided data.',
            'documentation' => 'You are a technical documentation writer. Generate clear, detailed documentation based on the provided data.',
            'response' => 'You are a customer service representative. Generate a helpful, professional response based on the provided data.',
        ];
        
        return $prompts[$template] ?? 'You are a helpful content generator. Generate appropriate content based on the provided data.';
    }

    /**
     * Get available models from OpenRouter
     */
    public function getAvailableModels(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::withHeaders($this->defaultHeaders)
                ->get($this->baseUrl . '/models');

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch OpenRouter models', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
        }

        return [];
    }

    /**
     * Check if the service is properly configured for this company
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) 
            && !empty($this->baseUrl)
            && ($this->aiSettings['enabled'] ?? false);
    }

    /**
     * Get the company this service is configured for
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * Get AI settings for this company
     */
    public function getSettings(): array
    {
        return $this->aiSettings;
    }
}
