<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Domains\Core\Services\NavigationService;
use App\Traits\UsesSelectedClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DocumentController extends Controller
{
    use UsesSelectedClient;
    /**
     * Display a listing of documents for the selected client
     */
    public function index(Request $request)
    {
        $client = $this->getSelectedClient($request);

        if (!$client) {
            return redirect()->route('clients.select-screen');
        }

        $query = $client->documents()->with('uploader');

        // Apply search filters
        if ($search = $request->get('search')) {
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

        // Apply category filter
        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        // Apply confidentiality filter
        if ($request->has('confidential')) {
            $query->where('is_confidential', $request->get('confidential') === '1');
        }

        // Apply expiry filter
        if ($request->get('show_expired')) {
            $query->expired();
        } elseif ($request->get('hide_expired')) {
            $query->active();
        }

        $documents = $query->orderBy('created_at', 'desc')
                          ->paginate(20)
                          ->appends($request->query());

        $categories = ClientDocument::getCategories();

        return view('clients.documents.index', compact('documents', 'client', 'categories'));
    }

    /**
     * Show the form for creating a new document
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $categories = ClientDocument::getCategories();

        return view('clients.documents.create', compact('clients', 'selectedClientId', 'categories'));
    }

    /**
     * Store a newly created document
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:' . implode(',', array_keys(ClientDocument::getCategories())),
            'file' => 'required|file|max:51200', // 50MB max
            'is_confidential' => 'boolean',
            'expires_at' => 'nullable|date|after:today',
            'tags' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'clients/documents/' . $filename;

        // Store the file
        $file->storeAs('clients/documents', $filename);

        // Calculate file hash
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Process tags
        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        $document = new ClientDocument([
            'client_id' => $request->client_id,
            'uploaded_by' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'original_filename' => $originalFilename,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_hash' => $fileHash,
            'is_confidential' => $request->has('is_confidential'),
            'expires_at' => $request->expires_at,
            'tags' => $tags,
            'version' => 1,
        ]);
        
        $document->company_id = auth()->user()->company_id;
        $document->save();

        return redirect()->route('clients.documents.standalone.index')
                        ->with('success', 'Document uploaded successfully.');
    }

    /**
     * Display the specified document
     */
    public function show(ClientDocument $document)
    {
        $this->authorize('view', $document);

        $document->load('client', 'uploader', 'versions');
        
        // Update access timestamp
        $document->update(['accessed_at' => now()]);

        return view('clients.documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified document
     */
    public function edit(ClientDocument $document)
    {
        $this->authorize('update', $document);

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $categories = ClientDocument::getCategories();

        return view('clients.documents.edit', compact('document', 'clients', 'categories'));
    }

    /**
     * Update the specified document
     */
    public function update(Request $request, ClientDocument $document)
    {
        $this->authorize('update', $document);

        $validator = Validator::make($request->all(), [
            'client_id' => [
                'required',
                'exists:clients,id',
                Rule::exists('clients', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:' . implode(',', array_keys(ClientDocument::getCategories())),
            'is_confidential' => 'boolean',
            'expires_at' => 'nullable|date|after:today',
            'tags' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Process tags
        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        $document->fill([
            'client_id' => $request->client_id,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'is_confidential' => $request->has('is_confidential'),
            'expires_at' => $request->expires_at,
            'tags' => $tags,
        ]);

        $document->save();

        return redirect()->route('clients.documents.standalone.index')
                        ->with('success', 'Document updated successfully.');
    }

    /**
     * Remove the specified document
     */
    public function destroy(ClientDocument $document)
    {
        $this->authorize('delete', $document);

        $document->delete(); // The model's boot method will handle file deletion

        return redirect()->route('clients.documents.standalone.index')
                        ->with('success', 'Document deleted successfully.');
    }

    /**
     * Download the specified document
     */
    public function download(ClientDocument $document)
    {
        $this->authorize('view', $document);

        if (!$document->fileExists()) {
            abort(404, 'File not found');
        }

        // Update access timestamp
        $document->update(['accessed_at' => now()]);

        return Storage::download($document->file_path, $document->original_filename);
    }

    /**
     * Upload a new version of an existing document
     */
    public function uploadVersion(Request $request, ClientDocument $document)
    {
        $this->authorize('update', $document);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:51200', // 50MB max
            'version_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'clients/documents/' . $filename;

        // Store the file
        $file->storeAs('clients/documents', $filename);

        // Calculate file hash
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Get the next version number
        $latestVersion = $document->versions()->max('version') ?: $document->version;
        $nextVersion = $latestVersion + 1;

        // Create new version
        $newVersion = new ClientDocument([
            'client_id' => $document->client_id,
            'uploaded_by' => auth()->id(),
            'name' => $document->name,
            'description' => $request->version_notes ?: $document->description,
            'category' => $document->category,
            'original_filename' => $originalFilename,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_hash' => $fileHash,
            'is_confidential' => $document->is_confidential,
            'expires_at' => $document->expires_at,
            'tags' => $document->tags,
            'version' => $nextVersion,
            'parent_document_id' => $document->parent_document_id ?: $document->id,
        ]);
        
        $newVersion->company_id = auth()->user()->company_id;
        $newVersion->save();

        return redirect()->route('clients.documents.standalone.show', $newVersion)
                        ->with('success', 'New document version uploaded successfully.');
    }

    /**
     * Export documents to CSV
     */
    public function export(Request $request)
    {
        $query = ClientDocument::with(['client', 'uploader'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

        // Apply same filters as index
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('original_filename', 'like', "%{$search}%");
            });
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($request->has('confidential')) {
            $query->where('is_confidential', $request->get('confidential') === '1');
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

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