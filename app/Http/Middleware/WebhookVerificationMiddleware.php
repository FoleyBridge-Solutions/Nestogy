<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;

/**
 * WebhookVerificationMiddleware
 * 
 * Verifies webhook signatures from external services to ensure
 * requests are authentic and haven't been tampered with.
 */
class WebhookVerificationMiddleware
{
    /**
     * Supported webhook providers and their verification methods
     */
    protected array $providers = [
        'stripe' => 'verifyStripeSignature',
        'github' => 'verifyGithubSignature',
        'gitlab' => 'verifyGitlabSignature',
        'bitbucket' => 'verifyBitbucketSignature',
        'slack' => 'verifySlackSignature',
        'twilio' => 'verifyTwilioSignature',
        'sendgrid' => 'verifySendgridSignature',
        'mailgun' => 'verifyMailgunSignature',
        'paypal' => 'verifyPaypalSignature',
        'shopify' => 'verifyShopifySignature',
        'custom' => 'verifyCustomSignature',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $provider  The webhook provider name
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        // Check if webhooks are enabled
        if (!config('security.webhooks.enabled', true)) {
            return $this->webhooksDisabledResponse();
        }

        // Validate provider
        if (!isset($this->providers[$provider])) {
            return $this->invalidProviderResponse($provider);
        }

        // Check for replay attacks
        if ($this->isReplayAttack($request, $provider)) {
            return $this->replayAttackResponse($request, $provider);
        }

        // Verify signature
        $verificationMethod = $this->providers[$provider];
        if (!$this->$verificationMethod($request)) {
            $this->logFailedVerification($request, $provider);
            return $this->unauthorizedResponse($provider);
        }

        // Log successful webhook
        $this->logSuccessfulWebhook($request, $provider);

        // Add webhook info to request
        $request->attributes->set('webhook_provider', $provider);
        $request->attributes->set('webhook_verified', true);

        return $next($request);
    }

    /**
     * Verify Stripe webhook signature.
     */
    protected function verifyStripeSignature(Request $request): bool
    {
        $signature = $request->header('Stripe-Signature');
        if (!$signature) {
            return false;
        }

        $secret = config('security.webhooks.secrets.stripe');
        if (!$secret) {
            return false;
        }

        $payload = $request->getContent();
        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', $element, 2);
            if ($parts[0] === 't') {
                $timestamp = $parts[1];
            } elseif ($parts[0] === 'v1') {
                $signatures[] = $parts[1];
            }
        }

        if (!$timestamp || empty($signatures)) {
            return false;
        }

        // Check timestamp to prevent replay attacks
        if (abs(time() - intval($timestamp)) > 300) { // 5 minutes tolerance
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify GitHub webhook signature.
     */
    protected function verifyGithubSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            // Fallback to SHA1 for older webhooks
            $signature = $request->header('X-Hub-Signature');
            if (!$signature) {
                return false;
            }
            return $this->verifyGithubSha1Signature($request, $signature);
        }

        $secret = config('security.webhooks.secrets.github');
        if (!$secret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify GitHub SHA1 signature (legacy).
     */
    protected function verifyGithubSha1Signature(Request $request, string $signature): bool
    {
        $secret = config('security.webhooks.secrets.github');
        if (!$secret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha1=' . hash_hmac('sha1', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify GitLab webhook signature.
     */
    protected function verifyGitlabSignature(Request $request): bool
    {
        $token = $request->header('X-Gitlab-Token');
        if (!$token) {
            return false;
        }

        $expectedToken = config('security.webhooks.secrets.gitlab');
        if (!$expectedToken) {
            return false;
        }

        return hash_equals($expectedToken, $token);
    }

    /**
     * Verify Bitbucket webhook signature.
     */
    protected function verifyBitbucketSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature');
        if (!$signature) {
            return false;
        }

        $secret = config('security.webhooks.secrets.bitbucket');
        if (!$secret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Slack webhook signature.
     */
    protected function verifySlackSignature(Request $request): bool
    {
        $signature = $request->header('X-Slack-Signature');
        $timestamp = $request->header('X-Slack-Request-Timestamp');
        
        if (!$signature || !$timestamp) {
            return false;
        }

        // Check timestamp to prevent replay attacks
        if (abs(time() - intval($timestamp)) > 300) { // 5 minutes tolerance
            return false;
        }

        $secret = config('security.webhooks.secrets.slack');
        if (!$secret) {
            return false;
        }

        $payload = $request->getContent();
        $sigBasestring = 'v0:' . $timestamp . ':' . $payload;
        $expectedSignature = 'v0=' . hash_hmac('sha256', $sigBasestring, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Twilio webhook signature.
     */
    protected function verifyTwilioSignature(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Signature');
        if (!$signature) {
            return false;
        }

        $authToken = config('security.webhooks.secrets.twilio');
        if (!$authToken) {
            return false;
        }

        $url = $request->fullUrl();
        $data = $request->all();
        
        // Sort parameters
        ksort($data);
        
        // Build the string to sign
        $string = $url;
        foreach ($data as $key => $value) {
            $string .= $key . $value;
        }

        $expectedSignature = base64_encode(hash_hmac('sha1', $string, $authToken, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify SendGrid webhook signature.
     */
    protected function verifySendgridSignature(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
        $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');
        
        if (!$signature || !$timestamp) {
            return false;
        }

        $publicKey = config('security.webhooks.secrets.sendgrid_public_key');
        if (!$publicKey) {
            return false;
        }

        $payload = $request->getContent();
        $signedContent = $timestamp . $payload;
        
        // Verify using public key
        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if (!$publicKeyResource) {
            return false;
        }

        $verified = openssl_verify(
            $signedContent,
            base64_decode($signature),
            $publicKeyResource,
            OPENSSL_ALGO_SHA256
        );

        return $verified === 1;
    }

    /**
     * Verify Mailgun webhook signature.
     */
    protected function verifyMailgunSignature(Request $request): bool
    {
        $timestamp = $request->input('timestamp');
        $token = $request->input('token');
        $signature = $request->input('signature');
        
        if (!$timestamp || !$token || !$signature) {
            return false;
        }

        $secret = config('security.webhooks.secrets.mailgun');
        if (!$secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . $token, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify PayPal webhook signature.
     */
    protected function verifyPaypalSignature(Request $request): bool
    {
        // PayPal webhooks require API verification
        // This is a simplified version - in production, you should verify with PayPal API
        $transmissionId = $request->header('PAYPAL-TRANSMISSION-ID');
        $transmissionTime = $request->header('PAYPAL-TRANSMISSION-TIME');
        $certUrl = $request->header('PAYPAL-CERT-URL');
        $authAlgo = $request->header('PAYPAL-AUTH-ALGO');
        $transmissionSig = $request->header('PAYPAL-TRANSMISSION-SIG');
        
        if (!$transmissionId || !$transmissionTime || !$certUrl || !$authAlgo || !$transmissionSig) {
            return false;
        }

        // In production, verify with PayPal API
        // For now, just check if webhook ID is configured
        return config('security.webhooks.secrets.paypal_webhook_id') !== null;
    }

    /**
     * Verify Shopify webhook signature.
     */
    protected function verifyShopifySignature(Request $request): bool
    {
        $signature = $request->header('X-Shopify-Hmac-Sha256');
        if (!$signature) {
            return false;
        }

        $secret = config('security.webhooks.secrets.shopify');
        if (!$secret) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify custom webhook signature.
     */
    protected function verifyCustomSignature(Request $request): bool
    {
        $config = config('security.webhooks.custom');
        if (!$config) {
            return false;
        }

        $signatureHeader = $config['signature_header'] ?? 'X-Webhook-Signature';
        $signature = $request->header($signatureHeader);
        if (!$signature) {
            return false;
        }

        $secret = $config['secret'] ?? '';
        if (!$secret) {
            return false;
        }

        $algorithm = $config['algorithm'] ?? 'sha256';
        $payload = $request->getContent();
        
        // Check if signature includes algorithm prefix
        $prefix = $config['signature_prefix'] ?? '';
        if ($prefix) {
            $expectedSignature = $prefix . hash_hmac($algorithm, $payload, $secret);
        } else {
            $expectedSignature = hash_hmac($algorithm, $payload, $secret);
        }

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check for replay attacks.
     */
    protected function isReplayAttack(Request $request, string $provider): bool
    {
        // Get unique identifier for this webhook
        $identifier = $this->getWebhookIdentifier($request, $provider);
        if (!$identifier) {
            return false;
        }

        $cacheKey = 'webhook_processed:' . $provider . ':' . $identifier;
        
        // Check if we've seen this webhook before
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Mark as processed (store for 24 hours)
        Cache::put($cacheKey, true, now()->addDay());
        
        return false;
    }

    /**
     * Get unique identifier for webhook.
     */
    protected function getWebhookIdentifier(Request $request, string $provider): ?string
    {
        return match($provider) {
            'stripe' => $request->header('Stripe-Signature'),
            'github' => $request->header('X-GitHub-Delivery'),
            'gitlab' => $request->header('X-Gitlab-Event-UUID'),
            'slack' => $request->header('X-Slack-Request-Timestamp') . ':' . $request->header('X-Slack-Signature'),
            'twilio' => $request->header('X-Twilio-Signature'),
            default => hash('sha256', $request->getContent()),
        };
    }

    /**
     * Log failed verification.
     */
    protected function logFailedVerification(Request $request, string $provider): void
    {
        AuditLog::logSecurity('Webhook Verification Failed', [
            'provider' => $provider,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'headers' => $this->getSafeHeaders($request),
        ], AuditLog::SEVERITY_WARNING);
    }

    /**
     * Log successful webhook.
     */
    protected function logSuccessfulWebhook(Request $request, string $provider): void
    {
        AuditLog::logApi('Webhook Received', [
            'provider' => $provider,
            'event_type' => $this->getEventType($request, $provider),
            'ip' => $request->ip(),
        ]);
    }

    /**
     * Get safe headers for logging (exclude sensitive data).
     */
    protected function getSafeHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie'];
        
        foreach ($sensitiveHeaders as $header) {
            unset($headers[$header]);
        }
        
        return $headers;
    }

    /**
     * Get event type from webhook.
     */
    protected function getEventType(Request $request, string $provider): ?string
    {
        return match($provider) {
            'stripe' => $request->input('type'),
            'github' => $request->header('X-GitHub-Event'),
            'gitlab' => $request->header('X-Gitlab-Event'),
            'slack' => $request->input('event.type'),
            default => null,
        };
    }

    /**
     * Webhooks disabled response.
     */
    protected function webhooksDisabledResponse(): Response
    {
        return response()->json([
            'error' => 'Service Unavailable',
            'message' => 'Webhooks are temporarily disabled.',
        ], 503);
    }

    /**
     * Invalid provider response.
     */
    protected function invalidProviderResponse(string $provider): Response
    {
        return response()->json([
            'error' => 'Invalid Provider',
            'message' => "Unknown webhook provider: {$provider}",
        ], 400);
    }

    /**
     * Replay attack response.
     */
    protected function replayAttackResponse(Request $request, string $provider): Response
    {
        AuditLog::logSecurity('Webhook Replay Attack Detected', [
            'provider' => $provider,
            'ip' => $request->ip(),
        ], AuditLog::SEVERITY_CRITICAL);

        return response()->json([
            'error' => 'Duplicate Request',
            'message' => 'This webhook has already been processed.',
        ], 409);
    }

    /**
     * Unauthorized response.
     */
    protected function unauthorizedResponse(string $provider): Response
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => "Invalid webhook signature for {$provider}.",
        ], 401);
    }
}