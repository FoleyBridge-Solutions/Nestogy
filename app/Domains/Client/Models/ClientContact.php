<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientContact extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'name',
        'title',
        'email',
        'phone',
        'extension',
        'mobile',
        'photo',
        'pin',
        'notes',
        'auth_method',
        'password_hash',
        'password_reset_token',
        'token_expire',
        'primary',
        'important',
        'billing',
        'technical',
        'department',
        'accessed_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'client_id' => 'integer',
        'primary' => 'boolean',
        'important' => 'boolean',
        'billing' => 'boolean',
        'technical' => 'boolean',
        'token_expire' => 'datetime',
        'accessed_at' => 'datetime',
    ];

    protected $dates = [
        'token_expire',
        'accessed_at',
        'deleted_at',
    ];

    protected $hidden = [
        'password_hash',
        'password_reset_token',
        'pin',
    ];

    /**
     * Get the client that owns the contact.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the addresses associated with this contact.
     */
    public function addresses()
    {
        return $this->hasMany(ClientAddress::class, 'contact_id');
    }

    /**
     * Scope a query to only include primary contacts.
     */
    public function scopePrimary($query)
    {
        return $query->where('primary', true);
    }

    /**
     * Scope a query to only include billing contacts.
     */
    public function scopeBilling($query)
    {
        return $query->where('billing', true);
    }

    /**
     * Scope a query to only include technical contacts.
     */
    public function scopeTechnical($query)
    {
        return $query->where('technical', true);
    }

    /**
     * Scope a query to only include important contacts.
     */
    public function scopeImportant($query)
    {
        return $query->where('important', true);
    }

    /**
     * Get the contact's full name with title.
     */
    public function getFullNameAttribute()
    {
        return $this->title ? "{$this->name} ({$this->title})" : $this->name;
    }

    /**
     * Get the contact's display phone number.
     */
    public function getDisplayPhoneAttribute()
    {
        if ($this->phone && $this->extension) {
            return "{$this->phone} ext. {$this->extension}";
        }
        
        return $this->phone ?: $this->mobile;
    }

    /**
     * Check if the contact has authentication credentials.
     */
    public function hasAuth()
    {
        return !empty($this->password_hash) || !empty($this->pin);
    }

    /**
     * Get contact type labels.
     */
    public function getTypeLabelsAttribute()
    {
        $types = [];
        
        if ($this->primary) $types[] = 'Primary';
        if ($this->billing) $types[] = 'Billing';
        if ($this->technical) $types[] = 'Technical';
        if ($this->important) $types[] = 'Important';
        
        return $types;
    }
}