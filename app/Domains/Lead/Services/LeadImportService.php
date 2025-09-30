<?php

namespace App\Domains\Lead\Services;

use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadSource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use League\Csv\Statement;

class LeadImportService
{
    protected array $results = [
        'success' => 0,
        'errors' => 0,
        'skipped' => 0,
        'details' => [],
    ];

    protected LeadScoringService $leadScoringService;

    public function __construct(LeadScoringService $leadScoringService)
    {
        $this->leadScoringService = $leadScoringService;
    }

    /**
     * Import leads from CSV file
     */
    public function importFromCsv(UploadedFile $file, array $options = []): array
    {
        $this->results = [
            'success' => 0,
            'errors' => 0,
            'skipped' => 0,
            'details' => [],
        ];

        try {
            // Validate file
            if (! $this->validateCsvFile($file)) {
                throw new \Exception('Invalid CSV file');
            }

            // Read CSV
            $csv = Reader::createFromPath($file->getPathname(), 'r');
            $csv->setHeaderOffset(0);

            // Get default lead source for imports
            $defaultSource = $this->getDefaultImportSource();

            // Process each row
            $records = Statement::create()->process($csv);

            DB::transaction(function () use ($records, $defaultSource, $options) {
                $rowNumber = 1;

                foreach ($records as $record) {
                    $rowNumber++;
                    $this->processRow($record, $defaultSource, $rowNumber, $options);
                }
            });

        } catch (\Exception $e) {
            $this->results['details'][] = 'Import failed: '.$e->getMessage();
        }

        return $this->results;
    }

    /**
     * Validate CSV file format
     */
    protected function validateCsvFile(UploadedFile $file): bool
    {
        if ($file->getClientOriginalExtension() !== 'csv') {
            return false;
        }

        if ($file->getSize() > 10 * 1024 * 1024) { // 10MB limit
            return false;
        }

        return true;
    }

    /**
     * Process a single CSV row
     */
    protected function processRow(array $record, LeadSource $defaultSource, int $rowNumber, array $options): void
    {
        try {
            // Map CSV columns to lead data
            $leadData = $this->mapCsvToLeadData($record, $defaultSource, $options);

            // Validate lead data
            $validator = $this->validateLeadData($leadData);

            if ($validator->fails()) {
                $this->results['errors']++;
                $this->results['details'][] = "Row {$rowNumber}: ".implode(', ', $validator->errors()->all());

                return;
            }

            // Check for duplicates if requested
            if ($options['skip_duplicates'] ?? true) {
                if ($this->isDuplicate($leadData)) {
                    $this->results['skipped']++;
                    $this->results['details'][] = "Row {$rowNumber}: Skipped duplicate (email: {$leadData['email']})";

                    return;
                }
            }

            // Create lead
            $lead = Lead::create($leadData);

            // Calculate initial score
            $scores = $this->leadScoringService->calculateTotalScore($lead);
            $lead->update($scores);

            $this->results['success']++;
            $this->results['details'][] = "Row {$rowNumber}: Created lead {$lead->full_name} (Score: {$lead->total_score})";

        } catch (\Exception $e) {
            $this->results['errors']++;
            $this->results['details'][] = "Row {$rowNumber}: Error - ".$e->getMessage();
        }
    }

    /**
     * Map CSV columns to lead data
     */
    protected function mapCsvToLeadData(array $record, LeadSource $defaultSource, array $options): array
    {
        // Clean and normalize field names (handle variations in column names)
        $cleanRecord = [];
        foreach ($record as $key => $value) {
            $cleanKey = strtolower(trim($key));
            $cleanRecord[$cleanKey] = trim($value);
        }

        return [
            'company_id' => auth()->user()->company_id,
            'first_name' => $this->getFieldValue($cleanRecord, ['first', 'first name', 'firstname']),
            'last_name' => $this->getFieldValue($cleanRecord, ['last', 'last name', 'lastname']),
            'middle_name' => $this->getFieldValue($cleanRecord, ['middle', 'middle name', 'middlename']),
            'email' => $this->getFieldValue($cleanRecord, ['email', 'email address', 'e-mail']),
            'phone' => $this->getFieldValue($cleanRecord, ['phone', 'phone number', 'telephone']),
            'company_name' => $this->getFieldValue($cleanRecord, ['company name', 'comapany name', 'company', 'organization']),
            'company_address_line_1' => $this->getFieldValue($cleanRecord, ['company address line 1', 'address line 1', 'address1', 'street']),
            'company_address_line_2' => $this->getFieldValue($cleanRecord, ['company address line 2', 'address line 2', 'address2', 'suite']),
            'company_city' => $this->getFieldValue($cleanRecord, ['city', 'company city']),
            'company_state' => $this->getFieldValue($cleanRecord, ['state', 'company state', 'province']),
            'company_zip' => $this->getFieldValue($cleanRecord, ['zip', 'zip code', 'postal code', 'postcode']),
            'website' => $this->getFieldValue($cleanRecord, ['website', 'web site', 'url']),
            'lead_source_id' => $options['lead_source_id'] ?? $defaultSource->id,
            'status' => $options['default_status'] ?? Lead::STATUS_NEW,
            'assigned_user_id' => $options['assigned_user_id'] ?? null,
            'interest_level' => $options['default_interest_level'] ?? 'medium',
            'notes' => $options['import_notes'] ?? 'Imported from CSV',
        ];
    }

    /**
     * Get field value with fallback options
     */
    protected function getFieldValue(array $record, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($record[$key]) && ! empty($record[$key])) {
                return $record[$key];
            }
        }

        return null;
    }

    /**
     * Validate lead data
     */
    protected function validateLeadData(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'lead_source_id' => 'required|exists:lead_sources,id',
            'status' => 'required|in:'.implode(',', array_keys(Lead::getStatuses())),
            'interest_level' => 'required|in:low,medium,high,urgent',
        ]);
    }

    /**
     * Check if lead is duplicate
     */
    protected function isDuplicate(array $leadData): bool
    {
        return Lead::where('company_id', $leadData['company_id'])
            ->where('email', $leadData['email'])
            ->exists();
    }

    /**
     * Get or create default import lead source
     */
    protected function getDefaultImportSource(): LeadSource
    {
        return LeadSource::firstOrCreate(
            [
                'company_id' => auth()->user()->company_id,
                'name' => 'CSV Import',
            ],
            [
                'type' => 'import',
                'description' => 'Leads imported from CSV files',
                'is_active' => true,
            ]
        );
    }

    /**
     * Get CSV template headers
     */
    public static function getCsvTemplate(): array
    {
        return [
            'Last',
            'First',
            'Middle',
            'Company Name',
            'Company Address Line 1',
            'Company Address Line 2',
            'City',
            'State',
            'ZIP',
            'Email',
            'Website',
            'Phone',
        ];
    }

    /**
     * Generate CSV template file content
     */
    public static function generateCsvTemplate(): string
    {
        $headers = self::getCsvTemplate();
        $sampleData = [
            'Doe',
            'John',
            'M',
            'Acme Technologies Inc',
            '123 Business St',
            'Suite 100',
            'Dallas',
            'TX',
            '75201',
            'john.doe@acmetechnologies.com',
            'https://acmetechnologies.com',
            '(555) 123-4567',
        ];

        $output = fopen('php://memory', 'w');
        fputcsv($output, $headers);
        fputcsv($output, $sampleData);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
