<?php

namespace App\Domains\Integration\Services;

use App\Domains\Integration\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Webhook Service
 * 
 * Handles secure webhook receiving, authentication, rate limiting,
 * and payload validation for RMM integrations.
 */
class WebhookService
{
    protected RMMIntegrationService $rmmService;

    public function __construct(RMMIntegrationService $rmmService)
    {
        $this->rmmService = $rmmService;
    }

    /**
     * Process incoming webhook request.
     */
    public function processWebhook(Request $request, string $provider, string $integrationUuid): array
    {
        $startTime = microtime(true);
        
        try {
            // Rate limiting
            $this->checkRateLimit($request, $integrationUuid);
            
            // Find integration
            $integration = $this->findIntegration($integrationUuid, $provider);
            
            // Authenticate request
            $this->authenticateWebhook($request, $integration);
            
            // Validate payload
            $payload = $this->validatePayload($request, $provider);
            
            // Process with RMM service
            $result = $this->rmmService->processWebhookPayload($integration, $payload);
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Webhook processed successfully', [
                'integration_uuid' => $integrationUuid,
                'provider' => $provider,
                'processing_time_ms' => $processingTime,
                'result' => $result,
            ]);
            
            return array_merge($result, [
                'processing_time_ms' => $processingTime,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::error('Webhook processing failed', [
                'integration_uuid' => $integrationUuid,
                'provider' => $provider,
                'processing_time_ms' => $processingTime,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Check rate limiting for webhook requests.
     */
    protected function checkRateLimit(Request $request, string $integrationUuid): void
    {
        $key = "webhook:{$integrationUuid}:{$request->ip()}";
        $maxAttempts = 1000; // 1000 requests per minute
        $decayMinutes = 1;
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            Log::warning('Webhook rate limit exceeded', [
                'integration_uuid' => $integrationUuid,
                'ip' => $request->ip(),
                'retry_after' => $seconds,
            ]);
            
            throw new \Exception("Rate limit exceeded. Retry after {$seconds} seconds.", 429);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
    }

    /**
     * Find integration by UUID and provider.
     */
    protected function findIntegration(string $uuid, string $provider): Integration
    {
        $integration = Integration::where('uuid', $uuid)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();
            
        if (!$integration) {
            throw new \Exception("Integration not found or inactive: {$uuid}", 404);
        }
        
        return $integration;
    }

    /**
     * Authenticate webhook request based on provider.
     */
    protected function authenticateWebhook(Request $request, Integration $integration): void
    {
        $provider = $integration->provider;
        $credentials = $integration->getCredentials();
        
        try {
            switch ($provider) {
                case Integration::PROVIDER_CONNECTWISE:
                    $this->authenticateConnectWise($request, $credentials);
                    break;
                    
                case Integration::PROVIDER_DATTO:
                    $this->authenticateDatto($request, $credentials);
                    break;
                    
                case Integration::PROVIDER_NINJA:
                    $this->authenticateNinja($request, $credentials);
                    break;
                    
                case Integration::PROVIDER_GENERIC:
                    $this->authenticateGeneric($request, $credentials);
                    break;
                    
                default:
                    throw new \Exception("Unsupported provider: {$provider}", 400);
            }
        } catch (\Exception $e) {
            Log::warning('Webhook authentication failed', [
                'integration_id' => $integration->id,
                'provider' => $provider,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            
            throw new \Exception('Authentication failed', 401);
        }
    }

    /**
     * Authenticate ConnectWise webhook.
     */
    protected function authenticateConnectWise(Request $request, array $credentials): void
    {
        $apiKey = data_get($credentials, 'api_key');
        if (!$apiKey) {
            throw new \Exception('API key not configured');
        }
        
        $providedKey = $request->header('X-CW-API-Key') 
                    ?: $request->header('Authorization')
                    ?: $request->input('api_key');
                    
        if (!$providedKey || !hash_equals($apiKey, $providedKey)) {
            throw new \Exception('Invalid API key');
        }
    }

    /**
     * Authenticate Datto webhook.
     */
    protected function authenticateDatto(Request $request, array $credentials): void
    {
        $sharedSecret = data_get($credentials, 'shared_secret');
        if (!$sharedSecret) {
            throw new \Exception('Shared secret not configured');
        }
        
        $providedSignature = $request->header('X-Datto-Signature');
        if (!$providedSignature) {
            throw new \Exception('Missing signature header');
        }
        
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $sharedSecret);
        
        if (!hash_equals($expectedSignature, $providedSignature)) {
            throw new \Exception('Invalid signature');
        }
    }

    /**
     * Authenticate NinjaOne webhook.
     */
    protected function authenticateNinja(Request $request, array $credentials): void
    {
        $bearerToken = data_get($credentials, 'bearer_token');
        if (!$bearerToken) {
            throw new \Exception('Bearer token not configured');
        }
        
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new \Exception('Missing or invalid authorization header');
        }
        
        $providedToken = substr($authHeader, 7); // Remove 'Bearer '
        
        if (!hash_equals($bearerToken, $providedToken)) {
            throw new \Exception('Invalid bearer token');
        }
    }

    /**
     * Authenticate generic webhook.
     */
    protected function authenticateGeneric(Request $request, array $credentials): void
    {
        $authMethod = data_get($credentials, 'auth_method', 'api_key');
        
        switch ($authMethod) {
            case 'api_key':
                $apiKey = data_get($credentials, 'api_key');
                if (!$apiKey) {
                    throw new \Exception('API key not configured');
                }
                
                $providedKey = $request->header('X-API-Key')
                            ?: $request->header('Authorization')
                            ?: $request->input('api_key');
                            
                if (!$providedKey || !hash_equals($apiKey, $providedKey)) {
                    throw new \Exception('Invalid API key');
                }
                break;
                
            case 'hmac':
                $secret = data_get($credentials, 'hmac_secret');
                if (!$secret) {
                    throw new \Exception('HMAC secret not configured');
                }
                
                $signature = $request->header('X-Signature');
                if (!$signature) {
                    throw new \Exception('Missing signature header');
                }
                
                $payload = $request->getContent();
                $expected = hash_hmac('sha256', $payload, $secret);
                
                if (!hash_equals($expected, $signature)) {
                    throw new \Exception('Invalid HMAC signature');
                }
                break;
                
            case 'none':
                // No authentication required
                break;
                
            default:
                throw new \Exception("Unsupported auth method: {$authMethod}");
        }
    }

    /**
     * Validate webhook payload based on provider.
     */
    protected function validatePayload(Request $request, string $provider): array
    {
        $contentType = $request->header('Content-Type', '');
        
        // Handle different content types
        if (str_contains($contentType, 'application/json')) {
            $payload = $request->json()->all();
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            $payload = $request->all();
        } elseif (str_contains($contentType, 'application/xml') || str_contains($contentType, 'text/xml')) {
            $payload = $this->parseXmlPayload($request->getContent());
        } else {
            // Try to parse as JSON first, then fall back to form data
            try {
                $payload = json_decode($request->getContent(), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $payload = $request->all();
                }
            } catch (\Exception $e) {
                $payload = $request->all();
            }
        }
        
        if (empty($payload)) {
            throw new ValidationException('Empty payload received');
        }
        
        // Provider-specific validation
        $this->validateProviderPayload($payload, $provider);
        
        return $payload;
    }

    /**
     * Parse XML payload to array.
     */
    protected function parseXmlPayload(string $xml): array
    {
        try {
            $xmlObject = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            return json_decode(json_encode($xmlObject), true);
        } catch (\Exception $e) {
            Log::warning('Failed to parse XML payload', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Validate provider-specific payload structure.
     */
    protected function validateProviderPayload(array $payload, string $provider): void
    {
        switch ($provider) {
            case Integration::PROVIDER_CONNECTWISE:
                $this->validateConnectWisePayload($payload);
                break;
                
            case Integration::PROVIDER_DATTO:
                $this->validateDattoPayload($payload);
                break;
                
            case Integration::PROVIDER_NINJA:
                $this->validateNinjaPayload($payload);
                break;
                
            case Integration::PROVIDER_GENERIC:
                // Generic validation - just ensure we have some basic fields
                if (!isset($payload['device_id']) && !isset($payload['alert_id'])) {
                    throw new ValidationException('Missing required fields for generic webhook');
                }
                break;
        }
    }

    /**
     * Validate ConnectWise payload.
     */
    protected function validateConnectWisePayload(array $payload): void
    {
        $required = ['ComputerID', 'AlertID'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new ValidationException("Missing required ConnectWise field: {$field}");
            }
        }
    }

    /**
     * Validate Datto payload.
     */
    protected function validateDattoPayload(array $payload): void
    {
        $required = ['uid', 'alert_uid'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new ValidationException("Missing required Datto field: {$field}");
            }
        }
    }

    /**
     * Validate NinjaOne payload.
     */
    protected function validateNinjaPayload(array $payload): void
    {
        $required = ['deviceId', 'alertId'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new ValidationException("Missing required NinjaOne field: {$field}");
            }
        }
    }

    /**
     * Get webhook health check response.
     */
    public function getHealthCheck(string $integrationUuid): array
    {
        $integration = Integration::where('uuid', $integrationUuid)
            ->where('is_active', true)
            ->first();
            
        if (!$integration) {
            return [
                'status' => 'not_found',
                'message' => 'Integration not found or inactive',
                'timestamp' => now()->toISOString(),
            ];
        }
        
        return [
            'status' => 'ok',
            'integration_name' => $integration->name,
            'provider' => $integration->provider,
            'last_sync' => $integration->last_sync?->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }
}