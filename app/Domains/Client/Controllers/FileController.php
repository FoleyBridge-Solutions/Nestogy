<?php

namespace App\Domains\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    /**
     * Display a listing of all files (standalone view)
     */
    public function index(Request $request)
    {
        $query = ClientFile::with(['client', 'uploader'])
            ->whereHas('client', function($q) {
                $q->where('company_id', auth()->user()->company_id);
            });

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

        // Apply folder filter
        if ($folder = $request->get('folder')) {
            $query->where('folder', $folder);
        }

        // Apply client filter
        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Apply file type filter
        if ($fileType = $request->get('file_type')) {
            $extensions = $this->getExtensionsByType($fileType);
            $query->where(function($q) use ($extensions) {
                foreach ($extensions as $ext) {
                    $q->orWhere('original_filename', 'like', "%.{$ext}");
                }
            });
        }

        // Apply visibility filter
        if ($request->has('is_public')) {
            $query->where('is_public', $request->get('is_public') === '1');
        }

        $files = $query->orderBy('created_at', 'desc')
                      ->paginate(20)
                      ->appends($request->query());

        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $folders = ClientFile::getFolders();

        return view('clients.files.index', compact('files', 'clients', 'folders'));
    }

    /**
     * Show the form for creating a new file
     */
    public function create(Request $request)
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                        ->orderBy('name')
                        ->get();

        $selectedClientId = $request->get('client_id');
        $folders = ClientFile::getFolders();

        return view('clients.files.create', compact('clients', 'selectedClientId', 'folders'));
    }

    /**
     * Store a newly created file
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
            'folder' => 'required|in:' . implode(',', array_keys(ClientFile::getFolders())),
            'file' => 'required|file|max:102400', // 100MB max
            'is_public' => 'boolean',
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
        $filePath = 'clients/files/' . $filename;

        // Store the file
        $file->storeAs('clients/files', $filename);

        // Calculate file hash
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Process tags
        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        $clientFile = new ClientFile([
            'client_id' => $request->client_id,
            'uploaded_by' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'folder' => $request->folder,
            'original_filename' => $originalFilename,
            'filename' => $filename,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_hash' => $fileHash,
            'is_public' => $request->has('is_public'),
            'download_count' => 0,
            'tags' => $tags,
        ]);
        
        $clientFile->company_id = auth()->user()->company_id;
        $clientFile->save();

        return redirect()->route('clients.files.standalone.index')
                        ->with('success', 'File uploaded successfully.');
    }

    /**
     * Display the specified file
     */
    public function show(ClientFile $file)
    {
        $this->authorize('view', $file);

        $file->load('client', 'uploader');

        return view('clients.files.show', compact('file'));
    }

    /**
     * Remove the specified file
     */
    public function destroy(ClientFile $file)
    {
        $this->authorize('delete', $file);

        $file->delete(); // The model's boot method will handle file deletion

        return redirect()->route('clients.files.standalone.index')
                        ->with('success', 'File deleted successfully.');
    }

    /**
     * Download the specified file
     */
    public function download(ClientFile $file)
    {
        $this->authorize('view', $file);

        if (!$file->fileExists()) {
            abort(404, 'File not found');
        }

        // Increment download count and update access timestamp
        $file->incrementDownloadCount();

        return Storage::download($file->file_path, $file->original_filename);
    }

    /**
     * Export files to CSV
     */
    public function export(Request $request)
    {
        $query = ClientFile::with(['client', 'uploader'])
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

        if ($folder = $request->get('folder')) {
            $query->where('folder', $folder);
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        $files = $query->orderBy('created_at', 'desc')->get();

        $filename = 'files_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($files) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'File Name',
                'Description',
                'Folder',
                'Client Name',
                'Original Filename',
                'File Size',
                'File Type',
                'Uploaded By',
                'Upload Date',
                'Download Count',
                'Public',
                'Tags'
            ]);

            // CSV data
            foreach ($files as $file) {
                fputcsv($file, [
                    $file->name,
                    $file->description,
                    $file->folder,
                    $file->client->display_name,
                    $file->original_filename,
                    $file->file_size_human,
                    $file->file_type,
                    $file->uploader ? $file->uploader->name : '',
                    $file->created_at->format('Y-m-d H:i:s'),
                    $file->download_count,
                    $file->is_public ? 'Yes' : 'No',
                    is_array($file->tags) ? implode(', ', $file->tags) : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get file extensions by type
     */
    private function getExtensionsByType($type)
    {
        $types = [
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
            'spreadsheet' => ['xls', 'xlsx', 'csv', 'ods'],
            'presentation' => ['ppt', 'pptx', 'odp'],
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'code' => ['html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c'],
        ];

        return $types[$type] ?? [];
    }
}