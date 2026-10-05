<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRD 01 §29 Index & Constraint Strategy — verifikasi schema terhadap PRD.
 */
class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function businessTableProvider(): array
    {
        $tables = [
            // §4 Organization
            'companies', 'branches', 'departments', 'positions',
            // §5 User, role, permission
            'users', 'roles', 'permissions', 'role_user', 'permission_role',
            // §6 Employee
            'employees', 'employee_family_members', 'employee_insurances',
            // §7 Customer
            'customers',
            // §8 Product
            'product_categories', 'brands', 'units', 'products', 'product_prices',
            // §9 Service
            'service_categories', 'services', 'service_prices',
            // §10 Package
            'service_packages', 'service_package_items',
            // §11 Supplier & purchasing
            'suppliers', 'supplier_bank_accounts', 'purchase_orders', 'purchase_order_items',
            'goods_receipts', 'goods_receipt_items', 'purchase_invoices', 'purchase_invoice_items',
            // §12 Inventory
            'warehouses', 'inventory_stocks', 'inventory_movements',
            'stock_transfers', 'stock_transfer_items', 'stock_opnames', 'stock_opname_items',
            // §13 Booking
            'bookings', 'booking_items',
            // §14 Sales
            'sales_transactions', 'sales_transaction_items', 'sales_returns', 'sales_return_items',
            // §15-16 Payment & bank
            'banks', 'financial_accounts', 'payment_methods', 'payments',
            'bank_transactions', 'bank_reconciliations',
            // §17-18 Bills & expense
            'bill_templates', 'bills', 'expense_categories', 'expenses',
            // §19-21 Payroll, commission, payslip
            'payroll_periods', 'salary_components', 'employee_salary_structures',
            'employee_salary_components', 'payrolls', 'payroll_items', 'payroll_adjustments',
            'commission_rules', 'employee_commissions',
            'payslip_templates', 'payslips', 'payslip_items',
            // §22 Attendance
            'shifts', 'employee_shifts', 'attendances', 'overtime_records',
            // §23-24 Asset, media, digital account
            'asset_categories', 'assets', 'asset_movements',
            'media_files', 'media_attachments',
            'digital_account_categories', 'digital_accounts',
            // §25 Approval & audit
            'approval_requests', 'audit_logs',
        ];

        return array_combine(
            $tables,
            array_map(fn (string $t) => [$t], $tables)
        );
    }

    #[Test]
    #[DataProvider('businessTableProvider')]
    public function table_exists(string $table): void
    {
        $this->assertTrue(Schema::hasTable($table), "Tabel {$table} tidak ada");
    }

    /** @return array<string, array{string, string}> */
    public static function uniqueConstraintProvider(): array
    {
        return [
            'users.email' => ['users', 'email'],
            'employees.employee_code' => ['employees', 'employee_code'],
            'customers.customer_code' => ['customers', 'customer_code'],
            'products.sku' => ['products', 'sku'],
            'products.barcode' => ['products', 'barcode'],
            'services.service_code' => ['services', 'service_code'],
            'service_packages.code' => ['service_packages', 'code'],
            'suppliers.supplier_code' => ['suppliers', 'supplier_code'],
            'branches.code per company' => ['branches', 'company_id,code'],
            'departments.code per company' => ['departments', 'company_id,code'],
            'inventory_stocks.warehouse+product' => ['inventory_stocks', 'warehouse_id,product_id'],
            'role_user.role+user' => ['role_user', 'role_id,user_id'],
            'permission_role.permission+role' => ['permission_role', 'permission_id,role_id'],
            'service_package_items.package+service' => ['service_package_items', 'package_id,service_id'],
            'attendances.employee+date' => ['attendances', 'employee_id,attendance_date'],
            'payrolls.period+employee' => ['payrolls', 'payroll_period_id,employee_id'],
        ];
    }

    #[Test]
    #[DataProvider('uniqueConstraintProvider')]
    public function unique_constraint_exists(string $table, string $columns): void
    {
        $indexes = collect(Schema::getIndexes($table))
            ->filter(fn (array $index) => $index['unique'])
            ->map(fn (array $index) => strtolower(implode(',', $index['columns'])))
            ->values();

        $this->assertTrue(
            $indexes->contains(strtolower($columns)),
            "Unique constraint ({$columns}) tidak ada di {$table}. Existing: ".$indexes->implode(' | ')
        );
    }

    /** @return array<string, array{string, string}> */
    public static function businessNumberProvider(): array
    {
        return [
            'purchase_orders.po_number' => ['purchase_orders', 'po_number'],
            'goods_receipts.receipt_number' => ['goods_receipts', 'receipt_number'],
            'purchase_invoices.invoice_number' => ['purchase_invoices', 'invoice_number'],
            'stock_transfers.transfer_number' => ['stock_transfers', 'transfer_number'],
            'stock_opnames.opname_number' => ['stock_opnames', 'opname_number'],
            'bookings.booking_number' => ['bookings', 'booking_number'],
            'sales_transactions.invoice_number' => ['sales_transactions', 'invoice_number'],
            'sales_returns.return_number' => ['sales_returns', 'return_number'],
            'payments.payment_number' => ['payments', 'payment_number'],
            'bills.bill_number' => ['bills', 'bill_number'],
            'expenses.expense_number' => ['expenses', 'expense_number'],
            'payroll_periods.code' => ['payroll_periods', 'code'],
            'payslips.payslip_number' => ['payslips', 'payslip_number'],
            'assets.asset_code' => ['assets', 'asset_code'],
        ];
    }

    #[Test]
    #[DataProvider('businessNumberProvider')]
    public function business_number_is_unique(string $table, string $column): void
    {
        $indexed = collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['unique'] && $index['columns'] === [$column]);

        $this->assertTrue($indexed, "Business number {$table}.{$column} harus unique");
    }

    /** @return array<string, array{string}> */
    public static function softDeleteTableProvider(): array
    {
        return array_map(
            fn (string $t) => [$t],
            ['employees', 'customers', 'products', 'services', 'service_packages', 'suppliers', 'assets']
        );
    }

    #[Test]
    #[DataProvider('softDeleteTableProvider')]
    public function master_table_uses_soft_delete(string $table): void
    {
        $this->assertTrue(
            Schema::hasColumn($table, 'deleted_at'),
            "Master {$table} harus soft delete"
        );
    }

    #[Test]
    public function transaction_tables_are_not_soft_deleted(): void
    {
        $transactions = [
            'sales_transactions', 'sales_transaction_items', 'sales_returns',
            'purchase_orders', 'goods_receipts', 'purchase_invoices',
            'payments', 'bank_transactions', 'bills', 'expenses',
            'payrolls', 'payslips', 'inventory_movements', 'bookings',
        ];

        foreach ($transactions as $table) {
            $this->assertFalse(
                Schema::hasColumn($table, 'deleted_at'),
                "Transaksi {$table} tidak boleh soft delete / hard delete"
            );
        }
    }

    /**
     * PRD 01 §3: Monetary amount = DECIMAL(20,2), tidak boleh FLOAT/DOUBLE.
     *
     * Daftar kolom uang eksplisit agar assertion tidak ikut menangkap kolom
     * non-uang (mis. tax_number, payment_date, *_id).
     *
     * @return array<string, array{string}>
     */
    public static function monetaryColumnProvider(): array
    {
        $columns = [
            'products' => ['purchase_price', 'default_sell_price'],
            'product_prices' => ['price'],
            'service_prices' => ['price'],
            'service_packages' => ['price'],
            'employee_insurances' => ['employee_contribution', 'company_contribution'],
            'financial_accounts' => ['opening_balance'],
            'purchase_orders' => ['subtotal', 'discount', 'tax', 'grand_total'],
            'purchase_order_items' => ['unit_price', 'discount', 'subtotal'],
            'goods_receipt_items' => ['unit_cost'],
            'purchase_invoices' => ['subtotal', 'discount', 'tax', 'grand_total'],
            'purchase_invoice_items' => ['unit_price', 'discount', 'subtotal'],
            'inventory_movements' => ['unit_cost'],
            'sales_transactions' => ['subtotal', 'discount', 'tax', 'grand_total'],
            'sales_transaction_items' => ['unit_price', 'discount', 'subtotal'],
            'sales_return_items' => ['amount'],
            'payments' => ['amount'],
            'bank_transactions' => ['amount', 'balance'],
            'bank_reconciliations' => ['matched_amount'],
            'bill_templates' => ['default_amount'],
            'bills' => ['amount', 'penalty_amount', 'total_amount'],
            'expenses' => ['amount'],
            'employee_salary_structures' => ['basic_salary'],
            'employee_salary_components' => ['amount'],
            'payrolls' => ['basic_salary', 'total_earnings', 'total_deductions', 'net_salary'],
            'payroll_items' => ['amount'],
            'payroll_adjustments' => ['amount'],
            'commission_rules' => ['commission_value'],
            'employee_commissions' => ['commission_amount'],
            'payslips' => ['gross_salary', 'total_deduction', 'net_salary'],
            'payslip_items' => ['amount'],
            'assets' => ['purchase_price'],
        ];

        $flat = [];
        foreach ($columns as $table => $names) {
            foreach ($names as $name) {
                $flat["{$table}.{$name}"] = [$table, $name];
            }
        }

        return $flat;
    }

    #[Test]
    #[DataProvider('monetaryColumnProvider')]
    public function monetary_column_is_decimal_20_2(string $table, string $column): void
    {
        $type = collect(Schema::getColumns($table))
            ->firstWhere('name', $column)['type'] ?? '';

        $this->assertSame(
            'decimal(20,2)',
            strtolower($type),
            "{$table}.{$column} harus DECIMAL(20,2), got ".strtolower($type)
        );
    }

    #[Test]
    public function no_float_or_double_columns_anywhere(): void
    {
        foreach ($this->allTables() as $table) {
            foreach (Schema::getColumns($table) as $column) {
                $type = strtolower($column['type'] ?? '');

                $this->assertStringNotContainsString('float', $type, "{$table}.{$column['name']} tidak boleh FLOAT");
                $this->assertStringNotContainsString('double', $type, "{$table}.{$column['name']} tidak boleh DOUBLE");
            }
        }
    }

    #[Test]
    public function quantity_columns_use_decimal_20_4(): void
    {
        $tables = $this->allTables();

        foreach ($tables as $table) {
            $columns = collect(Schema::getColumns($table))
                ->filter(fn (array $c) => preg_match('/^(quantity|min_weight|max_weight|weight|available)$/', $c['name']));

            foreach ($columns as $column) {
                $this->assertSame(
                    'decimal(20,4)',
                    strtolower($column['type']),
                    "{$table}.{$column['name']} harus DECIMAL(20,4)"
                );
            }
        }
    }

    /** @return array<string, array{string, string}> */
    public static function indexedForeignKeyProvider(): array
    {
        $pairs = [
            ['branches', 'company_id'],
            ['departments', 'company_id'],
            ['positions', 'department_id'],
            ['employees', 'branch_id'],
            ['employees', 'manager_id'],
            ['users', 'employee_id'],
            ['bookings', 'customer_id'],
            ['bookings', 'branch_id'],
            ['sales_transactions', 'branch_id'],
            ['sales_transactions', 'customer_id'],
            ['sales_transaction_items', 'sales_transaction_id'],
            ['sales_transaction_items', 'product_id'],
            ['sales_transaction_items', 'service_id'],
            ['purchase_orders', 'supplier_id'],
            ['purchase_orders', 'branch_id'],
            ['goods_receipts', 'supplier_id'],
            ['inventory_stocks', 'warehouse_id'],
            ['inventory_stocks', 'product_id'],
            ['inventory_movements', 'warehouse_id'],
            ['inventory_movements', 'product_id'],
            ['payments', 'payment_method_id'],
            ['payments', 'financial_account_id'],
            ['bank_transactions', 'financial_account_id'],
            ['bank_reconciliations', 'bank_transaction_id'],
            ['payrolls', 'payroll_period_id'],
            ['payrolls', 'employee_id'],
            ['payroll_items', 'payroll_id'],
            ['payslips', 'payroll_id'],
            ['payslips', 'employee_id'],
            ['attendances', 'employee_id'],
            ['assets', 'asset_category_id'],
            ['expenses', 'expense_category_id'],
        ];

        return array_combine(
            array_map(fn (array $p) => "{$p[0]}.{$p[1]}", $pairs),
            $pairs
        );
    }

    #[Test]
    #[DataProvider('indexedForeignKeyProvider')]
    public function foreign_key_is_indexed(string $table, string $column): void
    {
        $indexed = collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => in_array($column, $index['columns'], true));

        $this->assertTrue($indexed, "FK {$table}.{$column} wajib di-index");
    }

    #[Test]
    public function transaction_date_index_exists(): void
    {
        $expectations = [
            'sales_transactions' => 'transaction_date',
            'purchase_orders' => 'order_date',
            'goods_receipts' => 'receipt_date',
            'purchase_invoices' => 'invoice_date',
            'payments' => 'payment_date',
            'bank_transactions' => 'transaction_date',
            'inventory_movements' => 'movement_date',
            'stock_transfers' => 'transfer_date',
            'stock_opnames' => 'opname_date',
            'expenses' => 'expense_date',
            'bookings' => 'booking_date',
        ];

        foreach ($expectations as $table => $column) {
            $indexed = collect(Schema::getIndexes($table))
                ->contains(fn (array $index) => in_array($column, $index['columns'], true));

            $this->assertTrue($indexed, "{$table}.{$column} wajib di-index");
        }
    }

    #[Test]
    public function bank_reconciliation_status_is_indexed(): void
    {
        $indexed = collect(Schema::getIndexes('bank_transactions'))
            ->contains(fn (array $index) => in_array('reconciliation_status', $index['columns'], true));

        $this->assertTrue($indexed, 'bank_transactions.reconciliation_status wajib di-index');
    }

    #[Test]
    public function branch_scope_index_exists_for_operational_tables(): void
    {
        $tables = [
            'sales_transactions', 'purchase_orders', 'goods_receipts', 'purchase_invoices',
            'bookings', 'expenses', 'attendances', 'warehouses', 'employees',
        ];

        foreach ($tables as $table) {
            $hasBranch = Schema::hasColumn($table, 'branch_id');
            $indexed = collect(Schema::getIndexes($table))
                ->contains(fn (array $index) => in_array('branch_id', $index['columns'], true));

            $this->assertTrue($hasBranch && $indexed, "{$table} harus punya branch_id ter-index");
        }
    }

    #[Test]
    public function foreign_keys_enforce_referential_integrity(): void
    {
        $expected = [
            'employees' => ['branch_id', 'department_id', 'position_id', 'manager_id'],
            'sales_transactions' => ['branch_id', 'customer_id'],
            'inventory_movements' => ['warehouse_id', 'product_id'],
            'payrolls' => ['payroll_period_id', 'employee_id'],
            'payments' => ['payment_method_id', 'financial_account_id'],
        ];

        foreach ($expected as $table => $columns) {
            $foreignKeys = collect(Schema::getForeignKeys($table))
                ->flatMap(fn (array $fk) => $fk['columns']);

            foreach ($columns as $column) {
                $this->assertTrue(
                    $foreignKeys->contains($column),
                    "FK {$table}.{$column} harus punya constraint"
                );
            }
        }
    }

    #[Test]
    public function polymorphic_columns_are_present_without_fk_constraint(): void
    {
        $polymorphic = [
            'media_attachments' => ['attachable_type', 'attachable_id'],
            'audit_logs' => ['auditable_type', 'auditable_id'],
            'digital_accounts' => ['owner_type', 'owner_id'],
        ];

        foreach ($polymorphic as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertTrue(Schema::hasColumn($table, $column));

                $foreignKeys = collect(Schema::getForeignKeys($table))
                    ->flatMap(fn (array $fk) => $fk['columns']);

                $this->assertFalse(
                    $foreignKeys->contains($column),
                    "{$table}.{$column} polymorphic tidak boleh punya FK constraint"
                );
            }
        }
    }

    #[Test]
    public function logical_reference_tables_have_source_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('payments', 'transaction_type'));
        $this->assertTrue(Schema::hasColumn('payments', 'transaction_id'));

        $indexed = collect(Schema::getIndexes('payments'))
            ->contains(fn (array $index) => $index['columns'] === ['transaction_type', 'transaction_id']);

        $this->assertTrue($indexed, 'payments harus punya index (transaction_type, transaction_id)');

        $this->assertTrue(Schema::hasColumn('approval_requests', 'request_type'));
        $this->assertTrue(Schema::hasColumn('approval_requests', 'request_id'));
        $this->assertTrue(Schema::hasColumn('inventory_movements', 'reference_type'));
        $this->assertTrue(Schema::hasColumn('inventory_movements', 'reference_id'));
    }

    #[Test]
    public function snapshot_columns_exist_for_historical_data(): void
    {
        $this->assertTrue(Schema::hasColumn('sales_transaction_items', 'item_name'));
        $this->assertTrue(Schema::hasColumn('sales_transaction_items', 'unit_price'));
        $this->assertTrue(Schema::hasColumn('payroll_items', 'component_name'));
        $this->assertTrue(Schema::hasColumn('payslip_items', 'component_name'));
    }

    /**
     * PRD 01 §16 — tidak boleh ada tabel bank terpisah (BCA/BNI).
     */
    #[Test]
    public function no_per_bank_tables(): void
    {
        // bank_transactions & bank_reconciliations adalah tabel generik sesuai
        // PRD 01 §16, yang dilarang adalah tabel per bank (bca_*, bni_*, ...).
        foreach ($this->allTables() as $table) {
            $this->assertDoesNotMatchRegularExpression(
                '/^(bca|bni|bri|mandiri|bsi)_/',
                $table,
                "Tabel per bank {$table} melanggar PRD 01 §16"
            );
        }
    }

    /**
     * PRD 02 §37.2 — tidak boleh ada tabel per cabang.
     */
    #[Test]
    public function no_per_branch_tables(): void
    {
        foreach ($this->allTables() as $table) {
            $this->assertStringNotContainsString(
                'branch_',
                $table,
                "Tabel per cabang {$table} melanggar PRD 02 §37.2"
            );
        }
    }

    #[Test]
    public function sales_product_and_service_use_same_engine(): void
    {
        $columns = collect(Schema::getColumns('sales_transaction_items'))
            ->pluck('name');

        foreach (['item_type', 'product_id', 'service_id', 'package_id'] as $column) {
            $this->assertTrue($columns->contains($column));
        }
    }

    #[Test]
    public function no_table_per_bill_type(): void
    {
        $billTables = collect($this->allTables())
            ->filter(fn (string $t) => preg_match('/^(bpjs|pam|internet|pbb|listrik|sewa|asuransi|cicilan)/i', $t))
            ->values();

        $this->assertTrue(
            $billTables->isEmpty(),
            'Tagihan harus satu tabel bills: ditemukan '.$billTables->implode(', ')
        );
    }

    /**
     * @return array<int, string>
     */
    private function allTables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn (object $row) => array_values((array) $row)[0])
            ->reject(fn (string $t) => in_array($t, [
                'migrations',
                'sessions', 'cache', 'cache_locks',
                'jobs', 'job_batches', 'failed_jobs',
            ], true))
            ->values()
            ->all();
    }
}