<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSearch
{
    protected array $searchableFields = ['name'];

    public function scopeSearch(Builder $query, string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            foreach ($this->getSearchableFields() as $field) {
                if (str_contains($field, '.')) {
                    // Handle relationship searches
                    $this->addRelationshipSearch($q, $field, $term);
                } else {
                    // Handle direct field searches
                    $q->orWhere($field, 'like', "%{$term}%");
                }
            }
        });
    }

    protected function addRelationshipSearch(Builder $query, string $field, string $term): void
    {
        [$relation, $column] = explode('.', $field, 2);

        $query->orWhereHas($relation, function (Builder $q) use ($column, $term) {
            $q->where($column, 'like', "%{$term}%");
        });
    }

    protected function getSearchableFields(): array
    {
        return property_exists($this, 'searchableFields') ? $this->searchableFields : ['name'];
    }

    public function scopeSearchIn(Builder $query, array $fields, string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($fields, $term) {
            foreach ($fields as $field) {
                if (str_contains($field, '.')) {
                    $this->addRelationshipSearch($q, $field, $term);
                } else {
                    $q->orWhere($field, 'like', "%{$term}%");
                }
            }
        });
    }

    public function scopeFullTextSearch(Builder $query, string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $searchableFields = $this->getSearchableFields();
        $columns = implode(',', array_filter($searchableFields, function ($field) {
            return ! str_contains($field, '.');
        }));

        if (! empty($columns)) {
            return $query->whereRaw("MATCH({$columns}) AGAINST(? IN BOOLEAN MODE)", [$term]);
        }

        return $this->scopeSearch($query, $term);
    }
}
