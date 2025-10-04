<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;
    protected $table = 'expense_categories';
    
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'color',
        'sort_order',
        'is_active',
        'requires_approval',
        'approval_threshold',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
