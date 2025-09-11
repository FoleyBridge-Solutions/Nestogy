<?php

namespace App\Domains\Email\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_account_id',
        'name',
        'content_html',
        'content_text',
        'is_default',
        'auto_append_replies',
        'auto_append_forwards',
        'conditions',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'auto_append_replies' => 'boolean',
        'auto_append_forwards' => 'boolean',
        'conditions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    // Helper methods
    public function processVariables(array $variables = []): array
    {
        $defaultVariables = [
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'user_phone' => $this->user->phone ?? '',
            'user_title' => $this->user->title ?? '',
            'company_name' => config('app.name'),
            'current_date' => now()->format('F j, Y'),
            'current_time' => now()->format('g:i A'),
        ];

        $allVariables = array_merge($defaultVariables, $variables);

        $processedHtml = $this->content_html;
        $processedText = $this->content_text;

        foreach ($allVariables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $processedHtml = str_replace($placeholder, $value, $processedHtml);
            $processedText = str_replace($placeholder, $value, $processedText);
        }

        return [
            'html' => $processedHtml,
            'text' => $processedText,
        ];
    }

    public function shouldAppendToReply(): bool
    {
        return $this->auto_append_replies;
    }

    public function shouldAppendToForward(): bool
    {
        return $this->auto_append_forwards;
    }

    public function matchesConditions(array $context = []): bool
    {
        if (!$this->conditions || empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            $contextValue = $context[$field] ?? null;

            if (!$this->evaluateCondition($contextValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    private function evaluateCondition($contextValue, string $operator, $expectedValue): bool
    {
        return match ($operator) {
            'equals' => $contextValue === $expectedValue,
            'not_equals' => $contextValue !== $expectedValue,
            'contains' => str_contains((string) $contextValue, (string) $expectedValue),
            'not_contains' => !str_contains((string) $contextValue, (string) $expectedValue),
            'starts_with' => str_starts_with((string) $contextValue, (string) $expectedValue),
            'ends_with' => str_ends_with((string) $contextValue, (string) $expectedValue),
            'in' => in_array($contextValue, (array) $expectedValue),
            'not_in' => !in_array($contextValue, (array) $expectedValue),
            default => true
        };
    }

    public function getPreview(int $length = 100): string
    {
        $text = strip_tags($this->content_html ?: $this->content_text);
        return \Illuminate\Support\Str::limit($text, $length);
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAccount($query, int $emailAccountId)
    {
        return $query->where('email_account_id', $emailAccountId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('email_account_id');
    }

    public function scopeAccountSpecific($query)
    {
        return $query->whereNotNull('email_account_id');
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($signature) {
            // Ensure only one default signature per user/account combination
            if ($signature->is_default) {
                static::where('user_id', $signature->user_id)
                    ->where('email_account_id', $signature->email_account_id)
                    ->where('id', '!=', $signature->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }
}