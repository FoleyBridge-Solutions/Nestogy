<?php

namespace App\Livewire\Leads;

use App\Domains\Core\Models\User;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadSource;
use App\Domains\Lead\Services\LeadImportService;
use App\Livewire\BaseImportComponent;

class LeadImport extends BaseImportComponent
{
    public $leadSourceId = null;

    public $assignedUserId = null;

    public $defaultStatus = 'new';

    public $defaultPriority = 'medium';

    protected function getImportService()
    {
        return app(LeadImportService::class);
    }

    protected function getTemplateDownloadRoute(): string
    {
        return route('leads.import.template');
    }

    protected function getIndexRoute(): string
    {
        return route('leads.index');
    }

    protected function getImportTitle(): string
    {
        return 'Import Leads from CSV';
    }

    protected function getImportDescription(): string
    {
        return 'Upload a CSV file to import multiple leads at once.';
    }

    protected function getImportInstructions(): array
    {
        return [
            [
                'title' => 'Required Fields',
                'content' => 'First Name, Last Name, Email',
            ],
            [
                'title' => 'Optional Personal',
                'content' => 'Middle Name, Phone, Website',
            ],
            [
                'title' => 'Optional Company',
                'content' => 'Company Name, Address Line 1, Address Line 2, City, State, ZIP, Country',
            ],
            [
                'title' => 'Optional Metadata',
                'content' => 'Lead Source, Status, Interest Level, Notes',
            ],
        ];
    }

    protected function getImportSettings(): array
    {
        $leadSources = LeadSource::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $users = User::where('company_id', $this->companyId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return [
            [
                'type' => 'select',
                'label' => 'Lead Source',
                'model' => 'leadSourceId',
                'placeholder' => 'CSV Import (auto-created)',
                'options' => $leadSources,
                'required' => false,
            ],
            [
                'type' => 'select',
                'label' => 'Assign To',
                'model' => 'assignedUserId',
                'placeholder' => 'Unassigned',
                'options' => $users,
                'required' => false,
            ],
            [
                'type' => 'select',
                'label' => 'Default Status',
                'model' => 'defaultStatus',
                'options' => Lead::getStatuses(),
                'required' => true,
            ],
            [
                'type' => 'select',
                'label' => 'Default Priority',
                'model' => 'defaultPriority',
                'options' => Lead::getPriorities(),
                'required' => true,
            ],
            [
                'type' => 'checkbox',
                'label' => 'Skip duplicate emails (recommended)',
                'model' => 'skipDuplicates',
                'description' => 'Leads with email addresses that already exist will be skipped',
            ],
            [
                'type' => 'textarea',
                'label' => 'Import Notes',
                'model' => 'notes',
                'placeholder' => 'Optional notes to add to all imported leads...',
                'rows' => 3,
            ],
        ];
    }

    protected function getDefaultImportOptions(): array
    {
        return [
            'lead_source_id' => $this->leadSourceId,
            'assigned_user_id' => $this->assignedUserId,
            'default_status' => $this->defaultStatus,
            'default_interest_level' => $this->defaultPriority,
        ];
    }
}
