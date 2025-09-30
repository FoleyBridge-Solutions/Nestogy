<?php

namespace App\Domains\PhysicalMail\Controllers;

use App\Domains\PhysicalMail\Jobs\ProcessWebhookJob;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle PostGrid webhook
     */
    public function handle(Request $request): Response
    {
        // Log incoming webhook
        Log::info('PostGrid webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // Verify webhook signature
        if (! $this->verifySignature($request)) {
            Log::warning('Invalid PostGrid webhook signature');

            return response('Unauthorized', 401);
        }

        // Get webhook data
        $data = $request->all();

        // Check if we've already processed this webhook
        if ($this->isDuplicate($data)) {
            Log::info('Duplicate webhook, skipping', ['event_id' => $data['id'] ?? 'unknown']);

            return response('OK (duplicate)', 200);
        }

        // Queue webhook processing
        ProcessWebhookJob::dispatch($data)
            ->onQueue(config('physical_mail.queues.webhooks', 'default'));

        return response('OK', 200);
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-PostGrid-Signature');
        $secret = config('physical_mail.postgrid.webhook_secret');

        if (! $signature || ! $secret) {
            // If no secret is configured, skip verification in development
            if (app()->environment('local', 'testing')) {
                return true;
            }

            return false;
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Compare signatures
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check if webhook is a duplicate
     */
    private function isDuplicate(array $data): bool
    {
        if (! isset($data['id'])) {
            return false;
        }

        // Check if we've already processed this event
        return \DB::table('physical_mail_webhooks')
            ->where('postgrid_event_id', $data['id'])
            ->exists();
    }
}
