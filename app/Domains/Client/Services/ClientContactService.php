<?php

namespace App\Domains\Client\Services;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientContactService
{
    /**
     * Get all contacts for a client
     */
    public function getContacts(Client $client): Collection
    {
        return $client->contacts()
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get primary contact for a client
     */
    public function getPrimaryContact(Client $client): ?Contact
    {
        return $client->contacts()
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Create a new contact for a client
     */
    public function createContact(Client $client, array $data): Contact
    {
        DB::beginTransaction();

        try {
            // If this is set as primary, unset other primary contacts
            if (! empty($data['is_primary']) && $data['is_primary']) {
                $client->contacts()->update(['is_primary' => false]);
            }

            // Create the contact
            $contact = $client->contacts()->create([
                'name' => $data['name'],
                'title' => $data['title'] ?? null,
                'phone' => $data['phone'] ?? null,
                'extension' => $data['extension'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'is_technical' => $data['is_technical'] ?? false,
                'is_billing' => $data['is_billing'] ?? false,
                'notes' => $data['notes'] ?? null,
                'company_id' => $client->company_id,
            ]);

            DB::commit();

            Log::info('Contact created for client', [
                'client_id' => $client->id,
                'contact_id' => $contact->id,
            ]);

            return $contact;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create contact', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update a contact
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        DB::beginTransaction();

        try {
            // If setting as primary, unset other primary contacts for this client
            if (! empty($data['is_primary']) && $data['is_primary'] && ! $contact->is_primary) {
                Contact::where('client_id', $contact->client_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }

            $contact->update([
                'name' => $data['name'] ?? $contact->name,
                'title' => $data['title'] ?? $contact->title,
                'phone' => $data['phone'] ?? $contact->phone,
                'extension' => $data['extension'] ?? $contact->extension,
                'mobile' => $data['mobile'] ?? $contact->mobile,
                'email' => $data['email'] ?? $contact->email,
                'is_primary' => $data['is_primary'] ?? $contact->is_primary,
                'is_technical' => $data['is_technical'] ?? $contact->is_technical,
                'is_billing' => $data['is_billing'] ?? $contact->is_billing,
                'notes' => $data['notes'] ?? $contact->notes,
            ]);

            DB::commit();

            Log::info('Contact updated', ['contact_id' => $contact->id]);

            return $contact->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update contact', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a contact
     */
    public function deleteContact(Contact $contact): bool
    {
        try {
            // If this was the primary contact, set another as primary
            if ($contact->is_primary) {
                $nextContact = Contact::where('client_id', $contact->client_id)
                    ->where('id', '!=', $contact->id)
                    ->first();

                if ($nextContact) {
                    $nextContact->update(['is_primary' => true]);
                }
            }

            $contact->delete();

            Log::info('Contact deleted', ['contact_id' => $contact->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete contact', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get technical contacts for a client
     */
    public function getTechnicalContacts(Client $client): Collection
    {
        return $client->contacts()
            ->where('is_technical', true)
            ->get();
    }

    /**
     * Get billing contacts for a client
     */
    public function getBillingContacts(Client $client): Collection
    {
        return $client->contacts()
            ->where('is_billing', true)
            ->get();
    }

    /**
     * Search contacts by name or email
     */
    public function searchContacts(Client $client, string $search): Collection
    {
        return $client->contacts()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            })
            ->get();
    }

    /**
     * Bulk import contacts for a client
     */
    public function bulkImportContacts(Client $client, array $contacts): array
    {
        $imported = [];
        $failed = [];

        DB::beginTransaction();

        try {
            foreach ($contacts as $index => $contactData) {
                try {
                    $contact = $this->createContact($client, $contactData);
                    $imported[] = $contact;
                } catch (\Exception $e) {
                    $failed[] = [
                        'index' => $index,
                        'data' => $contactData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (empty($failed)) {
                DB::commit();

                Log::info('Bulk contact import completed', [
                    'client_id' => $client->id,
                    'imported_count' => count($imported),
                ]);
            } else {
                DB::rollBack();

                Log::warning('Bulk contact import failed', [
                    'client_id' => $client->id,
                    'failed_count' => count($failed),
                ]);
            }

            return [
                'imported' => $imported,
                'failed' => $failed,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk contact import error', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get contact communication history
     */
    public function getCommunicationHistory(Contact $contact): array
    {
        // This would integrate with tickets, emails, etc.
        $history = [];

        // Get tickets created by or assigned to this contact
        if (class_exists(\App\Domains\Ticket\Models\Ticket::class)) {
            $tickets = \App\Domains\Ticket\Models\Ticket::where('contact_id', $contact->id)
                ->orWhere('contact_email', $contact->email)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($tickets as $ticket) {
                $history[] = [
                    'type' => 'ticket',
                    'subject' => $ticket->subject,
                    'date' => $ticket->created_at,
                    'status' => $ticket->status,
                ];
            }
        }

        // Get recent invoice communications
        if (class_exists(\App\Models\Invoice::class)) {
            $invoices = \App\Models\Invoice::where('client_id', $contact->client_id)
                ->where(function ($query) use ($contact) {
                    $query->where('sent_to', $contact->email)
                        ->orWhere('cc_emails', 'like', "%{$contact->email}%");
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($invoices as $invoice) {
                $history[] = [
                    'type' => 'invoice',
                    'subject' => 'Invoice #'.$invoice->invoice_number,
                    'date' => $invoice->created_at,
                    'status' => $invoice->status,
                ];
            }
        }

        // Sort by date
        usort($history, function ($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return $history;
    }

    /**
     * Validate contact email uniqueness within company
     */
    public function isEmailUnique(string $email, int $companyId, ?int $excludeId = null): bool
    {
        $query = Contact::where('company_id', $companyId)
            ->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return ! $query->exists();
    }

    /**
     * Merge duplicate contacts
     */
    public function mergeContacts(Contact $primary, Contact $duplicate): Contact
    {
        DB::beginTransaction();

        try {
            // Merge data (keep primary's data, fill in missing from duplicate)
            $primary->update([
                'phone' => $primary->phone ?: $duplicate->phone,
                'extension' => $primary->extension ?: $duplicate->extension,
                'mobile' => $primary->mobile ?: $duplicate->mobile,
                'title' => $primary->title ?: $duplicate->title,
                'notes' => $primary->notes.($duplicate->notes ? "\n\n".$duplicate->notes : ''),
                'is_technical' => $primary->is_technical || $duplicate->is_technical,
                'is_billing' => $primary->is_billing || $duplicate->is_billing,
            ]);

            // Update any references to the duplicate contact
            // This would need to update tickets, communications, etc.

            // Delete the duplicate
            $duplicate->delete();

            DB::commit();

            Log::info('Contacts merged', [
                'primary_id' => $primary->id,
                'duplicate_id' => $duplicate->id,
            ]);

            return $primary->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to merge contacts', [
                'primary_id' => $primary->id,
                'duplicate_id' => $duplicate->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
