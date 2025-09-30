<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasArchive
{
    /**
     * Boot the HasArchive trait.
     */
    protected static function bootHasArchive()
    {
        // Add global scope to exclude archived records by default
        static::addGlobalScope('archived', function (Builder $builder) {
            $builder->whereNull($builder->getModel()->getTable().'.archived_at');
        });
    }

    /**
     * Archive the model.
     */
    public function archive()
    {
        $this->archived_at = now();
        $this->save();

        return $this;
    }

    /**
     * Restore the archived model.
     */
    public function restore()
    {
        $this->archived_at = null;
        $this->save();

        return $this;
    }

    /**
     * Determine if the model is archived.
     */
    public function isArchived()
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Scope a query to only include archived models.
     */
    public function scopeArchived($query)
    {
        return $query->withoutGlobalScope('archived')->whereNotNull('archived_at');
    }

    /**
     * Scope a query to include archived models.
     */
    public function scopeWithArchived($query)
    {
        return $query->withoutGlobalScope('archived');
    }

    /**
     * Scope a query to only include non-archived models.
     */
    public function scopeWithoutArchived($query)
    {
        return $query->whereNull('archived_at');
    }
}
