<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait HasActivity
{
    protected static function bootHasActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            if ($model->wasChanged() && !$model->wasRecentlyCreated) {
                $model->logActivity('updated', $model->getChanges());
            }
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity(string $action, array $properties = []): void
    {
        $data = [
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'model_name' => $this->getActivityName(),
            'user_id' => Auth::id(),
            'company_id' => $this->company_id ?? Auth::user()?->company_id,
            'properties' => $properties,
            'timestamp' => now()->toISOString()
        ];

        Log::info("Model activity: {$action}", $data);

        // You can also store this in a dedicated activity table if needed
        // activity()->performedOn($this)->withProperties($properties)->log($action);
    }

    protected function getActivityName(): string
    {
        return $this->name ?? $this->title ?? class_basename($this) . ' #' . $this->id;
    }

    public function markAsAccessed(): bool
    {
        if (in_array('accessed_at', $this->getFillable())) {
            return $this->update(['accessed_at' => now()]);
        }

        return true;
    }

    public function getLastAccessedAttribute()
    {
        return $this->accessed_at;
    }

    public function wasAccessedToday(): bool
    {
        return $this->accessed_at && $this->accessed_at->isToday();
    }

    public function wasAccessedThisWeek(): bool
    {
        return $this->accessed_at && $this->accessed_at->isCurrentWeek();
    }
}