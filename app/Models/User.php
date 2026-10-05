<?php

namespace App\Models;

use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use HasStatus;
    use Notifiable;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Permission milik user, dihitung lewat role yang dipegang.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->permissionNames()->contains($permission);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function permissionNames()
    {
        return Permission::query()
            ->whereIn('id', Permission::query()
                ->join('permission_role', 'permission_role.permission_id', '=', 'permissions.id')
                ->join('role_user', 'role_user.role_id', '=', 'permission_role.role_id')
                ->where('role_user.user_id', $this->getKey())
                ->select('permissions.id'))
            ->pluck('name');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function isSuperAdmin(): bool
    {
        if (! $this->exists) {
            return false;
        }

        return $this->roles()->whereIn('name', ['SUPER_ADMIN', 'OWNER'])->exists();
    }

    /**
     * Branch yang boleh diakses user.
     *
     * Super admin/owner: null = semua branch.
     * User lain: daftar branch_id hasil assignment.
     */
    public function accessibleBranchIds(): ?array
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        return $this->employee?->branch_id ? [$this->employee->branch_id] : [];
    }

    public function createdSalesTransactions(): HasMany
    {
        return $this->hasMany(SalesTransaction::class, 'created_by');
    }
}