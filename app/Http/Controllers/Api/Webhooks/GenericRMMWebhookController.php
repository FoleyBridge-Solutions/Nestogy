<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Domains\Integration\Services\WebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Generic RMM Webhook Controller
 * 
 * Handles webhooks from generic/custom RMM systems.
 * Provides flexible processing for any RMM tool with configurable field mappings.
 */
class GenericRMMWebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle Generic RMM webhook.
     */
    public function handle(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('Generic RMM webhook received', [
                'integration' => $integration,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content_type' => $request->header('Content-Type'),
                'auth_headers' => [
                    'api_key_present' => !empty($request->header('X-API-Key')),
                    'signature_present' => !empty($request->header('X-Signature')),
                    'auth_present' => !empty($request->header('Authorization')),
                ],
                'payload_size' => strlen($request->getContent()),
            ]);

            $result = $this->webhookService->processWebhook(
                $request,
                'generic',
                $integration
            );

            return response()->json([
                'success' => true,
                'message' => 'Generic RMM webhook processed successfully',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500;
            }

            Log::error('Generic RMM webhook processing failed', [
                'integration' => $integration,
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process Generic RMM webhook',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Handle Generic RMM health check.
     */
    public function health(string $integration): JsonResponse
    {
        try {
            $health = $this->webhookService->getHealthCheck($integration);

            return response()->json([
                'success' => true,
                'provider' => 'Generic RMM',
                'data' => $health,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'provider' => 'Generic RMM',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Generic RMM test webhook.
     */
    public function test(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('Generic RMM test webhook received', [
                'integration' => $integration,
                'payload' => $request->all(),
            ]);

            // Generate test response
            $testData = [
                'integration' => $integration,
                'provider' => 'generic',
                'received_at' => now()->toISOString(),
                'headers' => [
                    'content_type' => $request->header('Content-Type'),
                    'user_agent' => $request->userAgent(),
                    'authentication' => $this->getAuthenticationInfo($request),
                ],
                'payload_preview' => $this->getPayloadPreview($request->all()),
                'field_detection' => $this->detectCommonFields($request->all()),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Generic RMM test webhook received successfully',
                'data' => $testData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Generic RMM test webhook failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authentication information for testing.
     */
    protected function getAuthenticationInfo(Request $request): array
    {
        return [
            'api_key_present' => !empty($request->header('X-API-Key')),
            'signature_present' => !empty($request->header('X-Signature')),
            'authorization_present' => !empty($request->header('Authorization')),
            'custom_auth_headers' => $this->getCustomAuthHeaders($request),
        ];
    }

    /**
     * Get custom authentication headers.
     */
    protected function getCustomAuthHeaders(Request $request): array
    {
        $authHeaders = [];
        $headers = $request->headers->all();
        
        foreach ($headers as $key => $value) {
            if (str_contains(strtolower($key), 'auth') || 
                str_contains(strtolower($key), 'key') || 
                str_contains(strtolower($key), 'token')) {
                $authHeaders[$key] = 'present';
            }
        }
        
        return $authHeaders;
    }

    /**
     * Get sanitized payload preview for testing.
     */
    protected function getPayloadPreview(array $payload): array
    {
        $preview = [];
        
        // Show first few fields as preview
        $count = 0;
        foreach ($payload as $key => $value) {
            if ($count >= 10) break;
            
            if (is_string($value) || is_numeric($value)) {
                $preview[$key] = $value;
            } elseif (is_array($value)) {
                $preview[$key] = '[array with ' . count($value) . ' items]';
            } elseif (is_object($value)) {
                $preview[$key] = '[object]';
            } else {
                $preview[$key] = '[' . gettype($value) . ']';
            }
            $count++;
        }
        
        if (count($payload) > 10) {
            $preview['...'] = 'and ' . (count($payload) - 10) . ' more fields';
        }
        
        $preview['total_fields'] = count($payload);
        
        return $preview;
    }

    /**
     * Detect common field patterns in payload.
     */
    protected function detectCommonFields(array $payload): array
    {
        $detectedFields = [];
        
        // Common field patterns for different RMM systems
        $patterns = [
            'device_id' => ['device_id', 'deviceId', 'computer_id', 'ComputerID', 'machine_id', 'uid', 'id'],
            'device_name' => ['device_name', 'deviceName', 'computer_name', 'ComputerName', 'machine_name', 'hostname'],
            'client_id' => ['client_id', 'clientId', 'ClientID', 'site_name', 'organizationId', 'customer_id'],
            'alert_id' => ['alert_id', 'alertId', 'AlertID', 'alert_uid', 'notification_id'],
            'message' => ['message', 'alert_message', 'AlertMessage', 'description', 'details'],
            'severity' => ['severity', 'alert_type', 'AlertType', 'priority', 'level'],
            'timestamp' => ['timestamp', 'created_at', 'createdAt', 'DateStamp', 'date', 'time'],
        ];
        
        foreach ($patterns as $fieldType => $possibleKeys) {
            foreach ($possibleKeys as $key) {
                if (isset($payload[$key])) {
                    $detectedFields[$fieldType] = [
                        'key' => $key,
                        'value' => $payload[$key],
                    ];
                    break;
                }
            }
        }
        
        return $detectedFields;
    }

    /**
     * Get field mapping suggestions based on payload analysis.
     */
    public function suggestFieldMappings(Request $request, string $integration): JsonResponse
    {
        try {
            $payload = $request->all();
            $detected = $this->detectCommonFields($payload);
            
            $suggestions = [];
            foreach ($detected as $fieldType => $info) {
                $suggestions[$fieldType] = $info['key'];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Field mapping suggestions generated',
                'data' => [
                    'detected_fields' => $detected,
                    'suggested_mappings' => $suggestions,
                    'payload_structure' => $this->analyzePayloadStructure($payload),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate field mapping suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze payload structure for mapping insights.
     */
    protected function analyzePayloadStructure(array $payload): array
    {
        $structure = [
            'total_fields' => count($payload),
            'field_types' => [],
            'nested_objects' => [],
            'array_fields' => [],
        ];
        
        foreach ($payload as $key => $value) {
            $type = gettype($value);
            $structure['field_types'][$type] = ($structure['field_types'][$type] ?? 0) + 1;
            
            if (is_array($value)) {
                $structure['array_fields'][] = $key;
            } elseif (is_object($value)) {
                $structure['nested_objects'][] = $key;
            }
        }
        
        return $structure;
    }
}