<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * PRD 01 §28: roles & permissions. Nama permission memakai modul.submenu.action
 * agar mudah dipetakan ke policy Laravel.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, array<string, string[]>>
     */
    private const ROLES = [
        'OWNER' => [
            'description' => 'Pemilik perusahaan, akses penuh lintas cabang',
            'permissions' => ['*'],
        ],
        'SUPER_ADMIN' => [
            'description' => 'Administrator sistem, akses penuh lintas cabang',
            'permissions' => ['*'],
        ],
        'BRANCH_MANAGER' => [
            'description' => 'Manager cabang, akses data cabang sendiri',
            'permissions' => [
                'dashboard.view',
                'organization.view',
                'employee.view', 'employee.manage', 'employee.salary.view',
                'customer.view', 'customer.manage',
                'product.view', 'product.manage',
                'service.view', 'service.manage',
                'package.view', 'package.manage',
                'booking.view', 'booking.manage',
                'sales.view', 'sales.create', 'sales.return',
                'commission.view',
                'inventory.view', 'inventory.transfer', 'inventory.opname', 'inventory.adjust',
                'purchasing.view', 'purchasing.manage', 'purchasing.approve',
                'finance.view', 'finance.expense.manage', 'finance.bill.manage',
                'finance.payment.manage', 'finance.reconciliation',
                'payroll.view', 'payroll.approve', 'payroll.adjust',
                'payslip.view', 'payslip.manage', 'payslip.publish',
                'asset.view', 'asset.manage', 'document.view', 'document.manage',
                'report.sales', 'report.inventory', 'report.finance', 'report.payroll', 'report.hr',
                'approval.view', 'approval.review',
                'audit.view',
            ],
        ],
        'ADMIN' => [
            'description' => 'Admin operasional harian',
            'permissions' => [
                'dashboard.view',
                'organization.view',
                'customer.view', 'customer.manage',
                'product.view', 'product.manage',
                'service.view', 'service.manage',
                'package.view', 'package.manage',
                'booking.view', 'booking.manage',
                'sales.view', 'sales.create', 'sales.return',
                'inventory.view', 'inventory.transfer', 'inventory.opname', 'inventory.adjust',
                'purchasing.view', 'purchasing.manage',
                'finance.view', 'finance.expense.manage', 'finance.bill.manage',
                'asset.view', 'asset.manage', 'document.view', 'document.manage',
                'report.sales', 'report.inventory',
                'audit.view',
            ],
        ],
        'CASHIER' => [
            'description' => 'Kasir POS',
            'permissions' => [
                'dashboard.view',
                'customer.view', 'customer.manage',
                'product.view', 'service.view', 'package.view',
                'booking.view', 'booking.manage',
                'sales.view', 'sales.create', 'sales.return',
                'commission.view',
            ],
        ],
        'THERAPIST' => [
            'description' => 'Terapis treatment',
            'permissions' => [
                'dashboard.view',
                'customer.view',
                'booking.view', 'booking.manage',
                'service.view',
                'commission.view',
                'payslip.view',
            ],
        ],
        'ACCOUNTING' => [
            'description' => 'Staff finance & akunting',
            'permissions' => [
                'dashboard.view', 'dashboard.finance',
                'organization.view',
                'purchasing.view', 'purchasing.manage',
                'finance.view', 'finance.expense.manage', 'finance.bill.manage',
                'finance.payment.manage', 'finance.reconciliation',
                'report.sales', 'report.inventory', 'report.finance',
                'audit.view',
            ],
        ],
        'HR_STAFF' => [
            'description' => 'Staff HR dan payroll',
            'permissions' => [
                'dashboard.view',
                'employee.view', 'employee.manage', 'employee.salary.view',
                'payroll.view', 'payroll.adjust', 'payslip.view', 'payslip.manage',
                'report.hr',
                'document.view', 'document.manage',
            ],
        ],
    ];

    /**
     * Permission master per domain. Nama unik, didesain untuk Gate::allows().
     */
    private const PERMISSION_LIST = [
        'dashboard.view', 'dashboard.finance',

        'organization.view', 'organization.manage',

        'employee.view', 'employee.manage', 'employee.salary.view',

        'customer.view', 'customer.manage',
        'product.view', 'product.manage',
        'service.view', 'service.manage',
        'package.view', 'package.manage',
        'supplier.view', 'supplier.manage',

        'booking.view', 'booking.manage',
        'sales.view', 'sales.create', 'sales.return',
        'commission.view',
        'payment.view', 'payment.manage',

        'inventory.view', 'inventory.transfer', 'inventory.opname', 'inventory.adjust',
        'inventory.negative_stock',
        'purchasing.view', 'purchasing.manage', 'purchasing.approve',

        'finance.view', 'finance.expense.manage', 'finance.bill.manage',
        'finance.payment.manage', 'finance.reconciliation',

        'payroll.view', 'payroll.manage', 'payroll.adjust', 'payroll.approve',
        'payslip.view', 'payslip.manage', 'payslip.publish',

        'asset.view', 'asset.manage',
        'document.view', 'document.manage',
        'digital_account.view', 'digital_account.manage',

        'report.sales', 'report.inventory', 'report.finance', 'report.payroll', 'report.hr',

        'approval.view', 'approval.review',
        'audit.view',

        // Sensitive field access (PRD 02 §28)
        'sensitive.nik', 'sensitive.bpjs', 'sensitive.rekening',
        'sensitive.payroll', 'sensitive.digital_credential',
    ];

    public function run(): void
    {
        $permissions = [];

        foreach (self::PERMISSION_LIST as $name) {
            $permissions[$name] = Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $name]
            );
        }

        foreach (self::ROLES as $roleName => $config) {
            $role = Role::updateOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['description' => $config['description']]
            );

            if ($config['permissions'] === ['*']) {
                $role->permissions()->sync(collect($permissions)->pluck('id'));
                continue;
            }

            $role->permissions()->sync(
                collect($config['permissions'])
                    ->filter(fn (string $name) => isset($permissions[$name]))
                    ->map(fn (string $name) => $permissions[$name]->id)
            );
        }
    }
}