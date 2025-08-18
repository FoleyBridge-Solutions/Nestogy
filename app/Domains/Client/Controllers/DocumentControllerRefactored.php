<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\BaseResourceController;
use App\Http\Controllers\Traits\HasClientRelation;
use App\Domains\Client\Services\ClientDocumentService;
use App\Domains\Client\Requests\StoreClientDocumentRequest;
use App\Domains\Client\Requests\UpdateClientDocumentRequest;
use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentControllerRefactored extends BaseResourceController
{
    use HasClientRelation;

    protected function initializeController(): void
    {
        $this->service = app(ClientDocumentService::class);
        $this->resourceName = 'document';
        $this->viewPath = 'clients.documents';
        $this->routePrefix = 'clients.documents.standalone';
        $this->perPage = 20;
    }

    protected function getModelClass(): string
    {
        return ClientDocument::class;
    }
    
    protected function getAllowedFilters(): array
    {
        return array_merge(parent::getAllowedFilters(), [
            'category', 'confidential', 'show_expired', 'hide_expired'
        ]);
    }
    
    public function index(Request $request)
    {
        $result = parent::index($request);
        
        if ($request->expectsJson()) {
            return $result;
        }
        
        // Add additional data for the view
        $result->with([
            'clients' => $this->getClientFilterOptions(),
            'categories' => $this->service->getCategories()
        ]);
        
        return $result;
    }

    public function create(Request $request)
    {
        $result = parent::create($request);
        
        return $result->with([
            'clients' => $this->getClientFilterOptions(),
            'selectedClientId' => $request->get('client_id'),
            'categories' => $this->service->getCategories()
        ]);
    }

    public function store(StoreClientDocumentRequest $request)
    {
        try {
            $file = $request->file('file');
            $document = $this->service->createWithFile($request->validated(), $file);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Document uploaded successfully.',
                    'data' => $document,
                ], 201);
            }
            
            return redirect()
                ->route($this->routePrefix . '.show', $document)
                ->with('success', 'Document uploaded successfully.');
                
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to upload document',
                    'error' => $e->getMessage()
                ], 422);
            }
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to upload document: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $document = $this->service->findByIdOrFail($id);
        $this->authorize('view', $document);
        
        $document->load('versions');
        $this->service->updateAccessTime($document);
        
        if (request()->expectsJson()) {
            return response()->json(['data' => $document]);
        }
        
        return view($this->getViewName('show'), [
            'item' => $document,
            'document' => $document, // Legacy compatibility
            'resourceName' => $this->resourceName,
            'routePrefix' => $this->routePrefix,
        ]);
    }

    public function edit($id)
    {
        $result = parent::edit($id);
        
        return $result->with([
            'clients' => $this->getClientFilterOptions(),
            'categories' => $this->service->getCategories()
        ]);
    }

    public function update(UpdateClientDocumentRequest $request, $id)
    {
        return parent::update($request, $id);
    }

    // Custom methods specific to document management

    public function download(ClientDocument $document)
    {
        $this->authorize('view', $document);

        if (!$document->fileExists()) {
            abort(404, 'File not found');
        }

        // Update access timestamp
        $this->service->updateAccessTime($document);

        return Storage::download($document->file_path, $document->original_filename);
    }

    public function uploadVersion(Request $request, ClientDocument $document)
    {
        $this->authorize('update', $document);

        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
            'version_notes' => 'nullable|string',
        ]);

        try {
            $file = $request->file('file');
            $newVersion = $this->service->createVersion($document, $file, $request->only('version_notes'));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'New document version uploaded successfully.',
                    'data' => $newVersion,
                ]);
            }

            return redirect()
                ->route($this->routePrefix . '.show', $newVersion)
                ->with('success', 'New document version uploaded successfully.');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to upload document version',
                    'error' => $e->getMessage()
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to upload document version: ' . $e->getMessage()]);
        }
    }

    public function export(Request $request)
    {
        $filters = $request->only($this->getAllowedFilters());
        $documents = $this->service->getExportData($filters);

        $filename = 'documents_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($documents) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Document Name',
                'Description', 
                'Category',
                'Client Name',
                'Original Filename',
                'File Size',
                'Uploaded By',
                'Upload Date',
                'Confidential',
                'Expires At',
                'Tags',
                'Version'
            ]);

            // CSV data
            foreach ($documents as $document) {
                fputcsv($file, [
                    $document->name,
                    $document->description,
                    $document->category,
                    $document->client->display_name,
                    $document->original_filename,
                    $document->file_size_human,
                    $document->uploader ? $document->uploader->name : '',
                    $document->created_at->format('Y-m-d H:i:s'),
                    $document->is_confidential ? 'Yes' : 'No',
                    $document->expires_at ? $document->expires_at->format('Y-m-d') : '',
                    is_array($document->tags) ? implode(', ', $document->tags) : '',
                    $document->version,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}