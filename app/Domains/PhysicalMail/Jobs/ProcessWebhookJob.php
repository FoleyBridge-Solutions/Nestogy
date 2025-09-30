<?php

namespace App\Domains\PhysicalMail\Jobs;

use App\Domains\PhysicalMail\Models\PhysicalMailContact;
use App\Domains\PhysicalMail\Models\PhysicalMailOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        private array $webhookData
    ) {}

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info('Processing PostGrid webhook', [
            'type' => $this->webhookData['type'] ?? 'unknown',
            'id' => $this->webhookData['id'] ?? null,
        ]);

        DB::transaction(function () {
            // Store webhook record
            $this->storeWebhookRecord();

            // Process based on webhook type
            $type = $this->webhookData['type'] ?? '';

            match (true) {
                str_starts_with($type, 'letter.') => $this->processLetterWebhook(),
                str_starts_with($type, 'postcard.') => $this->processPostcardWebhook(),
                str_starts_with($type, 'cheque.') => $this->processChequeWebhook(),
                str_starts_with($type, 'contact.') => $this->processContactWebhook(),
                default => Log::warning('Unknown webhook type', ['type' => $type]),
            };
        });
    }

    /**
     * Store webhook record
     */
    private function storeWebhookRecord(): void
    {
        DB::table('physical_mail_webhooks')->insert([
            'id' => Str::uuid(),
            'postgrid_event_id' => $this->webhookData['id'] ?? Str::uuid(),
            'type' => $this->webhookData['type'] ?? 'unknown',
            'payload' => json_encode($this->webhookData),
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Process letter webhooks
     */
    private function processLetterWebhook(): void
    {
        $data = $this->webhookData['data'] ?? [];
        $postgridId = $data['id'] ?? null;

        if (! $postgridId) {
            Log::warning('Letter webhook missing PostGrid ID');

            return;
        }

        $order = PhysicalMailOrder::where('postgrid_id', $postgridId)->first();

        if (! $order) {
            Log::warning('Order not found for PostGrid ID', ['postgrid_id' => $postgridId]);

            return;
        }

        $this->updateOrderFromWebhook($order, $data);
    }

    /**
     * Process postcard webhooks
     */
    private function processPostcardWebhook(): void
    {
        // Similar to letter processing
        $this->processLetterWebhook();
    }

    /**
     * Process cheque webhooks
     */
    private function processChequeWebhook(): void
    {
        // Similar to letter processing
        $this->processLetterWebhook();
    }

    /**
     * Process contact webhooks (address changes)
     */
    private function processContactWebhook(): void
    {
        $data = $this->webhookData['data'] ?? [];
        $postgridId = $data['id'] ?? null;

        if (! $postgridId) {
            return;
        }

        $contact = PhysicalMailContact::where('postgrid_id', $postgridId)->first();

        if (! $contact) {
            // Create new contact from webhook data
            PhysicalMailContact::fromPostGrid($data);

            return;
        }

        // Update contact with new information
        $this->updateContactFromWebhook($contact, $data);
    }

    /**
     * Update order from webhook data
     */
    private function updateOrderFromWebhook(PhysicalMailOrder $order, array $data): void
    {
        $updates = [];

        // Update status
        if (isset($data['status']) && $data['status'] !== $order->status) {
            $updates['status'] = $data['status'];

            Log::info('Order status updated via webhook', [
                'order_id' => $order->id,
                'old_status' => $order->status,
                'new_status' => $data['status'],
            ]);
        }

        // Update IMB tracking (US mail)
        if (isset($data['imbStatus'])) {
            $updates['imb_status'] = $data['imbStatus'];
        }

        if (isset($data['imbDate'])) {
            $updates['imb_date'] = $data['imbDate'];
        }

        if (isset($data['imbZIPCode'])) {
            $updates['imb_zip_code'] = $data['imbZIPCode'];
        }

        // Update tracking number (certified/registered mail)
        if (isset($data['trackingNumber']) && ! $order->tracking_number) {
            $updates['tracking_number'] = $data['trackingNumber'];

            Log::info('Tracking number added via webhook', [
                'order_id' => $order->id,
                'tracking_number' => $data['trackingNumber'],
            ]);
        }

        // Update PDF URL if not set
        if (isset($data['url']) && ! $order->pdf_url) {
            $updates['pdf_url'] = $data['url'];
        }

        // Update cost if provided
        if (isset($data['cost'])) {
            $updates['cost'] = $data['cost'];
        }

        if (! empty($updates)) {
            $order->update($updates);
        }

        // Check for address changes (NCOA)
        $this->checkAddressChanges($order, $data);

        // Fire events based on status changes
        $this->fireStatusEvents($order, $data['status'] ?? null);
    }

    /**
     * Update contact from webhook data
     */
    private function updateContactFromWebhook(PhysicalMailContact $contact, array $data): void
    {
        $updates = [];

        // Map PostGrid fields to our fields
        $fieldMap = [
            'addressLine1' => 'address_line1',
            'addressLine2' => 'address_line2',
            'city' => 'city',
            'provinceOrState' => 'province_or_state',
            'postalOrZip' => 'postal_or_zip',
            'addressStatus' => 'address_status',
        ];

        foreach ($fieldMap as $pgField => $dbField) {
            if (isset($data[$pgField]) && $data[$pgField] !== $contact->$dbField) {
                $updates[$dbField] = $data[$pgField];
            }
        }

        // Handle address changes (NCOA)
        if (isset($data['addressChange'])) {
            $updates['address_change'] = $data['addressChange'];

            Log::info('Contact address changed via NCOA', [
                'contact_id' => $contact->id,
                'old_address' => $contact->address_line1,
                'new_address' => $data['addressLine1'] ?? 'unknown',
            ]);
        }

        if (! empty($updates)) {
            $contact->update($updates);
        }
    }

    /**
     * Check for address changes in order
     */
    private function checkAddressChanges(PhysicalMailOrder $order, array $data): void
    {
        // Check 'to' address change
        if (isset($data['to']['addressChange'])) {
            $order->mailable->toContact->update([
                'address_change' => $data['to']['addressChange'],
                'address_line1' => $data['to']['addressLine1'] ?? $order->mailable->toContact->address_line1,
                'address_line2' => $data['to']['addressLine2'] ?? $order->mailable->toContact->address_line2,
                'city' => $data['to']['city'] ?? $order->mailable->toContact->city,
                'province_or_state' => $data['to']['provinceOrState'] ?? $order->mailable->toContact->province_or_state,
                'postal_or_zip' => $data['to']['postalOrZip'] ?? $order->mailable->toContact->postal_or_zip,
            ]);

            Log::info('Recipient address updated via NCOA', [
                'order_id' => $order->id,
                'contact_id' => $order->mailable->toContact->id,
            ]);
        }
    }

    /**
     * Fire status change events
     */
    private function fireStatusEvents(PhysicalMailOrder $order, ?string $newStatus): void
    {
        if (! $newStatus) {
            return;
        }

        switch ($newStatus) {
            case 'printing':
                event(new \App\Domains\PhysicalMail\Events\MailOrderPrinting($order));
                break;

            case 'processed_for_delivery':
                event(new \App\Domains\PhysicalMail\Events\MailOrderProcessed($order));
                break;

            case 'completed':
                event(new \App\Domains\PhysicalMail\Events\MailOrderDelivered($order));
                break;

            case 'returned_to_sender':
                event(new \App\Domains\PhysicalMail\Events\MailOrderReturned($order));
                break;
        }
    }
}
