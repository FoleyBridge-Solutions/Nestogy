<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\Contact;
use App\Models\Location;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AssetsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    protected $clientId;
    protected $companyId;
    protected $importedCount = 0;
    protected $skippedCount = 0;

    public function __construct($clientId)
    {
        $this->clientId = $clientId;
        $this->companyId = Auth::user()->company_id;
    }

    public function model(array $row)
    {
        // Skip if asset with same name already exists for this client
        $existingAsset = Asset::where('name', $row['name'])
            ->where('client_id', $this->clientId)
            ->where('company_id', $this->companyId)
            ->first();

        if ($existingAsset) {
            $this->skippedCount++;
            return null;
        }

        // Find contact if specified
        $contactId = null;
        if (!empty($row['assigned_to'])) {
            $contact = Contact::where('name', $row['assigned_to'])
                ->where('client_id', $this->clientId)
                ->where('company_id', $this->companyId)
                ->first();
            
            if ($contact) {
                $contactId = $contact->id;
            }
        }

        // Find location if specified
        $locationId = null;
        if (!empty($row['location'])) {
            $location = Location::where('name', $row['location'])
                ->where('client_id', $this->clientId)
                ->where('company_id', $this->companyId)
                ->first();
            
            if ($location) {
                $locationId = $location->id;
            }
        }

        $this->importedCount++;

        return new Asset([
            'company_id' => $this->companyId,
            'client_id' => $this->clientId,
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'type' => $row['type'],
            'make' => $row['make'],
            'model' => $row['model'] ?? null,
            'serial' => $row['serial'] ?? null,
            'os' => $row['os'] ?? null,
            'status' => 'Ready To Deploy',
            'contact_id' => $contactId,
            'location_id' => $locationId,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(Asset::TYPES)],
            'make' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial' => 'nullable|string|max:255',
            'os' => 'nullable|string|max:255',
            'assigned_to' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'name.required' => 'Asset name is required',
            'type.required' => 'Asset type is required',
            'type.in' => 'Asset type must be one of: ' . implode(', ', Asset::TYPES),
            'make.required' => 'Asset make/manufacturer is required',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}