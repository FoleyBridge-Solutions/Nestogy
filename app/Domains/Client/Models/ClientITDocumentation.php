<?php

namespace App\Domains\Client\Models;

use App\Models\Client;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ClientITDocumentation extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $table = 'client_it_documentation';

    protected $fillable = [
        'company_id',
        'client_id',
        'authored_by',
        'name',
        'description',
        'it_category',
        'system_references',
        'ip_addresses',
        'software_versions',
        'compliance_requirements',
        'review_schedule',
        'last_reviewed_at',
        'next_review_at',
        'access_level',
        'procedure_steps',
        'network_diagram',
        'related_entities',
        'tags',
        'version',
        'parent_document_id',
        'file_path',
        'original_filename',
        'filename',
        'file_size',
        'mime_type',
        'file_hash',
        'last_accessed_at',
        'access_count',
        'is_active',
        'enabled_tabs',
        'tab_configuration',
        'status',
        'effective_date',
        'expiry_date',
        'template_used',
        'ports',
        'api_endpoints',
        'ssl_certificates',
        'dns_entries',
        'firewall_rules',
        'vpn_settings',
        'hardware_references',
        'environment_variables',
        'procedure_diagram',
        'rollback_procedures',
        'prerequisites',
        'data_classification',
        'encryption_required',
        'audit_requirements',
        'security_controls',
        'external_resources',
        'vendor_contacts',
        'support_contracts',
        'test_cases',
        'validation_checklist',
        'performance_benchmarks',
        'health_checks',
        'automation_scripts',
        'integrations',
        'webhooks',
        'scheduled_tasks',
        'uptime_requirement',
        'rto',
        'rpo',
        'performance_metrics',
        'alert_thresholds',
        'escalation_paths',
        'change_summary',
        'change_log',
        'requires_technical_review',
        'requires_management_approval',
        'approval_history',
        'review_comments',
        'custom_fields',
        'documentation_completeness',
        'is_template',
        'template_category',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'authored_by' => 'integer',
        'system_references' => 'array',
        'ip_addresses' => 'array',
        'software_versions' => 'array',
        'compliance_requirements' => 'array',
        'procedure_steps' => 'array',
        'network_diagram' => 'array',
        'related_entities' => 'array',
        'tags' => 'array',
        'parent_document_id' => 'integer',
        'file_size' => 'integer',
        'access_count' => 'integer',
        'is_active' => 'boolean',
        'last_reviewed_at' => 'datetime',
        'next_review_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'enabled_tabs' => 'array',
        'tab_configuration' => 'array',
        'ports' => 'array',
        'api_endpoints' => 'array',
        'ssl_certificates' => 'array',
        'dns_entries' => 'array',
        'firewall_rules' => 'array',
        'vpn_settings' => 'array',
        'hardware_references' => 'array',
        'environment_variables' => 'array',
        'procedure_diagram' => 'array',
        'rollback_procedures' => 'array',
        'prerequisites' => 'array',
        'audit_requirements' => 'array',
        'security_controls' => 'array',
        'external_resources' => 'array',
        'vendor_contacts' => 'array',
        'support_contracts' => 'array',
        'test_cases' => 'array',
        'validation_checklist' => 'array',
        'performance_benchmarks' => 'array',
        'health_checks' => 'array',
        'automation_scripts' => 'array',
        'integrations' => 'array',
        'webhooks' => 'array',
        'scheduled_tasks' => 'array',
        'performance_metrics' => 'array',
        'alert_thresholds' => 'array',
        'escalation_paths' => 'array',
        'approval_history' => 'array',
        'review_comments' => 'array',
        'custom_fields' => 'array',
        'documentation_completeness' => 'integer',
        'is_template' => 'boolean',
        'encryption_required' => 'boolean',
        'requires_technical_review' => 'boolean',
        'requires_management_approval' => 'boolean',
        'uptime_requirement' => 'decimal:2',
    ];

    /**
     * Get the client that owns the IT documentation.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who authored the documentation.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'authored_by');
    }

    /**
     * Get the parent document (for versioning).
     */
    public function parentDocument()
    {
        return $this->belongsTo(self::class, 'parent_document_id');
    }

    /**
     * Get all versions of this document.
     */
    public function versions()
    {
        return $this->hasMany(self::class, 'parent_document_id')->orderBy('version');
    }

    /**
     * Get the latest version of this document.
     */
    public function latestVersion()
    {
        if ($this->parent_document_id) {
            return $this->parentDocument->versions()->latest('version')->first();
        }

        return $this->versions()->latest('version')->first() ?: $this;
    }

    /**
     * Scope a query to only include documents of a specific category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('it_category', $category);
    }

    /**
     * Scope a query to only include documents with specific access level.
     */
    public function scopeAccessLevel($query, $level)
    {
        return $query->where('access_level', $level);
    }

    /**
     * Scope a query to only include active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include documents needing review.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('next_review_at', '<=', now())
            ->orWhereNull('next_review_at');
    }

    /**
     * Scope a query to search documents.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereJsonContains('tags', $search)
                ->orWhere('it_category', 'like', "%{$search}%");
        });
    }

    /**
     * Check if the document needs review.
     */
    public function needsReview()
    {
        return $this->next_review_at && $this->next_review_at->isPast();
    }

    /**
     * Check if the document has an attached file.
     */
    public function hasFile()
    {
        return ! empty($this->file_path);
    }

    /**
     * Check if the file exists in storage.
     */
    public function fileExists()
    {
        return $this->hasFile() && Storage::exists($this->file_path);
    }

    /**
     * Get the document's file size in human readable format.
     */
    public function getFileSizeHumanAttribute()
    {
        if (! $this->file_size) {
            return null;
        }

        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2).' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes.' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes.' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * Get the document's file extension.
     */
    public function getFileExtensionAttribute()
    {
        return $this->original_filename ? pathinfo($this->original_filename, PATHINFO_EXTENSION) : null;
    }

    /**
     * Get the document's icon based on category.
     */
    public function getCategoryIconAttribute()
    {
        $icons = [
            'runbook' => '📋',
            'troubleshooting' => '🔧',
            'architecture' => '🏗️',
            'backup_recovery' => '💾',
            'monitoring' => '📊',
            'change_management' => '🔄',
            'business_continuity' => '🛡️',
            'user_guide' => '📖',
            'compliance' => '✅',
            'vendor_procedure' => '🏢',
        ];

        return $icons[$this->it_category] ?? '📄';
    }

    /**
     * Get the access level badge color.
     */
    public function getAccessLevelColorAttribute()
    {
        $colors = [
            'public' => 'green',
            'confidential' => 'yellow',
            'restricted' => 'red',
            'admin_only' => 'purple',
        ];

        return $colors[$this->access_level] ?? 'gray';
    }

    /**
     * Get the review status color.
     */
    public function getReviewStatusColorAttribute()
    {
        if (! $this->next_review_at) {
            return 'gray';
        }

        if ($this->next_review_at->isPast()) {
            return 'red';
        }

        if ($this->next_review_at->diffInDays() <= 30) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * Get the download URL for attached file.
     */
    public function getDownloadUrlAttribute()
    {
        return $this->hasFile() ? route('clients.it-documentation.download', $this) : null;
    }

    /**
     * Delete the physical file from storage.
     */
    public function deleteFile()
    {
        if ($this->fileExists()) {
            Storage::delete($this->file_path);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            $document->deleteFile();
        });
    }

    /**
     * Get available IT documentation categories.
     */
    public static function getITCategories()
    {
        return [
            'runbook' => 'Runbooks & Procedures',
            'troubleshooting' => 'Troubleshooting Guides',
            'architecture' => 'System Architecture',
            'backup_recovery' => 'Backup & Recovery Plans',
            'monitoring' => 'Monitoring Documentation',
            'change_management' => 'Change Management',
            'business_continuity' => 'Business Continuity',
            'user_guide' => 'User Training Guides',
            'compliance' => 'Compliance Documentation',
            'vendor_procedure' => 'Vendor Procedures',
        ];
    }

    /**
     * Get available access levels.
     */
    public static function getAccessLevels()
    {
        return [
            'public' => 'Public',
            'confidential' => 'Confidential',
            'restricted' => 'Restricted',
            'admin_only' => 'Admin Only',
        ];
    }

    /**
     * Get available review schedules.
     */
    public static function getReviewSchedules()
    {
        return [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'annually' => 'Annually',
            'as_needed' => 'As Needed',
        ];
    }
}
