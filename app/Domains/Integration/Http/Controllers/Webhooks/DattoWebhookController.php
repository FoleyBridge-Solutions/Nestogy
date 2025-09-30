<?php

namespace App\Domains\Integration\Http\Controllers\Webhooks;

use App\Domains\Integration\Services\WebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Datto RMM Webhook Controller
 * 
 * Handles webhooks from Datto RMM system.
 * Processes device alerts, backup status, and monitoring data.
 */
class DattoWebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle Datto webhook.
     */
    public function handle(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('Datto webhook received', [
                'integration' => $integration,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content_type' => $request->header('Content-Type'),
                'signature_present' => !empty($request->header('X-Datto-Signature')),
                'payload_size' => strlen($request->getContent()),
            ]);

            $result = $this->webhookService->processWebhook(
                $request,
                'datto',
                $integration
            );

            return response()->json([
                'success' => true,
                'message' => 'Datto webhook processed successfully',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500;
            }

            Log::error('Datto webhook processing failed', [
                'integration' => $integration,
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process Datto webhook',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Handle Datto health check.
     */
    public function health(string $integration): JsonResponse
    {
        try {
            $health = $this->webhookService->getHealthCheck($integration);

            return response()->json([
                'success' => true,
                'provider' => 'Datto RMM',
                'data' => $health,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'provider' => 'Datto RMM',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Datto test webhook.
     */
    public function test(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('Datto test webhook received', [
                'integration' => $integration,
                'payload' => $request->all(),
            ]);

            // Generate test response
            $testData = [
                'integration' => $integration,
                'provider' => 'datto',
                'received_at' => now()->toISOString(),
                'headers' => [
                    'content_type' => $request->header('Content-Type'),
                    'user_agent' => $request->userAgent(),
                    'signature_present' => !empty($request->header('X-Datto-Signature')),
                ],
                'payload_preview' => $this->getPayloadPreview($request->all()),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Datto test webhook received successfully',
                'data' => $testData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datto test webhook failed',
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
            'uid',
            'device_name',
            'site_name',
            'alert_uid',
            'alert_message',
            'alert_type',
            'timestamp',
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