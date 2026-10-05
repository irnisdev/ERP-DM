<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Multi-branch access scope.
 *
 * PRD 01 §2.4 dan PRD 02 §6: data operasional harus memiliki branch_id langsung
 * atau melalui entity pemiliknya, dan akses antar cabang harus melewati
 * authorization scope.
 *
 * PendingBranchScope::apply() dipanggil oleh middleware/ViewServiceProvider agar
 * seluruh query otomatis dibatasi ke branch milik user aktif.
 */
trait HasBranch
{
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Batasi query ke daftar branch yang diizinkan.
     *
     * @param  array<int>  $branchIds
     */
    public function scopeInBranches(Builder $query, array $branchIds): Builder
    {
        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($this->qualifyColumn('branch_id'), $branchIds);
    }
}