<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class AssetsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $clientId;

    public function __construct($clientId = null)
    {
        $this->clientId = $clientId;
    }

    public function query()
    {
        $query = Asset::with(['client', 'location', 'contact', 'vendor', 'network'])
            ->where('company_id', Auth::user()->company_id)
            ->notArchived();

        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }

        return $query->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Type',
            'Make',
            'Model',
            'Serial Number',
            'Operating System',
            'Status',
            'Client',
            'Location',
            'Assigned To',
            'Vendor',
            'Network',
            'IP Address',
            'NAT IP',
            'MAC Address',
            'URI',
            'Secondary URI',
            'Purchase Date',
            'Warranty Expires',
            'Install Date',
            'Notes',
            'Created At',
            'Updated At'
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->id,
            $asset->name,
            $asset->description,
            $asset->type,
            $asset->make,
            $asset->model,
            $asset->serial,
            $asset->os,
            $asset->status,
            $asset->client->name ?? '',
            $asset->location->name ?? '',
            $asset->contact->name ?? '',
            $asset->vendor->name ?? '',
            $asset->network ? $asset->network->name . ' (' . $asset->network->network . ')' : '',
            $asset->ip,
            $asset->nat_ip,
            $asset->mac,
            $asset->uri,
            $asset->uri_2,
            $asset->purchase_date?->format('Y-m-d'),
            $asset->warranty_expire?->format('Y-m-d'),
            $asset->install_date?->format('Y-m-d'),
            $asset->notes,
            $asset->created_at->format('Y-m-d H:i:s'),
            $asset->updated_at->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}