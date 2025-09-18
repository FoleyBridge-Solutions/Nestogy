<?php

namespace App\Domains\PhysicalMail\Services;

use App\Domains\PhysicalMail\Jobs\SendLetterJob;
use App\Domains\PhysicalMail\Jobs\SendPostcardJob;
use App\Domains\PhysicalMail\Jobs\SendChequeJob;
use App\Domains\PhysicalMail\Models\PhysicalMailContact;
use App\Domains\PhysicalMail\Models\PhysicalMailLetter;
use App\Domains\PhysicalMail\Models\PhysicalMailOrder;
use App\Domains\PhysicalMail\Models\PhysicalMailTemplate;
use App\Domains\PhysicalMail\Services\PostGridClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PhysicalMailService
{
    
    public function __construct(
        private ?PostGridClient $postgrid = null,
        private ?PhysicalMailContactService $contactService = null,
        private ?PhysicalMailTemplateService $templateService = null,
        private ?PhysicalMailTemplateBuilder $templateBuilder = null,
        private ?PhysicalMailContentAnalyzer $contentAnalyzer = null
    ) {
        // Initialize with company-aware client if not provided
        if (!$this->postgrid) {
            $this->postgrid = new CompanyAwarePostGridClient();
        }
        
        // Initialize other services if not provided
        $this->contactService = $this->contactService ?: app(PhysicalMailContactService::class);
        $this->templateService = $this->templateService ?: app(PhysicalMailTemplateService::class);
        $this->templateBuilder = $this->templateBuilder ?: app(PhysicalMailTemplateBuilder::class);
        $this->contentAnalyzer = $this->contentAnalyzer ?: app(PhysicalMailContentAnalyzer::class);
    }
    

    /**
     * Send physical mail
     */
    public function send(string $type, array $data): PhysicalMailOrder
    {
        return DB::transaction(function () use ($type, $data) {
            // Create the mailable record
            $mailable = $this->createMailable($type, $data);
            
            // Queue the sending job
            $this->queueMailJob($type, $mailable);
            
            return $mailable->order;
        });
    }
    
    /**
     * Create mailable record based on type
     */
    private function createMailable(string $type, array $data): Model
    {
        // Map type to model class
        $modelClass = match (strtolower($type)) {
            'letter' => PhysicalMailLetter::class,
            'postcard' => \App\Domains\PhysicalMail\Models\PhysicalMailPostcard::class,
            'cheque', 'check' => \App\Domains\PhysicalMail\Models\PhysicalMailCheque::class,
            'self_mailer' => \App\Domains\PhysicalMail\Models\PhysicalMailSelfMailer::class,
            default => throw new \InvalidArgumentException("Invalid mail type: {$type}"),
        };
        
        // Process contacts
        $data['to_contact_id'] = $this->processContact($data['to']);
        $data['from_contact_id'] = $this->processContact($data['from'] ?? config('physical_mail.defaults.from_address'));
        
        // Process template if provided
        if (isset($data['template'])) {
            $data['template_id'] = $this->processTemplate($data['template']);
            unset($data['template']);
        }
        
        // Process content for safety (letters only)
        if (strtolower($type) === 'letter' && isset($data['content']) && !isset($data['pdf'])) {
            $data = $this->processContentForSafety($data);
        }
        
        // Generate idempotency key if not provided
        $data['idempotency_key'] = $data['idempotency_key'] ?? (string) Str::uuid();
        
        // Remove non-model fields
        $orderData = [
            'client_id' => $data['client_id'] ?? Auth::user()?->company?->currentClient?->id,
            'status' => 'pending',
            'mailing_class' => $data['mailing_class'] ?? config('physical_mail.defaults.mailing_class'),
            'send_date' => $data['send_date'] ?? now(),
            'metadata' => $data['metadata'] ?? [],
            'created_by' => Auth::id(),
        ];
        
        unset($data['client_id'], $data['mailing_class'], $data['send_date'], $data['metadata'], $data['to'], $data['from']);
        
        // Create mailable
        $mailable = $modelClass::create($data);
        
        // Create order record
        $mailable->order()->create($orderData);
        
        return $mailable;
    }
    
    /**
     * Process content to ensure it's safe for PostGrid
     */
    private function processContentForSafety(array $data): array
    {
        $content = $data['content'] ?? '';
        
        if (empty($content)) {
            return $data;
        }
        
        // Check if content was built with our template builder (has 4-inch margin)
        $hasAddressZone = strpos($content, 'address-zone') !== false;
        $has4InchMargin = preg_match('/margin-top:\s*4in|margin-top:\s*384px|height:\s*384px/', $content);
        
        if ($hasAddressZone || $has4InchMargin) {
            // Content is already safe with proper margins
            $data['address_placement'] = 'top_first_page';
            \Log::info('Content has safe margins, using top_first_page');
            return $data;
        }
        
        // Analyze content for conflicts
        $analysis = $this->contentAnalyzer->analyzeContent($content);
        
        // If content has address conflicts, fix it
        if ($analysis['has_address_conflict']) {
            // Try to reformat content first
            if ($analysis['recommended_placement'] === 'reformat' || 
                $analysis['estimated_pages'] === 1) {
                // Make content safe by adding proper margins
                $data['content'] = $this->contentAnalyzer->makeContentSafe($content);
                $data['address_placement'] = 'top_first_page';
                
                \Log::info('Content reformatted for PostGrid safety', [
                    'original_length' => strlen($content),
                    'new_length' => strlen($data['content']),
                ]);
            } else {
                // For multi-page documents with conflicts, use insert_blank_page
                $data['address_placement'] = 'insert_blank_page';
                
                \Log::info('Using insert_blank_page due to content conflicts', [
                    'estimated_pages' => $analysis['estimated_pages'],
                ]);
            }
        } else {
            // Content is already safe
            $data['address_placement'] = $data['address_placement'] ?? 'top_first_page';
        }
        
        return $data;
    }
    
    /**
     * Process contact data - find or create contact
     */
    private function processContact($contact): string
    {
        if (is_string($contact)) {
            // Assume it's a contact ID
            return $contact;
        }
        
        if (is_array($contact)) {
            return $this->contactService->findOrCreate($contact)->id;
        }
        
        throw new \InvalidArgumentException('Invalid contact data');
    }
    
    /**
     * Process template data
     */
    private function processTemplate($template): ?string
    {
        if (is_null($template)) {
            return null;
        }
        
        if (is_string($template)) {
            // Could be template ID or PostGrid ID
            $templateModel = PhysicalMailTemplate::where('id', $template)
                ->orWhere('postgrid_id', $template)
                ->first();
                
            if ($templateModel) {
                return $templateModel->id;
            }
            
            // Try to fetch from PostGrid
            return $this->templateService->syncFromPostGrid($template)->id;
        }
        
        if (is_array($template)) {
            return $this->templateService->findOrCreate($template)->id;
        }
        
        throw new \InvalidArgumentException('Invalid template data');
    }
    
    /**
     * Queue the appropriate job based on mail type
     */
    private function queueMailJob(string $type, Model $mailable): void
    {
        $jobClass = match (strtolower($type)) {
            'letter' => SendLetterJob::class,
            'postcard' => SendPostcardJob::class,
            'cheque', 'check' => SendChequeJob::class,
            'self_mailer' => \App\Domains\PhysicalMail\Jobs\SendSelfMailerJob::class,
            default => throw new \InvalidArgumentException("Invalid mail type: {$type}"),
        };
        
        $jobClass::dispatch($mailable)->onQueue(config('physical_mail.queues.mail', 'default'));
    }
    
    /**
     * Cancel a mail order
     */
    public function cancel(PhysicalMailOrder $order): bool
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception("Order cannot be cancelled in status: {$order->status}");
        }
        
        try {
            // Cancel in PostGrid if already sent
            if ($order->postgrid_id) {
                $resource = Str::plural(strtolower(class_basename($order->mailable_type)));
                $this->postgrid->cancel($resource, $order->postgrid_id);
            }
            
            // Update local status
            $order->update(['status' => 'cancelled']);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to cancel mail order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Get order tracking information
     */
    public function getTracking(PhysicalMailOrder $order): array
    {
        if (!$order->postgrid_id) {
            return ['status' => $order->status];
        }
        
        try {
            $resource = Str::plural(strtolower(class_basename($order->mailable_type)));
            $response = $this->postgrid->getResource($resource, $order->postgrid_id);
            
            // Update local tracking information
            $this->updateTrackingFromResponse($order, $response);
            
            return [
                'status' => $response['status'] ?? $order->status,
                'imb_status' => $response['imbStatus'] ?? $order->imb_status,
                'imb_date' => $response['imbDate'] ?? $order->imb_date,
                'imb_zip_code' => $response['imbZIPCode'] ?? $order->imb_zip_code,
                'tracking_number' => $response['trackingNumber'] ?? $order->tracking_number,
                'pdf_url' => $response['url'] ?? $order->pdf_url,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get tracking for order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return ['status' => $order->status];
        }
    }
    
    /**
     * Update tracking information from PostGrid response
     */
    private function updateTrackingFromResponse(PhysicalMailOrder $order, array $response): void
    {
        $updates = [];
        
        if (isset($response['status']) && $response['status'] !== $order->status) {
            $updates['status'] = $response['status'];
        }
        
        if (isset($response['imbStatus'])) {
            $updates['imb_status'] = $response['imbStatus'];
        }
        
        if (isset($response['imbDate'])) {
            $updates['imb_date'] = $response['imbDate'];
        }
        
        if (isset($response['imbZIPCode'])) {
            $updates['imb_zip_code'] = $response['imbZIPCode'];
        }
        
        if (isset($response['trackingNumber'])) {
            $updates['tracking_number'] = $response['trackingNumber'];
        }
        
        if (isset($response['url']) && !$order->pdf_url) {
            $updates['pdf_url'] = $response['url'];
        }
        
        if (!empty($updates)) {
            $order->update($updates);
        }
    }
    
    /**
     * Progress test order (test mode only)
     */
    public function progressTestOrder(PhysicalMailOrder $order): array
    {
        if (!$this->postgrid->isTestMode()) {
            throw new \Exception('Can only progress test orders');
        }
        
        if (!$order->postgrid_id) {
            throw new \Exception('Order has not been sent to PostGrid yet');
        }
        
        $resource = Str::plural(strtolower(class_basename($order->mailable_type)));
        $response = $this->postgrid->progressTest($resource, $order->postgrid_id);
        
        $this->updateTrackingFromResponse($order, $response);
        
        return $response;
    }
    
    /**
     * Send a letter using a safe template
     */
    public function sendWithTemplate(string $templateName, array $variables, array $recipients): PhysicalMailOrder
    {
        // Find template by name
        $template = PhysicalMailTemplate::where('name', $templateName)
            ->where('is_active', true)
            ->first();
        
        if (!$template) {
            throw new \Exception("Template not found: {$templateName}");
        }
        
        // Prepare data
        $data = [
            'type' => $template->type,
            'to' => $recipients['to'],
            'from' => $recipients['from'] ?? config('physical_mail.defaults.from_address'),
            'template_id' => $template->id,
            'merge_variables' => $variables,
            'color' => $recipients['color'] ?? config('physical_mail.defaults.color'),
            'double_sided' => $recipients['double_sided'] ?? config('physical_mail.defaults.double_sided'),
            'address_placement' => 'top_first_page', // Safe templates don't need blank page
        ];
        
        return $this->send($template->type, $data);
    }
    
    /**
     * Send an invoice by mail using safe template
     */
    public function sendInvoice(\App\Models\Invoice $invoice, array $options = []): PhysicalMailOrder
    {
        $variables = [
            'invoice_number' => $invoice->getFormattedNumber(),
            'invoice_date' => $invoice->date->format('F j, Y'),
            'due_date' => $invoice->due->format('F j, Y'),
            'amount_due' => '$' . number_format($invoice->getBalance(), 2),
            'total_amount' => '$' . number_format($invoice->amount, 2),
            'payment_instructions' => 'Please remit payment to the address below or pay online.',
            'line_items' => $invoice->items->map(function ($item) {
                return [
                    'description' => $item->description,
                    'amount' => '$' . number_format($item->amount, 2),
                ];
            })->toArray(),
        ];
        
        $recipients = [
            'to' => [
                'firstName' => $invoice->client->contact_first_name ?? '',
                'lastName' => $invoice->client->contact_last_name ?? '',
                'companyName' => $invoice->client->name,
                'addressLine1' => $invoice->client->address,
                'addressLine2' => $invoice->client->address2,
                'city' => $invoice->client->city,
                'provinceOrState' => $invoice->client->state,
                'postalOrZip' => $invoice->client->zip_code,
                'country' => $invoice->client->country_code ?? 'US',
            ],
            'color' => $options['color'] ?? true,
            'double_sided' => $options['double_sided'] ?? true,
        ];
        
        $order = $this->sendWithTemplate('Invoice Template', $variables, $recipients);
        
        // Update invoice to track mail order
        $invoice->update(['last_mail_order_id' => $order->id]);
        
        return $order;
    }
    
    /**
     * Get orders by client
     */
    public function getByClient(string $clientId, array $filters = [])
    {
        $query = $this->modelClass::where('client_id', $clientId);
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['mailable_type'])) {
            $query->where('mailable_type', 'like', '%' . $filters['mailable_type'] . '%');
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->with($this->defaultEagerLoad)
            ->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 25);
    }
}