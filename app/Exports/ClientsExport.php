<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Auth;

class ClientsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $isLead;

    public function __construct($isLead = false)
    {
        $this->isLead = $isLead;
    }

    public function query()
    {
        return Client::query()
            ->with(['primaryContact', 'primaryLocation', 'tags'])
            ->where('company_id', Auth::user()->company_id)
            ->whereNull('archived_at')
            ->where('lead', $this->isLead)
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Type',
            'Status',
            'Company',
            'Website',
            'Contact Name',
            'Contact Email',
            'Contact Phone',
            'Address',
            'City',
            'State',
            'Zip',
            'Tags',
            'Created At',
        ];
    }

    public function map($client): array
    {
        $contact = $client->primaryContact;
        $location = $client->primaryLocation;

        return [
            $client->name,
            $client->email ?? $contact?->email ?? '',
            $client->phone ?? $contact?->phone ?? '',
            ucfirst($client->type ?? 'individual'),
            $client->is_active ? 'Active' : 'Inactive',
            $client->company ?? '',
            $client->website ?? '',
            $contact?->name ?? '',
            $contact?->email ?? '',
            $contact?->phone ?? '',
            $location?->address ?? '',
            $location?->city ?? '',
            $location?->state ?? '',
            $location?->zip ?? '',
            $client->tags->pluck('name')->implode(', '),
            $client->created_at->format('Y-m-d H:i:s'),
        ];
    }
}