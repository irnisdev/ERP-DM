<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master data wajib (PRD 01 §27 sequence 1-16).
 * Demo data dipisahkan di DatabaseDemoSeeder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            BranchSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            RolePermissionSeeder::class,
            BankSeeder::class,
            CashBankAccountSeeder::class,
            UnitSeeder::class,
            BrandSeeder::class,
            ProductCategorySeeder::class,
            ServiceCategorySeeder::class,
            ExpenseCategorySeeder::class,
            DigitalAccountCategorySeeder::class,
            SalaryComponentSeeder::class,
            PaymentMethodSeeder::class,
            ShiftSeeder::class,
            BillTemplateSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}