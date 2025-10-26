<?php

namespace App\Domains\Ticket\Models;
use App\Domains\Core\Models\User;

use App\Domains\Client\Models\Client;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'ticket_id',
        'project_id',
        'client_id',
        'hours',
        'billable',
        'rate',
        'description',
        'date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'billable' => 'boolean',
        'hours' => 'decimal:2',
        'rate' => 'decimal:2',
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(\App\Domains\Ticket\Models\Ticket::class);
    }

    public function project()
    {
        return $this->belongsTo(\App\Domains\Project\Models\Project::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getAmountAttribute()
    {
        return $this->hours * ($this->rate ?? 75);
    }
}
