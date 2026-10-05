<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasStatus
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), 'ACTIVE');
    }

    public function scopeWithStatus(Builder $query, string|array $status): Builder
    {
        return is_array($status)
            ? $query->whereIn($this->qualifyColumn('status'), $status)
            : $query->where($this->qualifyColumn('status'), $status);
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }
}