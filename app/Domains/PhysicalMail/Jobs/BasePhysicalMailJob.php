<?php

namespace App\Domains\PhysicalMail\Jobs;

use App\Domains\PhysicalMail\Services\PostGridClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BasePhysicalMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 120, 300]; // 1 min, 2 min, 5 min
    
    protected Model $mailable;
    
    public function __construct()
    {
        $this->queue = config('physical_mail.queues.mail', 'default');
    }
    
    /**
     * Execute the job
     */
    public function handle(PostGridClient $client): void
    {
        try {
            Log::info('Sending physical mail', [
                'type' => $this->getMailType(),
                'mailable_id' => $this->mailable->id,
            ]);
            
            // Build payload
            $payload = $this->buildPayload();
            
            // Send to PostGrid
            $response = $client->send(
                $this->getMailType(),
                $payload,
                $this->mailable->idempotency_key
            );
            
            // Update order with response
            $this->updateOrder($response);
            
            Log::info('Physical mail sent successfully', [
                'type' => $this->getMailType(),
                'mailable_id' => $this->mailable->id,
                'postgrid_id' => $response['id'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send physical mail', [
                'type' => $this->getMailType(),
                'mailable_id' => $this->mailable->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }
    
    /**
     * Build the payload for PostGrid API
     */
    protected function buildPayload(): array
    {
        // Base payload for all mail types
        $payload = [
            'to' => $this->getContactPayload($this->mailable->toContact),
            'from' => $this->getContactPayload($this->mailable->fromContact),
        ];
        
        // Add mailing class if set on order
        if ($this->mailable->order) {
            $payload['mailingClass'] = $this->mailable->order->mailing_class;
            
            // Add metadata
            if (!empty($this->mailable->order->metadata)) {
                $payload['metadata'] = $this->mailable->order->metadata;
            }
        }
        
        // Add type-specific fields
        $typeSpecific = $this->getTypeSpecificPayload();
        
        return array_merge($payload, $typeSpecific);
    }
    
    /**
     * Get the mail type for PostGrid
     */
    abstract protected function getMailType(): string;
    
    /**
     * Get type-specific payload fields
     */
    abstract protected function getTypeSpecificPayload(): array;
    
    /**
     * Get contact payload (ID or full contact data)
     */
    protected function getContactPayload($contact): mixed
    {
        if (!$contact) {
            throw new \Exception('Contact not found');
        }
        
        // If contact has PostGrid ID, use that
        if ($contact->postgrid_id) {
            return $contact->postgrid_id;
        }
        
        // Otherwise send full contact data
        return $contact->toPostGridArray();
    }
    
    /**
     * Update order with PostGrid response
     */
    protected function updateOrder(array $response): void
    {
        // Load the order relationship if not loaded
        if (!$this->mailable->relationLoaded('order')) {
            $this->mailable->load('order');
        }
        
        if (!$this->mailable->order) {
            Log::warning('No order found for mailable', [
                'mailable_id' => $this->mailable->id,
                'mailable_type' => get_class($this->mailable),
            ]);
            return;
        }
        
        $updates = [
            'postgrid_id' => $response['id'] ?? null,
            'status' => $response['status'] ?? 'ready',
            'pdf_url' => $response['url'] ?? null,
        ];
        
        // Update send date if provided
        if (isset($response['sendDate'])) {
            $updates['send_date'] = $response['sendDate'];
        }
        
        // Update cost if provided
        if (isset($response['cost'])) {
            $updates['cost'] = $response['cost'];
        }
        
        $this->mailable->order->update($updates);
        
        // Update contact PostGrid IDs if created
        $this->updateContactIds($response);
    }
    
    /**
     * Update contact PostGrid IDs from response
     */
    protected function updateContactIds(array $response): void
    {
        // Update 'to' contact PostGrid ID
        if (isset($response['to']['id']) && !$this->mailable->toContact->postgrid_id) {
            $this->mailable->toContact->update([
                'postgrid_id' => $response['to']['id'],
                'address_status' => $response['to']['addressStatus'] ?? 'verified',
            ]);
        }
        
        // Update 'from' contact PostGrid ID
        if (isset($response['from']['id']) && !$this->mailable->fromContact->postgrid_id) {
            $this->mailable->fromContact->update([
                'postgrid_id' => $response['from']['id'],
                'address_status' => $response['from']['addressStatus'] ?? 'verified',
            ]);
        }
    }
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Physical mail job failed permanently', [
            'type' => $this->getMailType(),
            'mailable_id' => $this->mailable->id,
            'error' => $exception->getMessage(),
        ]);
        
        // Update order status to failed
        if ($this->mailable->order) {
            $this->mailable->order->update(['status' => 'failed']);
        }
        
        // Notify relevant parties (implement as needed)
        // Example: Send notification to admin
    }
}