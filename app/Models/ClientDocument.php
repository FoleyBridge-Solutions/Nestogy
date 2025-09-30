<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Client Document Model
 *
 * Manages documents and files shared with clients through the portal.
 * Supports versioning, security controls, and usage tracking.
 *
 * @property int $id
 * @property int $company_id
 * @property int $client_id
 * @property string $type
 * @property string|null $category
 * @property string|null $subcategory
 * @property string $title
 * @property string|null $description
 * @property string $filename
 * @property string $original_filename
 * @property string $mime_type
 * @property string $extension
 * @property int $file_size
 * @property string $storage_path
 * @property string $storage_disk
 * @property string|null $file_hash
 * @property string|null $checksum
 * @property bool $is_system_generated
 * @property bool $is_template_based
 * @property string|null $template_id
 * @property bool $is_signed
 * @property array|null $signatures
 * @property bool $is_encrypted
 * @property string|null $encryption_method
 * @property string $visibility
 * @property bool $requires_authentication
 * @property bool $download_enabled
 * @property bool $view_enabled
 * @property bool $print_enabled
 * @property bool $share_enabled
 * @property array|null $access_permissions
 * @property array|null $download_restrictions
 * @property int $security_level
 * @property bool $contains_pii
 * @property bool $contains_phi
 * @property bool $contains_financial_data
 * @property array|null $compliance_tags
 * @property bool $requires_retention
 * @property \Illuminate\Support\Carbon|null $retention_until
 * @property bool $auto_delete_enabled
 * @property int $version
 * @property int|null $parent_document_id
 * @property bool $is_current_version
 * @property array|null $version_notes
 * @property \Illuminate\Support\Carbon|null $superseded_at
 * @property int|null $superseded_by
 * @property int|null $invoice_id
 * @property int|null $payment_id
 * @property int|null $contract_id
 * @property int|null $ticket_id
 * @property int|null $project_id
 * @property string|null $related_model_type
 * @property int|null $related_model_id
 * @property string $processing_status
 * @property string|null $processing_error
 * @property bool $text_extracted
 * @property string|null $extracted_text
 * @property array|null $metadata_extracted
 * @property bool $thumbnail_generated
 * @property string|null $thumbnail_path
 * @property int|null $page_count
 * @property bool $email_delivery_enabled
 * @property array|null $email_recipients
 * @property \Illuminate\Support\Carbon|null $last_emailed_at
 * @property bool $portal_notification_sent
 * @property \Illuminate\Support\Carbon|null $portal_notification_sent_at
 * @property bool $requires_acknowledgment
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property string|null $acknowledgment_method
 * @property int $view_count
 * @property int $download_count
 * @property int $print_count
 * @property int $share_count
 * @property \Illuminate\Support\Carbon|null $first_viewed_at
 * @property \Illuminate\Support\Carbon|null $last_viewed_at
 * @property \Illuminate\Support\Carbon|null $first_downloaded_at
 * @property \Illuminate\Support\Carbon|null $last_downloaded_at
 * @property array|null $access_history
 * @property string|null $external_id
 * @property string|null $external_source
 * @property array|null $external_metadata
 * @property bool $sync_enabled
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property string|null $sync_error
 * @property array|null $tags
 * @property array|null $labels
 * @property string|null $reference_number
 * @property \Illuminate\Support\Carbon|null $document_date
 * @property \Illuminate\Support\Carbon|null $received_date
 * @property \Illuminate\Support\Carbon|null $effective_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property bool $show_in_portal
 * @property int|null $portal_sort_order
 * @property string|null $portal_display_name
 * @property string|null $portal_description
 * @property string|null $portal_icon
 * @property array|null $portal_settings
 * @property string|null $workflow_status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property bool $requires_approval
 * @property array|null $approval_workflow
 * @property array|null $custom_fields
 * @property array|null $metadata
 * @property string|null $notes
 * @property string|null $client_notes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $deletion_reason
 * @property int $uploaded_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ClientDocument extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'client_documents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'type',
        'category',
        'subcategory',
        'title',
        'description',
        'filename',
        'original_filename',
        'mime_type',
        'extension',
        'file_size',
        'storage_path',
        'storage_disk',
        'file_hash',
        'checksum',
        'is_system_generated',
        'is_template_based',
        'template_id',
        'is_signed',
        'signatures',
        'is_encrypted',
        'encryption_method',
        'visibility',
        'requires_authentication',
        'download_enabled',
        'view_enabled',
        'print_enabled',
        'share_enabled',
        'access_permissions',
        'download_restrictions',
        'security_level',
        'contains_pii',
        'contains_phi',
        'contains_financial_data',
        'compliance_tags',
        'requires_retention',
        'retention_until',
        'auto_delete_enabled',
        'version',
        'parent_document_id',
        'is_current_version',
        'version_notes',
        'superseded_at',
        'superseded_by',
        'invoice_id',
        'payment_id',
        'contract_id',
        'ticket_id',
        'project_id',
        'related_model_type',
        'related_model_id',
        'processing_status',
        'processing_error',
        'text_extracted',
        'extracted_text',
        'metadata_extracted',
        'thumbnail_generated',
        'thumbnail_path',
        'page_count',
        'email_delivery_enabled',
        'email_recipients',
        'last_emailed_at',
        'portal_notification_sent',
        'portal_notification_sent_at',
        'requires_acknowledgment',
        'acknowledged_at',
        'acknowledgment_method',
        'view_count',
        'download_count',
        'print_count',
        'share_count',
        'first_viewed_at',
        'last_viewed_at',
        'first_downloaded_at',
        'last_downloaded_at',
        'access_history',
        'external_id',
        'external_source',
        'external_metadata',
        'sync_enabled',
        'last_synced_at',
        'sync_error',
        'tags',
        'labels',
        'reference_number',
        'document_date',
        'received_date',
        'effective_date',
        'expiry_date',
        'show_in_portal',
        'portal_sort_order',
        'portal_display_name',
        'portal_description',
        'portal_icon',
        'portal_settings',
        'workflow_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'requires_approval',
        'approval_workflow',
        'custom_fields',
        'metadata',
        'notes',
        'client_notes',
        'status',
        'archived_at',
        'deletion_reason',
        'uploaded_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'file_size' => 'integer',
        'is_system_generated' => 'boolean',
        'is_template_based' => 'boolean',
        'is_signed' => 'boolean',
        'signatures' => 'array',
        'is_encrypted' => 'boolean',
        'requires_authentication' => 'boolean',
        'download_enabled' => 'boolean',
        'view_enabled' => 'boolean',
        'print_enabled' => 'boolean',
        'share_enabled' => 'boolean',
        'access_permissions' => 'array',
        'download_restrictions' => 'array',
        'security_level' => 'integer',
        'contains_pii' => 'boolean',
        'contains_phi' => 'boolean',
        'contains_financial_data' => 'boolean',
        'compliance_tags' => 'array',
        'requires_retention' => 'boolean',
        'retention_until' => 'date',
        'auto_delete_enabled' => 'boolean',
        'version' => 'integer',
        'parent_document_id' => 'integer',
        'is_current_version' => 'boolean',
        'version_notes' => 'array',
        'superseded_at' => 'datetime',
        'superseded_by' => 'integer',
        'invoice_id' => 'integer',
        'payment_id' => 'integer',
        'contract_id' => 'integer',
        'ticket_id' => 'integer',
        'project_id' => 'integer',
        'related_model_id' => 'integer',
        'text_extracted' => 'boolean',
        'metadata_extracted' => 'array',
        'thumbnail_generated' => 'boolean',
        'page_count' => 'integer',
        'email_delivery_enabled' => 'boolean',
        'email_recipients' => 'array',
        'last_emailed_at' => 'datetime',
        'portal_notification_sent' => 'boolean',
        'portal_notification_sent_at' => 'datetime',
        'requires_acknowledgment' => 'boolean',
        'acknowledged_at' => 'datetime',
        'view_count' => 'integer',
        'download_count' => 'integer',
        'print_count' => 'integer',
        'share_count' => 'integer',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'first_downloaded_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'access_history' => 'array',
        'external_metadata' => 'array',
        'sync_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
        'tags' => 'array',
        'labels' => 'array',
        'document_date' => 'date',
        'received_date' => 'date',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'show_in_portal' => 'boolean',
        'portal_sort_order' => 'integer',
        'portal_settings' => 'array',
        'approved_by' => 'integer',
        'approved_at' => 'datetime',
        'requires_approval' => 'boolean',
        'approval_workflow' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
        'archived_at' => 'datetime',
        'deleted_at' => 'datetime',
        'uploaded_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Document type constants
     */
    const TYPE_INVOICE = 'invoice';

    const TYPE_CONTRACT = 'contract';

    const TYPE_MANUAL = 'manual';

    const TYPE_CERTIFICATE = 'certificate';

    const TYPE_REPORT = 'report';

    const TYPE_RECEIPT = 'receipt';

    const TYPE_STATEMENT = 'statement';

    /**
     * Category constants
     */
    const CATEGORY_BILLING = 'billing';

    const CATEGORY_TECHNICAL = 'technical';

    const CATEGORY_LEGAL = 'legal';

    const CATEGORY_MARKETING = 'marketing';

    const CATEGORY_SUPPORT = 'support';

    /**
     * Visibility constants
     */
    const VISIBILITY_PRIVATE = 'private';

    const VISIBILITY_SHARED = 'shared';

    const VISIBILITY_PUBLIC = 'public';

    /**
     * Processing status constants
     */
    const PROCESSING_READY = 'ready';

    const PROCESSING_PROCESSING = 'processing';

    const PROCESSING_FAILED = 'failed';

    const PROCESSING_CORRUPTED = 'corrupted';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';

    const STATUS_ARCHIVED = 'archived';

    const STATUS_DELETED = 'deleted';

    const STATUS_EXPIRED = 'expired';

    /**
     * Get the client that owns this document.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the company that owns this document.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the user who last updated this document.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved this document.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who superseded this document.
     */
    public function superseder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superseded_by');
    }

    /**
     * Get the parent document (previous version).
     */
    public function parentDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    /**
     * Get child documents (newer versions).
     */
    public function childDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_document_id')->orderBy('version', 'desc');
    }

    /**
     * Get the current version of this document.
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_document_id')
            ->where('is_current_version', true);
    }

    /**
     * Get all versions of this document.
     */
    public function allVersions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_document_id')
            ->orderBy('version', 'desc');
    }

    /**
     * Get the related invoice.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the related payment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the related ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the related project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the related model (polymorphic relation).
     */
    public function relatedModel()
    {
        return $this->morphTo('related_model', 'related_model_type', 'related_model_id');
    }

    /**
     * Check if document is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->isExpired();
    }

    /**
     * Check if document is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED || $this->archived_at !== null;
    }

    /**
     * Check if document is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && Carbon::now()->gt($this->expiry_date);
    }

    /**
     * Check if document is current version.
     */
    public function isCurrentVersion(): bool
    {
        return $this->is_current_version === true;
    }

    /**
     * Check if document requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval === true && ! $this->approved_at;
    }

    /**
     * Check if document is approved.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Check if document requires acknowledgment.
     */
    public function requiresAcknowledgment(): bool
    {
        return $this->requires_acknowledgment === true && ! $this->acknowledged_at;
    }

    /**
     * Check if document is acknowledged.
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Check if document can be viewed.
     */
    public function canBeViewed(): bool
    {
        return $this->view_enabled === true && $this->isActive();
    }

    /**
     * Check if document can be downloaded.
     */
    public function canBeDownloaded(): bool
    {
        return $this->download_enabled === true && $this->isActive();
    }

    /**
     * Check if document can be shared.
     */
    public function canBeShared(): bool
    {
        return $this->share_enabled === true && $this->isActive();
    }

    /**
     * Get display name for the document.
     */
    public function getDisplayName(): string
    {
        return $this->portal_display_name ?: $this->title;
    }

    /**
     * Get display description for the document.
     */
    public function getDisplayDescription(): string
    {
        return $this->portal_description ?: $this->description;
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSize(): string
    {
        return $this->formatBytes($this->file_size);
    }

    /**
     * Get file URL for download.
     */
    public function getDownloadUrl(): string
    {
        return route('portal.documents.download', $this->id);
    }

    /**
     * Get file URL for viewing.
     */
    public function getViewUrl(): string
    {
        return route('portal.documents.view', $this->id);
    }

    /**
     * Get thumbnail URL if available.
     */
    public function getThumbnailUrl(): ?string
    {
        if ($this->thumbnail_generated && $this->thumbnail_path) {
            return Storage::disk($this->storage_disk)->url($this->thumbnail_path);
        }

        return null;
    }

    /**
     * Get document icon based on type and extension.
     */
    public function getIcon(): string
    {
        if ($this->portal_icon) {
            return $this->portal_icon;
        }

        // Icon mapping by extension
        $iconMap = [
            'pdf' => 'file-pdf',
            'doc' => 'file-word',
            'docx' => 'file-word',
            'xls' => 'file-excel',
            'xlsx' => 'file-excel',
            'ppt' => 'file-powerpoint',
            'pptx' => 'file-powerpoint',
            'txt' => 'file-text',
            'csv' => 'file-spreadsheet',
            'zip' => 'file-archive',
            'rar' => 'file-archive',
            '7z' => 'file-archive',
            'jpg' => 'file-image',
            'jpeg' => 'file-image',
            'png' => 'file-image',
            'gif' => 'file-image',
            'mp4' => 'file-video',
            'avi' => 'file-video',
            'mov' => 'file-video',
            'mp3' => 'file-audio',
            'wav' => 'file-audio',
        ];

        return $iconMap[$this->extension] ?? 'file';
    }

    /**
     * Record view event.
     */
    public function recordView(): bool
    {
        $updates = [
            'view_count' => $this->view_count + 1,
            'last_viewed_at' => Carbon::now(),
        ];

        if (! $this->first_viewed_at) {
            $updates['first_viewed_at'] = Carbon::now();
        }

        return $this->update($updates);
    }

    /**
     * Record download event.
     */
    public function recordDownload(): bool
    {
        $updates = [
            'download_count' => $this->download_count + 1,
            'last_downloaded_at' => Carbon::now(),
        ];

        if (! $this->first_downloaded_at) {
            $updates['first_downloaded_at'] = Carbon::now();
        }

        return $this->update($updates);
    }

    /**
     * Record print event.
     */
    public function recordPrint(): bool
    {
        return $this->update([
            'print_count' => $this->print_count + 1,
        ]);
    }

    /**
     * Record share event.
     */
    public function recordShare(): bool
    {
        return $this->update([
            'share_count' => $this->share_count + 1,
        ]);
    }

    /**
     * Approve the document.
     */
    public function approve(int $approvedBy, ?string $notes = null): bool
    {
        return $this->update([
            'approved_by' => $approvedBy,
            'approved_at' => Carbon::now(),
            'approval_notes' => $notes,
            'workflow_status' => 'approved',
        ]);
    }

    /**
     * Acknowledge the document.
     */
    public function acknowledge(string $method = 'manual'): bool
    {
        return $this->update([
            'acknowledged_at' => Carbon::now(),
            'acknowledgment_method' => $method,
        ]);
    }

    /**
     * Archive the document.
     */
    public function archive(): bool
    {
        return $this->update([
            'status' => self::STATUS_ARCHIVED,
            'archived_at' => Carbon::now(),
            'show_in_portal' => false,
        ]);
    }

    /**
     * Create new version of the document.
     */
    public function createNewVersion(array $data): self
    {
        // Mark current version as superseded
        $this->update([
            'is_current_version' => false,
            'superseded_at' => Carbon::now(),
        ]);

        // Create new version
        $newVersion = new self(array_merge($data, [
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'parent_document_id' => $this->parent_document_id ?: $this->id,
            'version' => $this->version + 1,
            'is_current_version' => true,
        ]));

        $newVersion->save();

        return $newVersion;
    }

    /**
     * Generate file hash for integrity checking.
     */
    public function generateFileHash(): string
    {
        if (Storage::disk($this->storage_disk)->exists($this->storage_path)) {
            $fileContent = Storage::disk($this->storage_disk)->get($this->storage_path);

            return hash('sha256', $fileContent);
        }

        return '';
    }

    /**
     * Verify file integrity.
     */
    public function verifyIntegrity(): bool
    {
        $currentHash = $this->generateFileHash();

        return $currentHash === $this->file_hash;
    }

    /**
     * Check if file exists in storage.
     */
    public function fileExists(): bool
    {
        return Storage::disk($this->storage_disk)->exists($this->storage_path);
    }

    /**
     * Get file content.
     */
    public function getFileContent(): ?string
    {
        if ($this->fileExists()) {
            return Storage::disk($this->storage_disk)->get($this->storage_path);
        }

        return null;
    }

    /**
     * Delete file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            Storage::disk($this->storage_disk)->delete($this->storage_path);
        }

        // Delete thumbnail if exists
        if ($this->thumbnail_path && Storage::disk($this->storage_disk)->exists($this->thumbnail_path)) {
            Storage::disk($this->storage_disk)->delete($this->thumbnail_path);
        }

        return true;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    /**
     * Scope to get active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', Carbon::now());
            });
    }

    /**
     * Scope to get portal visible documents.
     */
    public function scopePortalVisible($query)
    {
        return $query->where('show_in_portal', true)->active();
    }

    /**
     * Scope to get current versions only.
     */
    public function scopeCurrentVersions($query)
    {
        return $query->where('is_current_version', true);
    }

    /**
     * Scope to get documents by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get documents by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get approved documents.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Scope to get documents requiring approval.
     */
    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true)
            ->whereNull('approved_at');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (! $document->version) {
                $document->version = 1;
            }

            if ($document->is_current_version === null) {
                $document->is_current_version = true;
            }

            if (! $document->file_hash && $document->fileExists()) {
                $document->file_hash = $document->generateFileHash();
            }
        });

        static::deleting(function ($document) {
            if ($document->forceDeleting) {
                $document->deleteFile();
            }
        });
    }
}
