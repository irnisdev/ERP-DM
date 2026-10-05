<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::where('code', 'PUSAT')->firstOrFail();
        $department = Department::where('code', 'GA')->firstOrFail();
        $position = Position::where('code', 'OWNER')->firstOrFail();

        $employee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-0001'],
            [
                'full_name' => 'Administrator Sistem',
                'gender' => 'M',
                'phone' => '08123456789',
                'email' => 'admin@dianmustika.co.id',
                'address' => 'Jakarta',
                'marital_status' => 'MARRIED',
                'employment_status' => 'ACTIVE',
                'join_date' => now()->subYears(3)->toDateString(),
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'status' => 'ACTIVE',
            ]
        );

        $user = User::updateOrCreate(
            ['email' => 'admin@dianmustika.co.id'],
            [
                'employee_id' => $employee->id,
                'name' => 'Administrator Sistem',
                'password' => Hash::make('password'),
                'status' => 'ACTIVE',
            ]
        );

        $user->roles()->sync([
            Role::where('name', 'SUPER_ADMIN')->value('id'),
            Role::where('name', 'OWNER')->value('id'),
        ]);

        // Cashier default untuk uji POS
        $cashierDept = Department::where('code', 'SALES')->firstOrFail();
        $cashierPos = Position::where('code', 'CASHIER')->firstOrFail();

        $cashierEmployee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-0002'],
            [
                'full_name' => 'Kasir Pusat',
                'gender' => 'F',
                'phone' => '08129876543',
                'email' => 'kasir@dianmustika.co.id',
                'employment_status' => 'ACTIVE',
                'join_date' => now()->subYear()->toDateString(),
                'branch_id' => $branch->id,
                'department_id' => $cashierDept->id,
                'position_id' => $cashierPos->id,
                'status' => 'ACTIVE',
            ]
        );

        $cashier = User::updateOrCreate(
            ['email' => 'kasir@dianmustika.co.id'],
            [
                'employee_id' => $cashierEmployee->id,
                'name' => 'Kasir Pusat',
                'password' => Hash::make('password'),
                'status' => 'ACTIVE',
            ]
        );

        $cashier->roles()->sync([Role::where('name', 'CASHIER')->value('id')]);
    }
}