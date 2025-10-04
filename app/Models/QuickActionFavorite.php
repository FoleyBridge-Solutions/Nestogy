<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuickActionFavorite extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'custom_quick_action_id',
        'system_action',
        'position',
    ];

    /**
     * Get the user that owns the favorite
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the custom quick action
     */
    public function customQuickAction()
    {
        return $this->belongsTo(CustomQuickAction::class);
    }
}
