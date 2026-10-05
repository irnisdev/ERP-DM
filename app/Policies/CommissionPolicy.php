<?php

namespace App\Policies;

use App\Models\EmployeeCommission;
use App\Models\User;

class CommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('commission.view');
    }

    public function view(User $user, EmployeeCommission $commission): bool
    {
        if ($user->can('commission.view') && $user->isSuperAdmin()) {
            return true;
        }

        // Therapist hanya melihat komisinya sendiri
        return $user->can('commission.view')
            && $user->employee !== null
            && $user->employee->id === $commission->employee_id;
    }

    /**
     * Komisi yang sudah masuk payroll tidak boleh diedit manual.
     */
    public function update(User $user, EmployeeCommission $commission): bool
    {
        return $user->can('payroll.manage')
            && $commission->status === EmployeeCommission::STATUS_PENDING;
    }
}
