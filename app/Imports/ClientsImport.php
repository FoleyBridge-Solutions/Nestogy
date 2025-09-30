<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ClientsImport implements ToCollection, WithBatchInserts, WithChunkReading, WithHeadingRow, WithValidation
{
    private $rowCount = 0;

    private $user;

    public function __construct()
    {
        $this->user = Auth::user();
    }

    /**
     * Process the imported collection
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            DB::transaction(function () use ($row) {
                // Create client
                $client = Client::create([
                    'company_id' => $this->user->company_id,
                    'lead' => false,
                    'name' => $row['name'],
                    'company_name' => $row['company_name'] ?? null,
                    'type' => $row['type'] ?? null,
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'city' => $row['city'] ?? null,
                    'state' => $row['state'] ?? null,
                    'zip_code' => $row['zip_code'] ?? null,
                    'country' => $row['country'] ?? 'US',
                    'website' => $row['website'] ?? null,
                    'referral' => $row['referral'] ?? null,
                    'rate' => $row['rate'] ?? null,
                    'currency_code' => $row['currency_code'] ?? 'USD',
                    'net_terms' => $row['net_terms'] ?? 30,
                    'tax_id_number' => $row['tax_id_number'] ?? null,
                    'notes' => $row['notes'] ?? null,
                    'status' => 'active',
                    'created_by' => $this->user->id,
                ]);

                // Create primary contact if provided
                if (! empty($row['contact_name']) || ! empty($row['contact_email'])) {
                    $contact = Contact::create([
                        'company_id' => $this->user->company_id,
                        'client_id' => $client->id,
                        'name' => $row['contact_name'] ?? 'Primary Contact',
                        'email' => $row['contact_email'] ?? null,
                        'phone' => $row['contact_phone'] ?? null,
                        'mobile' => $row['contact_mobile'] ?? null,
                        'primary' => true,
                    ]);
                }

                // Create primary location if provided
                if (! empty($row['location_address']) || ! empty($row['location_name'])) {
                    $location = Location::create([
                        'company_id' => $this->user->company_id,
                        'client_id' => $client->id,
                        'name' => $row['location_name'] ?? 'Primary Location',
                        'address' => $row['location_address'] ?? $row['address'] ?? null,
                        'city' => $row['location_city'] ?? $row['city'] ?? null,
                        'state' => $row['location_state'] ?? $row['state'] ?? null,
                        'zip' => $row['location_zip'] ?? $row['zip_code'] ?? null,
                        'phone' => $row['location_phone'] ?? null,
                        'primary' => true,
                    ]);
                }

                $this->rowCount++;
            });
        }
    }

    /**
     * Define validation rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'referral' => 'nullable|string|max:255',
            'rate' => 'nullable|numeric|min:0',
            'currency_code' => 'nullable|string|size:3',
            'net_terms' => 'nullable|integer|min:0|max:365',
            'tax_id_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_mobile' => 'nullable|string|max:20',
            'location_name' => 'nullable|string|max:255',
            'location_address' => 'nullable|string|max:255',
            'location_city' => 'nullable|string|max:255',
            'location_state' => 'nullable|string|max:255',
            'location_zip' => 'nullable|string|max:20',
            'location_phone' => 'nullable|string|max:20',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Client name is required',
            'email.email' => 'Invalid email format for :attribute',
            'website.url' => 'Invalid URL format for :attribute',
            'rate.numeric' => 'Rate must be a number',
            'currency_code.size' => 'Currency code must be exactly 3 characters',
            'net_terms.integer' => 'Net terms must be a whole number',
            'net_terms.max' => 'Net terms cannot exceed 365 days',
        ];
    }

    /**
     * Get the number of rows imported
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Batch size for inserts
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Chunk size for reading
     */
    public function chunkSize(): int
    {
        return 100;
    }
}
