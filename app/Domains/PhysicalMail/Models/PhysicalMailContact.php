<?php

namespace App\Domains\PhysicalMail\Models;

use App\Models\Client;
use App\Models\Traits\HasPostGridIntegration;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalMailContact extends Model
{
    use HasFactory, HasUuids, HasPostGridIntegration;

    protected $table = 'physical_mail_contacts';

    protected $fillable = [
        'postgrid_id',
        'client_id',
        'first_name',
        'last_name',
        'company_name',
        'job_title',
        'address_line1',
        'address_line2',
        'city',
        'province_or_state',
        'postal_or_zip',
        'country_code',
        'email',
        'phone_number',
        'address_status',
        'address_change',
        'metadata',
    ];

    protected $casts = [
        'address_change' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the client for this contact
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the full name of the contact
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        return implode(' ', $parts) ?: $this->company_name ?: 'Unknown';
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute(): string
    {
        $lines = array_filter([
            $this->full_name,
            $this->company_name,
            $this->address_line1,
            $this->address_line2,
            implode(', ', array_filter([
                $this->city,
                $this->province_or_state,
                $this->postal_or_zip
            ])),
            $this->country_code,
        ]);
        
        return implode("\n", $lines);
    }

    /**
     * Check if address is verified
     */
    public function isAddressVerified(): bool
    {
        return $this->address_status === 'verified';
    }

    /**
     * Get PostGrid resource name
     */
    protected function getPostGridResource(): string
    {
        return 'contacts';
    }

    /**
     * Convert to PostGrid API format
     */
    public function toPostGridArray(): array
    {
        return array_filter([
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'companyName' => $this->company_name,
            'jobTitle' => $this->job_title,
            'addressLine1' => $this->address_line1,
            'addressLine2' => $this->address_line2,
            'city' => $this->city,
            'provinceOrState' => $this->province_or_state,
            'postalOrZip' => $this->postal_or_zip,
            'country' => $this->country_code,
            'email' => $this->email,
            'phoneNumber' => $this->phone_number,
            'metadata' => $this->metadata,
        ], fn($value) => !is_null($value));
    }

    /**
     * Create or update from PostGrid response
     */
    public static function fromPostGrid(array $data): self
    {
        return self::updateOrCreate(
            ['postgrid_id' => $data['id']],
            [
                'first_name' => $data['firstName'] ?? null,
                'last_name' => $data['lastName'] ?? null,
                'company_name' => $data['companyName'] ?? null,
                'job_title' => $data['jobTitle'] ?? null,
                'address_line1' => $data['addressLine1'],
                'address_line2' => $data['addressLine2'] ?? null,
                'city' => $data['city'] ?? null,
                'province_or_state' => $data['provinceOrState'] ?? null,
                'postal_or_zip' => $data['postalOrZip'] ?? null,
                'country_code' => $data['countryCode'] ?? $data['country'] ?? 'US',
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phoneNumber'] ?? null,
                'address_status' => $data['addressStatus'] ?? 'unverified',
                'metadata' => $data['metadata'] ?? [],
            ]
        );
    }
}