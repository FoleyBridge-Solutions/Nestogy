<?php

namespace App\Domains\PhysicalMail\Services;

use App\Domains\PhysicalMail\Models\PhysicalMailContact;
use App\Domains\PhysicalMail\Services\PostGridClient;

class PhysicalMailContactService
{
    public function __construct(private PostGridClient $postgrid)
    {
        // Dependencies are injected
    }
    
    /**
     * Find or create a contact
     */
    public function findOrCreate(array $data): PhysicalMailContact
    {
        // Try to find existing contact
        $query = PhysicalMailContact::query();
        
        if (!empty($data['email'])) {
            $query->where('email', $data['email']);
        } elseif (!empty($data['addressLine1']) || !empty($data['address_line1'])) {
            $addressLine = $data['addressLine1'] ?? $data['address_line1'];
            $query->where('address_line1', $addressLine);
            
            if (!empty($data['postalOrZip']) || !empty($data['postal_or_zip'])) {
                $postal = $data['postalOrZip'] ?? $data['postal_or_zip'];
                $query->where('postal_or_zip', $postal);
            }
        }
        
        $contact = $query->first();
        
        if ($contact) {
            return $contact;
        }
        
        // Create new contact
        return $this->create($data);
    }
    
    /**
     * Create a new contact
     */
    public function create(array $data): PhysicalMailContact
    {
        // Normalize field names from PostGrid format
        $normalized = $this->normalizeContactData($data);
        
        // Create local contact
        $contact = PhysicalMailContact::create($normalized);
        
        // Sync with PostGrid if not already synced
        if (!$contact->postgrid_id) {
            try {
                $response = $this->postgrid->createContact($contact->toPostGridArray());
                $contact->update([
                    'postgrid_id' => $response['id'],
                    'address_status' => $response['addressStatus'] ?? 'unverified',
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to sync contact with PostGrid', [
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $contact;
    }
    
    /**
     * Sync contact from PostGrid
     */
    public function syncFromPostGrid(string $postgridId): PhysicalMailContact
    {
        $response = $this->postgrid->getContact($postgridId);
        return PhysicalMailContact::fromPostGrid($response);
    }
    
    /**
     * Update contact and sync with PostGrid
     */
    public function update(\Illuminate\Database\Eloquent\Model $model, array $data): \Illuminate\Database\Eloquent\Model
    {
        $normalized = $this->normalizeContactData($data);
        $model->update($normalized);
        
        // If contact has PostGrid ID, update in PostGrid
        if ($model->postgrid_id) {
            try {
                // PostGrid doesn't have update endpoint, would need to create new
                \Log::info('Contact updated locally, PostGrid sync skipped', ['contact_id' => $model->id]);
            } catch (\Exception $e) {
                \Log::warning('Failed to update contact in PostGrid', [
                    'contact_id' => $model->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $model;
    }
    
    /**
     * Normalize contact data from various formats
     */
    private function normalizeContactData(array $data): array
    {
        $normalized = [];
        
        // Map PostGrid field names to our database field names
        $fieldMap = [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'companyName' => 'company_name',
            'jobTitle' => 'job_title',
            'addressLine1' => 'address_line1',
            'addressLine2' => 'address_line2',
            'city' => 'city',
            'provinceOrState' => 'province_or_state',
            'postalOrZip' => 'postal_or_zip',
            'country' => 'country_code',
            'countryCode' => 'country_code',
            'email' => 'email',
            'phoneNumber' => 'phone_number',
            'addressStatus' => 'address_status',
        ];
        
        foreach ($data as $key => $value) {
            $normalizedKey = $fieldMap[$key] ?? $key;
            if (in_array($normalizedKey, (new PhysicalMailContact)->getFillable())) {
                $normalized[$normalizedKey] = $value;
            }
        }
        
        // Ensure country code is uppercase and 2 characters
        if (isset($normalized['country_code'])) {
            $normalized['country_code'] = strtoupper(substr($normalized['country_code'], 0, 2));
        }
        
        // Set client_id if provided
        if (isset($data['client_id'])) {
            $normalized['client_id'] = $data['client_id'];
        }
        
        return $normalized;
    }
    
    /**
     * Verify address with PostGrid
     */
    public function verifyAddress(PhysicalMailContact $contact): array
    {
        // This would use PostGrid's address verification API
        // For now, return mock data
        return [
            'verified' => true,
            'corrected' => false,
            'address' => $contact->toPostGridArray(),
        ];
    }
}