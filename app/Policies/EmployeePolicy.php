<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        // User cabang hanya boleh melihat employee di cabangnya sendiri.
        return $user->can('employee.view') && $this->sameBranch($user, $employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employee.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employee.manage') && $this->sameBranch($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        // PRD 02 §7 — employee dengan transaksi historis tidak dihapus.
        // Penghapusan selalu soft delete.
        return $user->can('employee.manage')
            && $this->sameBranch($user, $employee)
            && ! $this->hasTransactions($employee);
    }

    public function viewSalary(User $user, Employee $employee): bool
    {
        // Sensitive field (PRD 02 §28)
        return $user->can('employee.salary.view') && $this->sameBranch($user, $employee);
    }

    public function manageFamily(User $user, Employee $employee): bool
    {
        return $user->can('employee.manage') && $this->sameBranch($user, $employee);
    }

    public function manageInsurance(User $user, Employee $employee): bool
    {
        // BPJS termasuk data sensitif
        return $user->can('employee.manage')
            && $user->hasPermission('sensitive.bpjs')
            && $this->sameBranch($user, $employee);
    }

    private function sameBranch(User $user, Employee $employee): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->employee !== null
            && $user->employee->branch_id === $employee->branch_id;
    }

    private function hasTransactions(Employee $employee): bool
    {
        return $employee->payrolls()->exists()
            || $employee->commissions()->exists()
            || $employee->attendances()->exists();
    }
}