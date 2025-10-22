<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithAuthenticatedUser;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class BaseImportComponent extends Component
{
    use WithAuthenticatedUser;
    use WithFileUploads;

    public $file;

    public $importing = false;

    public $importProgress = 0;

    public $importResults = null;

    public $skipDuplicates = true;

    public $notes = '';

    abstract protected function getImportService();

    abstract protected function getTemplateDownloadRoute(): string;

    abstract protected function getIndexRoute(): string;

    abstract protected function getImportInstructions(): array;

    abstract protected function getImportSettings(): array;

    abstract protected function getDefaultImportOptions(): array;

    protected function getMaxFileSize(): int
    {
        return 10;
    }

    protected function getAllowedExtensions(): array
    {
        return ['csv', 'txt'];
    }

    protected function getImportTitle(): string
    {
        return 'Import Data';
    }

    protected function getImportDescription(): string
    {
        return 'Upload a CSV file to import multiple records at once.';
    }

    public function mount()
    {
        $this->notes = 'Imported from CSV on '.now()->format('Y-m-d');
    }

    public function updatedFile()
    {
        $this->validateFile();
    }

    protected function validateFile()
    {
        if (!$this->file) {
            $this->addError('file', 'Please select a file to upload.');
            throw new \Illuminate\Validation\ValidationException(
                validator([], [])
            );
        }

        $this->validate([
            'file' => [
                'required',
                'max:'.($this->getMaxFileSize() * 1024),
            ],
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'File size must not exceed '.$this->getMaxFileSize().'MB.',
        ]);
    }

    public function removeFile()
    {
        $this->file = null;
        $this->importResults = null;
    }

    public function import()
    {
        \Log::info('Import started', ['file' => $this->file ? 'File present' : 'No file']);

        try {
            $this->validateFile();
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            return;
        }

        $this->importing = true;
        $this->importProgress = 0;
        $this->importResults = null;

        try {
            $importService = $this->getImportService();
            $options = $this->getImportOptions();

            \Log::info('Calling import service', ['options' => $options]);

            $this->importResults = $importService->importFromCsv($this->file, $options);

            $this->importProgress = 100;

            $successMessage = sprintf(
                'Import completed: %d successful, %d errors, %d skipped',
                $this->importResults['success'],
                $this->importResults['errors'],
                $this->importResults['skipped']
            );

            \Log::info('Import completed', ['results' => $this->importResults]);
            $this->dispatch('notify', message: $successMessage, type: 'success');

        } catch (\Exception $e) {
            \Log::error('Import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $errorMessage = 'Import failed: '.$e->getMessage();
            $this->dispatch('notify', message: $errorMessage, type: 'error');
            $this->importResults = [
                'success' => 0,
                'errors' => 1,
                'skipped' => 0,
                'details' => ['Error: '.$e->getMessage()],
            ];
        } finally {
            $this->importing = false;
        }
    }

    protected function getImportOptions(): array
    {
        return array_merge($this->getDefaultImportOptions(), [
            'company_id' => $this->companyId,
            'skip_duplicates' => $this->skipDuplicates,
            'notes' => $this->notes,
        ]);
    }

    public function render()
    {
        return view('livewire.base-import', [
            'importSettings' => $this->getImportSettings(),
            'importInstructions' => $this->getImportInstructions(),
            'templateRoute' => $this->getTemplateDownloadRoute(),
            'indexRoute' => $this->getIndexRoute(),
            'title' => $this->getImportTitle(),
            'description' => $this->getImportDescription(),
        ]);
    }
}
