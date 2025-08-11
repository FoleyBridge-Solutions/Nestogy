<?php

namespace App\Domains\Client\Services;

use App\Domains\Client\Models\ClientITDocumentation;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientITDocumentationService
{
    /**
     * Create a new IT documentation.
     */
    public function createITDocumentation(array $data, ?UploadedFile $file = null): ClientITDocumentation
    {
        // Handle file upload if provided
        if ($file) {
            $fileData = $this->handleFileUpload($file);
            $data = array_merge($data, $fileData);
        }

        // Set next review date based on schedule
        if (isset($data['review_schedule'])) {
            $data['next_review_at'] = $this->calculateNextReviewDate($data['review_schedule']);
        }

        // Set tenant_id from authenticated user
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['authored_by'] = auth()->id();

        return ClientITDocumentation::create($data);
    }

    /**
     * Update an existing IT documentation.
     */
    public function updateITDocumentation(ClientITDocumentation $documentation, array $data, ?UploadedFile $file = null): ClientITDocumentation
    {
        // Handle file upload if provided
        if ($file) {
            // Delete old file if exists
            if ($documentation->hasFile()) {
                $documentation->deleteFile();
            }

            $fileData = $this->handleFileUpload($file);
            $data = array_merge($data, $fileData);
        }

        // Update next review date if schedule changed
        if (isset($data['review_schedule']) && $data['review_schedule'] !== $documentation->review_schedule) {
            $data['next_review_at'] = $this->calculateNextReviewDate($data['review_schedule']);
        }

        $documentation->update($data);

        return $documentation->fresh();
    }

    /**
     * Generate a new version of existing documentation.
     */
    public function generateNewVersion(ClientITDocumentation $documentation, array $data, ?UploadedFile $file = null): ClientITDocumentation
    {
        // Handle file upload if provided
        if ($file) {
            $fileData = $this->handleFileUpload($file);
            $data = array_merge($data, $fileData);
        }

        // Get the next version number
        $latestVersion = $documentation->versions()->max('version') ?: $documentation->version;
        $nextVersion = $this->incrementVersion($latestVersion);

        // Create new version with updated data
        $newVersionData = array_merge($documentation->toArray(), $data, [
            'id' => null,
            'version' => $nextVersion,
            'parent_document_id' => $documentation->parent_document_id ?: $documentation->id,
            'authored_by' => auth()->id(),
            'created_at' => null,
            'updated_at' => null,
            'deleted_at' => null,
        ]);

        return ClientITDocumentation::create($newVersionData);
    }

    /**
     * Schedule a review for documentation.
     */
    public function scheduleReview(ClientITDocumentation $documentation, string $schedule): void
    {
        $nextReviewDate = $this->calculateNextReviewDate($schedule);
        
        $documentation->update([
            'review_schedule' => $schedule,
            'next_review_at' => $nextReviewDate,
            'last_reviewed_at' => now(),
        ]);
    }

    /**
     * Search documentation with filters.
     */
    public function searchDocumentation(array $filters): Collection
    {
        $query = ClientITDocumentation::with(['client', 'author'])
            ->where('tenant_id', auth()->user()->tenant_id);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (!empty($filters['it_category'])) {
            $query->where('it_category', $filters['it_category']);
        }

        if (!empty($filters['access_level'])) {
            $query->where('access_level', $filters['access_level']);
        }

        if (isset($filters['needs_review']) && $filters['needs_review']) {
            $query->needsReview();
        }

        if (isset($filters['active']) && $filters['active']) {
            $query->active();
        }

        // Sort by most recent
        $query->orderBy('updated_at', 'desc');

        return $query->get();
    }

    /**
     * Get related documents based on common attributes.
     */
    public function getRelatedDocuments(ClientITDocumentation $documentation, int $limit = 5): Collection
    {
        return ClientITDocumentation::where('id', '!=', $documentation->id)
            ->where('tenant_id', $documentation->tenant_id)
            ->where(function($query) use ($documentation) {
                $query->where('client_id', $documentation->client_id)
                      ->orWhere('it_category', $documentation->it_category);
            })
            ->active()
            ->limit($limit)
            ->get();
    }

    /**
     * Duplicate documentation for another client.
     */
    public function duplicateForClient(ClientITDocumentation $documentation, int $clientId): ClientITDocumentation
    {
        $duplicateData = $documentation->toArray();
        
        // Remove unique identifiers and set new client
        unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at'], $duplicateData['deleted_at']);
        $duplicateData['client_id'] = $clientId;
        $duplicateData['authored_by'] = auth()->id();
        $duplicateData['name'] = $duplicateData['name'] . ' (Copy)';
        $duplicateData['version'] = '1.0';
        $duplicateData['parent_document_id'] = null;

        // Handle file duplication if exists
        if ($documentation->hasFile() && $documentation->fileExists()) {
            $originalPath = $documentation->file_path;
            $newFilename = Str::uuid() . '.' . pathinfo($documentation->filename, PATHINFO_EXTENSION);
            $newPath = 'clients/it-documentation/' . $newFilename;

            Storage::copy($originalPath, $newPath);

            $duplicateData['filename'] = $newFilename;
            $duplicateData['file_path'] = $newPath;
        }

        return ClientITDocumentation::create($duplicateData);
    }

    /**
     * Get documentation statistics for a client.
     */
    public function getClientStatistics(int $clientId): array
    {
        $query = ClientITDocumentation::where('client_id', $clientId)
            ->where('tenant_id', auth()->user()->tenant_id);

        return [
            'total' => $query->count(),
            'active' => $query->active()->count(),
            'needs_review' => $query->needsReview()->count(),
            'by_category' => $query->groupBy('it_category')
                                 ->selectRaw('it_category, count(*) as count')
                                 ->pluck('count', 'it_category'),
            'by_access_level' => $query->groupBy('access_level')
                                    ->selectRaw('access_level, count(*) as count')
                                    ->pluck('count', 'access_level'),
        ];
    }

    /**
     * Handle file upload and return file data.
     */
    protected function handleFileUpload(UploadedFile $file): array
    {
        $originalFilename = $file->getClientOriginalName();
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'clients/it-documentation/' . $filename;

        // Store the file
        $file->storeAs('clients/it-documentation', $filename);

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

    /**
     * Calculate next review date based on schedule.
     */
    protected function calculateNextReviewDate(string $schedule): Carbon
    {
        return match($schedule) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addQuarter(),
            'annually' => now()->addYear(),
            default => now()->addYear(), // default to annually for 'as_needed'
        };
    }

    /**
     * Increment version number.
     */
    protected function incrementVersion(string $version): string
    {
        $parts = explode('.', $version);
        $minor = (int)($parts[1] ?? 0);
        $minor++;

        return $parts[0] . '.' . $minor;
    }

    /**
     * Mark documentation as accessed.
     */
    public function markAsAccessed(ClientITDocumentation $documentation): void
    {
        $documentation->increment('access_count');
        $documentation->update(['last_accessed_at' => now()]);
    }

    /**
     * Get overdue reviews.
     */
    public function getOverdueReviews(?int $clientId = null): Collection
    {
        $query = ClientITDocumentation::with(['client', 'author'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->needsReview()
            ->active();

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        return $query->orderBy('next_review_at')->get();
    }

    /**
     * Bulk update access levels.
     */
    public function bulkUpdateAccessLevel(array $documentationIds, string $accessLevel): int
    {
        return ClientITDocumentation::whereIn('id', $documentationIds)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->update(['access_level' => $accessLevel]);
    }

    /**
     * Export documentation data.
     */
    public function exportData(array $filters = []): Collection
    {
        return $this->searchDocumentation($filters)->map(function ($doc) {
            return [
                'name' => $doc->name,
                'description' => $doc->description,
                'category' => $doc->it_category,
                'client' => $doc->client->name,
                'access_level' => $doc->access_level,
                'version' => $doc->version,
                'author' => $doc->author->name,
                'created_at' => $doc->created_at->format('Y-m-d H:i:s'),
                'last_reviewed_at' => $doc->last_reviewed_at?->format('Y-m-d'),
                'next_review_at' => $doc->next_review_at?->format('Y-m-d'),
                'tags' => $doc->tags ? implode(', ', $doc->tags) : '',
            ];
        });
    }
}