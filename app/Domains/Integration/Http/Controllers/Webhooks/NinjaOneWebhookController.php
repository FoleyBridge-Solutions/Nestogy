<?php

namespace App\Domains\Integration\Http\Controllers\Webhooks;

use App\Domains\Integration\Services\WebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * NinjaOne Webhook Controller
 * 
 * Handles webhooks from NinjaOne RMM system.
 * Processes device alerts, software updates, and monitoring data.
 */
class NinjaOneWebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle NinjaOne webhook.
     */
    public function handle(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('NinjaOne webhook received', [
                'integration' => $integration,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content_type' => $request->header('Content-Type'),
                'auth_present' => !empty($request->header('Authorization')),
                'payload_size' => strlen($request->getContent()),
            ]);

            $result = $this->webhookService->processWebhook(
                $request,
                'ninja',
                $integration
            );

            return response()->json([
                'success' => true,
                'message' => 'NinjaOne webhook processed successfully',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500;
            }

            Log::error('NinjaOne webhook processing failed', [
                'integration' => $integration,
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process NinjaOne webhook',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Handle NinjaOne health check.
     */
    public function health(string $integration): JsonResponse
    {
        try {
            $health = $this->webhookService->getHealthCheck($integration);

            return response()->json([
                'success' => true,
                'provider' => 'NinjaOne',
                'data' => $health,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'provider' => 'NinjaOne',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle NinjaOne test webhook.
     */
    public function test(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('NinjaOne test webhook received', [
                'integration' => $integration,
                'payload' => $request->all(),
            ]);

            // Generate test response
            $testData = [
                'integration' => $integration,
                'provider' => 'ninja',
                'received_at' => now()->toISOString(),
                'headers' => [
                    'content_type' => $request->header('Content-Type'),
                    'user_agent' => $request->userAgent(),
                    'auth_present' => !empty($request->header('Authorization')),
                ],
                'payload_preview' => $this->getPayloadPreview($request->all()),
            ];

            return response()->json([
                'success' => true,
                'message' => 'NinjaOne test webhook received successfully',
                'data' => $testData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'NinjaOne test webhook failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sanitized payload preview for testing.
     */
    protected function getPayloadPreview(array $payload): array
    {
        $preview = [];
        
        // Show key fields without sensitive data
        $safeFields = [
            'deviceId',
            'deviceName',
            'organizationId',
            'alertId',
            'alertMessage',
            'alertType',
            'severity',
            'createdAt',
            'status',
        ];
        
        foreach ($safeFields as $field) {
            if (isset($payload[$field])) {
                $preview[$field] = $payload[$field];
            }
        }
        
        $preview['total_fields'] = count($payload);
        
        return $preview;
    }
}