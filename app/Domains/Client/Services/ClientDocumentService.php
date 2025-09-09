<?php

namespace App\Domains\Client\Services;

use App\Domains\Client\Services\ClientBaseService;
use App\Models\ClientDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientDocumentService extends ClientBaseService
{
    protected array $searchableFields = ['name', 'description', 'original_filename', 'tags'];
    
    protected function initializeService(): void
    {
        $this->modelClass = ClientDocument::class;
        $this->defaultEagerLoad = ['client', 'uploader'];
    }
    
    protected function applyCustomFilters($query, array $filters)
    {
        $query = parent::applyCustomFilters($query, $filters);
        
        // Apply category filter
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        
        // Apply confidentiality filter
        if (isset($filters['confidential'])) {
            $query->where('is_confidential', (bool) $filters['confidential']);
        }
        
        // Apply expiry filters
        if (!empty($filters['show_expired'])) {
            $query->expired();
        } elseif (!empty($filters['hide_expired'])) {
            $query->active();
        }
        
        // Enhanced search including client name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('original_filename', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }
        
        return $query;
    }
    
    public function createWithFile(array $data, UploadedFile $file): ClientDocument
    {
        $fileData = $this->processFileUpload($file);
        
        $documentData = array_merge($data, $fileData, [
            'uploaded_by' => auth()->id(),
            'version' => 1,
            'tags' => $this->processTags($data['tags'] ?? ''),
        ]);
        
        return $this->create($documentData);
    }
    
    public function createVersion(ClientDocument $document, UploadedFile $file, array $data = []): ClientDocument
    {
        $fileData = $this->processFileUpload($file);
        
        $latestVersion = $document->versions()->max('version') ?: $document->version;
        
        $versionData = array_merge([
            'client_id' => $document->client_id,
            'uploaded_by' => auth()->id(),
            'name' => $document->name,
            'description' => $data['version_notes'] ?? $document->description,
            'category' => $document->category,
            'is_confidential' => $document->is_confidential,
            'expires_at' => $document->expires_at,
            'tags' => $document->tags,
            'version' => $latestVersion + 1,
            'parent_document_id' => $document->parent_document_id ?: $document->id,
        ], $fileData);
        
        return $this->create($versionData);
    }
    
    public function updateAccessTime(ClientDocument $document): void
    {
        $document->update(['accessed_at' => now()]);
    }
    
    public function getExportData(array $filters = []): \Illuminate\Support\Collection
    {
        $query = $this->buildBaseQuery();
        $query = $this->applyFilters($query, $filters);
        
        return $query->orderBy('created_at', 'desc')->get();
    }
    
    public function getCategories(): array
    {
        return ClientDocument::getCategories();
    }
    
    protected function processFileUpload(UploadedFile $file): array
    {
        $originalFilename = $file->getClientOriginalName();
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'clients/documents/' . $filename;
        
        // Store the file
        $file->storeAs('clients/documents', $filename);
        
        // Calculate file hash
        $fileHash = hash_file('sha256', $file->getRealPath());
        
        return [
            'original_filename' => $originalFilename,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_hash' => $fileHash,
        ];
    }
    
    protected function processTags(string $tags): array
    {
        return $tags ? array_map('trim', explode(',', $tags)) : [];
    }
    
    protected function afterDelete($model): void
    {
        // Clean up file when document is deleted
        if ($model->file_path && Storage::exists($model->file_path)) {
            Storage::delete($model->file_path);
        }
        
        parent::afterDelete($model);
    }
}