<?php

namespace App\Models;

use App\Domains\Client\Models\Client;

use App\Domains\Ticket\Models\Ticket;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRating extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'client_id',
        'company_id',
        'rating',
        'feedback',
        'rating_type',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the ticket that owns the rating.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who created the rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client associated with the rating.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope to filter by rating type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rating_type', $type);
    }

    /**
     * Scope to filter by minimum rating.
     */
    public function scopeMinRating($query, int $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    /**
     * Scope to filter by maximum rating.
     */
    public function scopeMaxRating($query, int $rating)
    {
        return $query->where('rating', '<=', $rating);
    }
}
