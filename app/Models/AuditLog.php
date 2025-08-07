<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'event_type',
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'session_id',
        'request_method',
        'request_url',
        'request_headers',
        'request_body',
        'response_status',
        'execution_time',
        'severity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'request_headers' => 'array',
        'request_body' => 'array',
        'execution_time' => 'decimal:3',
    ];

    /**
     * Event types constants
     */
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';
    const EVENT_SECURITY = 'security';
    const EVENT_API = 'api';
    const EVENT_ACCESS = 'access';
    const EVENT_ERROR = 'error';

    /**
     * Severity levels
     */
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company associated with the log.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable()
    {
        if ($this->model_type && $this->model_id) {
            return $this->model_type::find($this->model_id);
        }
        return null;
    }

    /**
     * Log a security event.
     */
    public static function logSecurity(string $action, array $metadata = [], string $severity = self::SEVERITY_WARNING): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'company_id' => session('company_id'),
            'event_type' => self::EVENT_SECURITY,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'severity' => $severity,
        ]);
    }

    /**
     * Log an API event.
     */
    public static function logApi(string $action, array $metadata = [], ?int $responseStatus = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'company_id' => session('company_id'),
            'event_type' => self::EVENT_API,
            'action' => $action,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'response_status' => $responseStatus,
            'severity' => self::SEVERITY_INFO,
        ]);
    }

    /**
     * Log a model event.
     */
    public static function logModel(Model $model, string $eventType, array $oldValues = [], array $newValues = []): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'company_id' => session('company_id'),
            'event_type' => $eventType,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'action' => $eventType . ' ' . class_basename($model),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'severity' => self::SEVERITY_INFO,
        ]);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to filter by severity.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}