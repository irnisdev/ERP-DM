<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Branch access scope (PRD 02 §6).
 *
 * Owner/Super Admin melihat semua cabang. Branch user hanya melihat branch
 * yang diizinkan. Halaman listing wajib melewati helper ini.
 */
class BranchScope
{
    /**
     * Branch id yang boleh diakses user aktif. Null = semua cabang.
     *
     * @return array<int>|null
     */
    public function allowed(User $user): ?array
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->accessibleBranchIds() ?? [];
    }

    public function canAccess(User $user, ?int $branchId): bool
    {
        $allowed = $this->allowed($user);

        if ($allowed === null) {
            return true;
        }

        return $branchId !== null && in_array($branchId, $allowed, true);
    }

    public function apply(User $user, Builder $query, string $column = 'branch_id'): Builder
    {
        $allowed = $this->allowed($user);

        if ($allowed === null) {
            return $query;
        }

        if ($allowed === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $allowed);
    }

    /**
     * Branch milik user aktif, atau null bila user boleh melihat semua.
     */
    public function currentBranchId(User $user): ?int
    {
        return $user->employee?->branch_id;
    }

    public function describe(User $user): string
    {
        $branch = $user->employee?->branch;

        return $branch?->name ?? ($this->allowed($user) === null ? 'Semua cabang' : 'Tanpa cabang');
    }
}