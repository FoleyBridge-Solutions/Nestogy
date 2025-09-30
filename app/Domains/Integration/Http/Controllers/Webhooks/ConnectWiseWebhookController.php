<?php

namespace App\Domains\Integration\Http\Controllers\Webhooks;

use App\Domains\Integration\Services\WebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * ConnectWise Automate Webhook Controller
 * 
 * Handles webhooks from ConnectWise Automate RMM system.
 * Processes device alerts, status updates, and monitoring data.
 */
class ConnectWiseWebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle ConnectWise webhook.
     */
    public function handle(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('ConnectWise webhook received', [
                'integration' => $integration,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content_type' => $request->header('Content-Type'),
                'payload_size' => strlen($request->getContent()),
            ]);

            $result = $this->webhookService->processWebhook(
                $request,
                'connectwise',
                $integration
            );

            return response()->json([
                'success' => true,
                'message' => 'ConnectWise webhook processed successfully',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            if ($statusCode < 100 || $statusCode >= 600) {
                $statusCode = 500;
            }

            Log::error('ConnectWise webhook processing failed', [
                'integration' => $integration,
                'error' => $e->getMessage(),
                'status_code' => $statusCode,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process ConnectWise webhook',
                'error' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Handle ConnectWise health check.
     */
    public function health(string $integration): JsonResponse
    {
        try {
            $health = $this->webhookService->getHealthCheck($integration);

            return response()->json([
                'success' => true,
                'provider' => 'ConnectWise Automate',
                'data' => $health,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'provider' => 'ConnectWise Automate',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle ConnectWise test webhook.
     */
    public function test(Request $request, string $integration): JsonResponse
    {
        try {
            Log::info('ConnectWise test webhook received', [
                'integration' => $integration,
                'payload' => $request->all(),
            ]);

            // Generate test response
            $testData = [
                'integration' => $integration,
                'provider' => 'connectwise',
                'received_at' => now()->toISOString(),
                'headers' => [
                    'content_type' => $request->header('Content-Type'),
                    'user_agent' => $request->userAgent(),
                    'api_key_present' => !empty($request->header('X-CW-API-Key')),
                ],
                'payload_preview' => $this->getPayloadPreview($request->all()),
            ];

            return response()->json([
                'success' => true,
                'message' => 'ConnectWise test webhook received successfully',
                'data' => $testData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ConnectWise test webhook failed',
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
            'ComputerID',
            'ComputerName', 
            'ClientID',
            'AlertID',
            'AlertMessage',
            'Severity',
            'AlertType',
            'DateStamp',
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