# PRD 01 — ERP Dian Mustika
## Database Architecture, Relational Model, Seeder & Data Governance

**Version:** 1.0  
**Status:** Development Baseline  
**Target Stack:** Laravel + MySQL  
**Primary Goal:** Menjadi single source of truth untuk struktur database ERP Dian Mustika.

---

## 1. Tujuan

Dokumen ini mendefinisikan struktur database ERP Dian Mustika secara ter-normalisasi, konsisten, extensible, dan siap diimplementasikan pada Laravel Eloquent.

Cakupan utama:

- Multi-company / multi-branch
- Master karyawan, keluarga, BPJS
- Customer
- Produk, kategori, harga
- Jasa/perawatan, kategori, pricing berbasis durasi/berat
- Paket treatment
- Supplier dan pembelian
- Inventory dan mutasi antar cabang
- Sales produk/jasa/paket
- Booking treatment
- Pembayaran
- Kas, bank, mutasi bank dan rekonsiliasi
- Tagihan rutin
- Payroll, komisi, adjustment dan slip gaji
- Asset dan document management
- Digital account
- User, role, permission
- Approval dan audit log

## 2. Prinsip Arsitektur Database

### 2.1 Normalisasi
Gunakan normalisasi minimal sampai 3NF untuk master data. Duplikasi hanya diperbolehkan jika merupakan snapshot historis yang memang dibutuhkan untuk audit/transaksi.

### 2.2 Single Source of Truth
- Customer disimpan sekali di `customers`.
- Product disimpan sekali di `products`.
- Service disimpan sekali di `services`.
- Employee disimpan sekali di `employees`.
- Bank/rekening disimpan melalui master financial account.
- Mutasi semua bank menggunakan tabel yang sama.
- Tagihan semua jenis menggunakan tabel yang sama.
- Sales produk, jasa dan paket menggunakan engine transaksi yang sama.

### 2.3 Snapshot Historis
Data transaksi yang dapat berubah di master harus disimpan sebagai snapshot pada transaksi, misalnya:
- `sales_transaction_items.unit_price`
- `sales_transaction_items.item_name`
- `payroll_items.component_name`
- `payslip_items.component_name`

### 2.4 Multi-Cabang
Data operasional yang relevan wajib memiliki `branch_id` langsung atau melalui entity pemiliknya. Jangan membuat tabel terpisah per cabang.

### 2.5 Auditability
Transaksi finansial, inventory, payroll dan perubahan master penting harus dapat ditelusuri melalui user, timestamp, approval dan audit log.

---

# 3. Konvensi Database

- Table: plural snake_case.
- Column: singular snake_case.
- PK: `id` BIGINT UNSIGNED.
- FK: `<singular_table>_id`.
- Timestamp: `created_at`, `updated_at`.
- Soft delete untuk master yang tidak boleh hilang secara historis: `deleted_at`.
- Nomor dokumen menggunakan field business number terpisah dari PK.
- Monetary amount: DECIMAL(20,2).
- Quantity: DECIMAL(20,4).
- Jangan gunakan FLOAT/DOUBLE untuk uang.
- Index pada FK dan kolom pencarian yang sering digunakan.
- Unique constraint untuk kode bisnis yang memang harus unik.

---

# 4. Domain & Tabel

## 4.1 Organization

### companies
`id, code, name, legal_name, tax_number, phone, email, address, status, timestamps`

### branches
`id, company_id, code, name, branch_type, phone, email, address, status, timestamps`

### departments
`id, company_id, code, name, status, timestamps`

### positions
`id, department_id, code, name, status, timestamps`

Relasi:
- Company 1:N Branch
- Company 1:N Department
- Department 1:N Position
- Branch 1:N Employee

---

# 5. User, Role & Permission

### users
`id, employee_id nullable, name, email, password, status, last_login_at, timestamps`

### roles
`id, name, guard_name, description, timestamps`

### permissions
`id, name, guard_name, description, timestamps`

### role_user
`role_id, user_id`

### permission_role
`permission_id, role_id`

Constraint:
- `users.email` unique.
- Pivot pair unique.

---

# 6. Employee Domain

### employees
`id, employee_code, nik nullable, full_name, birth_place, birth_date, gender, phone, email, address, marital_status, employment_status, join_date, resign_date nullable, branch_id, department_id nullable, position_id nullable, manager_id nullable, status, timestamps, deleted_at`

Self relation:
- Employee `manager_id` → employees.id

### employee_family_members
`id, employee_id, name, relationship, nik nullable, birth_date nullable, gender nullable, phone nullable, address nullable, is_dependent, notes, timestamps`

### employee_insurances
`id, employee_id, insurance_type, provider, membership_number, insurance_class nullable, start_date, end_date nullable, employee_contribution, company_contribution, status, timestamps`

Relasi:
- Employee 1:N Family Member
- Employee 1:N Insurance

---

# 7. Customer Domain

### customers
`id, customer_code, name, gender nullable, birth_date nullable, phone nullable, email nullable, address nullable, customer_type, source nullable, notes nullable, status, timestamps, deleted_at`

Riwayat pembelian/treatment TIDAK disimpan sebagai kolom aggregate permanen kecuali untuk cache/performance layer.

---

# 8. Product Domain

### product_categories
`id, parent_id nullable, code, name, description nullable, status, timestamps`

### brands
`id, code, name, status, timestamps`

### units
`id, code, name, decimal_scale, status, timestamps`

### products
`id, sku, barcode nullable, category_id, brand_id nullable, unit_id, name, description nullable, purchase_price, default_sell_price, minimum_stock, maximum_stock nullable, weight nullable, status, timestamps, deleted_at`

### product_prices
`id, product_id, price_type, price, effective_date, end_date nullable, timestamps`

Rules:
- SKU unique.
- Barcode unique jika tidak null.
- Harga historis tidak mengubah harga transaksi lama.

---

# 9. Service / Treatment Domain

### service_categories
`id, parent_id nullable, code, name, description nullable, status, timestamps`

### services
`id, service_code, category_id, name, description nullable, pricing_method, default_duration_minutes nullable, status, timestamps, deleted_at`

`pricing_method`:
- FIXED
- DURATION
- WEIGHT

### service_prices
`id, service_id, duration_minutes nullable, min_weight nullable, max_weight nullable, price, effective_date, end_date nullable, timestamps`

Constraint bisnis:
- Untuk DURATION, `duration_minutes` wajib.
- Untuk WEIGHT, `min_weight` dan `max_weight` wajib.
- Range berat tidak boleh overlap untuk service dan periode yang sama.
- Harga historis tidak mengubah transaksi lama.

---

# 10. Package Domain

### service_packages
`id, code, name, description nullable, price, valid_from nullable, valid_until nullable, status, timestamps, deleted_at`

### service_package_items
`id, package_id, service_id, quantity, timestamps`

---

# 11. Supplier & Purchasing

### suppliers
`id, supplier_code, name, contact_person nullable, phone nullable, email nullable, address nullable, tax_number nullable, payment_terms_days nullable, status, timestamps, deleted_at`

### supplier_bank_accounts
`id, supplier_id, bank_id, account_number, account_name, branch_name nullable, is_primary, status, timestamps`

### purchase_orders
`id, po_number, supplier_id, branch_id, order_date, expected_date nullable, status, subtotal, discount, tax, grand_total, notes nullable, created_by, approved_by nullable, approved_at nullable, timestamps`

### purchase_order_items
`id, purchase_order_id, product_id, quantity, unit_price, discount, subtotal, notes nullable, timestamps`

### goods_receipts
`id, receipt_number, purchase_order_id nullable, supplier_id, branch_id, receipt_date, status, notes nullable, received_by, timestamps`

### goods_receipt_items
`id, goods_receipt_id, product_id, quantity, unit_cost nullable, notes nullable, timestamps`

### purchase_invoices
`id, invoice_number, supplier_id, purchase_order_id nullable, branch_id, invoice_date, due_date nullable, subtotal, discount, tax, grand_total, status, timestamps`

### purchase_invoice_items
`id, purchase_invoice_id, product_id, quantity, unit_price, discount, subtotal, timestamps`

---

# 12. Inventory

### warehouses
`id, branch_id, code, name, warehouse_type, address nullable, status, timestamps`

### inventory_stocks
`id, warehouse_id, product_id, quantity, reserved_quantity, timestamps`

Unique:
- `(warehouse_id, product_id)`

### inventory_movements
`id, warehouse_id, product_id, movement_type, quantity, unit_cost nullable, reference_type nullable, reference_id nullable, movement_date, notes nullable, created_by, timestamps`

Movement types:
- PURCHASE
- SALE
- TRANSFER_IN
- TRANSFER_OUT
- RETURN_IN
- RETURN_OUT
- ADJUSTMENT
- STOCK_OPNAME

### stock_transfers
`id, transfer_number, from_warehouse_id, to_warehouse_id, transfer_date, status, requested_by, approved_by nullable, approved_at nullable, notes nullable, timestamps`

### stock_transfer_items
`id, stock_transfer_id, product_id, quantity, timestamps`

### stock_opnames
`id, opname_number, warehouse_id, opname_date, status, created_by, approved_by nullable, approved_at nullable, notes nullable, timestamps`

### stock_opname_items
`id, stock_opname_id, product_id, system_quantity, physical_quantity, variance_quantity, notes nullable, timestamps`

Inventory principle:
- `inventory_movements` adalah ledger.
- `inventory_stocks` adalah current balance/cache yang harus konsisten dengan ledger.
- Setiap perubahan stok wajib menggunakan service/domain transaction, bukan update manual sembarang controller.

---

# 13. Booking / Treatment

### bookings
`id, booking_number, customer_id, branch_id, booking_date, start_time nullable, end_time nullable, status, notes nullable, created_by, timestamps`

### booking_items
`id, booking_id, service_id nullable, package_id nullable, employee_id nullable, scheduled_start nullable, scheduled_end nullable, status, notes nullable, timestamps`

Status:
- BOOKED
- CONFIRMED
- CHECKED_IN
- IN_TREATMENT
- COMPLETED
- CANCELLED
- NO_SHOW

---

# 14. Sales

### sales_transactions
`id, invoice_number, branch_id, customer_id nullable, cashier_id nullable, transaction_date, subtotal, discount, tax, grand_total, status, notes nullable, created_by, timestamps`

### sales_transaction_items
`id, sales_transaction_id, item_type, product_id nullable, service_id nullable, package_id nullable, item_name, quantity, unit_price, discount, subtotal, employee_id nullable, source_booking_item_id nullable, notes nullable, timestamps`

Aturan:
- PRODUCT → product_id wajib.
- SERVICE → service_id wajib.
- PACKAGE → package_id wajib.
- `item_name` adalah snapshot nama pada saat transaksi.
- `unit_price` adalah snapshot harga transaksi.

### sales_returns
`id, return_number, sales_transaction_id, branch_id, return_date, reason nullable, status, created_by, timestamps`

### sales_return_items
`id, sales_return_id, sales_transaction_item_id, product_id nullable, quantity, amount, timestamps`

---

# 15. Payment & Financial Account

### banks
`id, code, name, status, timestamps`

### financial_accounts
`id, account_code, account_type, bank_id nullable, branch_id nullable, account_number nullable, account_name, currency, opening_balance, status, timestamps`

Account types:
- CASH
- BANK
- E_WALLET
- OTHER

### payment_methods
`id, code, name, method_type, financial_account_id nullable, status, timestamps`

### payments
`id, payment_number, transaction_type, transaction_id, payment_method_id, financial_account_id nullable, amount, payment_date, reference_number nullable, status, notes nullable, created_by, timestamps`

Payment harus polymorphic/logical-reference terhadap sumber transaksi, dengan validasi service layer.

---

# 16. Bank Mutation & Reconciliation

### bank_transactions
`id, financial_account_id, transaction_date, transaction_type, amount, description nullable, reference_number nullable, balance nullable, external_reference nullable, reconciliation_status, imported_at nullable, timestamps`

### bank_reconciliations
`id, bank_transaction_id, payment_id nullable, matched_amount, status, reconciled_by nullable, reconciled_at nullable, notes nullable, timestamps`

Tidak membuat tabel BCA/BNI terpisah.

---

# 17. Bills / Tagihan

### bill_templates
`id, code, name, category, frequency, default_amount nullable, default_due_day nullable, branch_id nullable, supplier_id nullable, financial_account_id nullable, status, notes nullable, timestamps`

### bills
`id, bill_template_id, bill_number, branch_id nullable, period_start, period_end, issue_date nullable, due_date, amount, penalty_amount, total_amount, status, paid_at nullable, notes nullable, timestamps`

Frequency:
- MONTHLY
- QUARTERLY
- SEMESTERLY
- YEARLY
- ONE_TIME

Contoh:
- BPJS
- PAM
- Internet
- Cicilan
- PBB
- Listrik
- Sewa
- Asuransi

---

# 18. Expense

### expense_categories
`id, parent_id nullable, code, name, status, timestamps`

### expenses
`id, expense_number, branch_id nullable, expense_category_id, supplier_id nullable, amount, expense_date, financial_account_id nullable, bill_id nullable, description nullable, status, created_by, approved_by nullable, approved_at nullable, timestamps`

---

# 19. Payroll

### payroll_periods
`id, code, name, period_start, period_end, payment_date nullable, status, timestamps`

### salary_components
`id, code, name, type, calculation_method, is_taxable, is_bpjs, status, timestamps`

Types:
- EARNING
- DEDUCTION

### employee_salary_structures
`id, employee_id, effective_date, basic_salary, notes nullable, status, timestamps`

### employee_salary_components
`id, employee_id, salary_component_id, amount, calculation_type, effective_date, end_date nullable, status, timestamps`

### payrolls
`id, payroll_period_id, employee_id, basic_salary, total_earnings, total_deductions, net_salary, status, generated_at, approved_by nullable, approved_at nullable, timestamps`

### payroll_items
`id, payroll_id, salary_component_id nullable, component_name, type, amount, source_type nullable, source_id nullable, notes nullable, sort_order, timestamps`

### payroll_adjustments
`id, payroll_id, type, description, amount, reason, created_by, approved_by nullable, approved_at nullable, timestamps`

Payroll rule:
- Auto calculation mengambil master/attendance/commission/BPJS.
- Manual adjustment tidak mengubah master salary.
- Payroll yang approved menjadi historical snapshot.

---

# 20. Commission

### commission_rules
`id, employee_id nullable, service_id nullable, commission_type, commission_value, effective_date, end_date nullable, status, timestamps`

### employee_commissions
`id, employee_id, sales_transaction_id, sales_transaction_item_id nullable, commission_amount, status, payroll_id nullable, timestamps`

---

# 21. Payslip

### payslip_templates
`id, code, name, layout_config, status, is_default, timestamps`

`layout_config` dapat berupa JSON terstruktur untuk konfigurasi visual/field.

### payslips
`id, payroll_id, employee_id, template_id, payslip_number, issue_date, gross_salary, total_deduction, net_salary, status, pdf_path nullable, approved_by nullable, approved_at nullable, verification_code nullable, timestamps`

### payslip_items
`id, payslip_id, component_name, type, amount, notes nullable, sort_order, timestamps`

Workflow:
DRAFT → REVIEW → APPROVED → PUBLISHED → VOID

---

# 22. Attendance & Shift

### shifts
`id, branch_id nullable, code, name, start_time, end_time, break_minutes, status, timestamps`

### employee_shifts
`id, employee_id, shift_id, effective_date, end_date nullable, timestamps`

### attendances
`id, employee_id, branch_id, attendance_date, check_in nullable, check_out nullable, status, source, notes nullable, timestamps`

### overtime_records
`id, employee_id, attendance_id nullable, overtime_date, start_time, end_time, duration_minutes, approved_by nullable, status, timestamps`

---

# 23. Asset & Document

### asset_categories
`id, code, name, status, timestamps`

### assets
`id, asset_code, asset_category_id, name, purchase_date nullable, purchase_price nullable, branch_id nullable, location nullable, condition, status, notes nullable, timestamps, deleted_at`

### asset_movements
`id, asset_id, from_branch_id nullable, to_branch_id nullable, from_location nullable, to_location nullable, movement_date, reason nullable, moved_by, approved_by nullable, timestamps`

### media_files
`id, file_name, original_name, mime_type, file_size, storage_disk, storage_path, uploaded_by, uploaded_at, checksum nullable, timestamps`

### media_attachments
`id, media_file_id, attachable_type, attachable_id, collection_name nullable, timestamps`

Use polymorphic attachment agar file dapat digunakan oleh Employee, Product, Customer, Transaction, Asset, Branch, BPJS, Supplier, dll.

---

# 24. Digital Account

### digital_account_categories
`id, code, name, status, timestamps`

### digital_accounts
`id, category_id, name, username nullable, email nullable, url nullable, owner_type nullable, owner_id nullable, renewal_date nullable, status, notes nullable, timestamps`

Password/secret tidak disimpan plaintext. Jika dibutuhkan, gunakan encrypted credential storage dengan akses sangat terbatas.

---

# 25. Approval & Audit

### approval_requests
`id, request_type, request_id, requested_by, approver_id nullable, status, requested_at, approved_at nullable, rejected_at nullable, rejection_reason nullable, notes nullable, timestamps`

### audit_logs
`id, user_id nullable, action, auditable_type, auditable_id, old_values nullable, new_values nullable, ip_address nullable, user_agent nullable, created_at`

---

# 26. Relasi Utama

```text
Company
 ├── Branch
 │    ├── Employee
 │    ├── Warehouse
 │    ├── Customer activity
 │    ├── Sales
 │    ├── Purchase
 │    └── Finance
 └── Department
      └── Position

Employee
 ├── Family Members
 ├── Insurance/BPJS
 ├── Salary Structure
 ├── Salary Components
 ├── Attendance
 ├── Commission
 └── Payroll

Product
 ├── Category
 ├── Prices
 ├── Inventory
 ├── Purchase Items
 └── Sales Items

Service
 ├── Category
 ├── Prices
 ├── Package Items
 ├── Booking Items
 └── Sales Items

Sales
 ├── Items
 ├── Payments
 ├── Commission
 └── Inventory Movement

Purchase
 ├── Items
 ├── Goods Receipt
 ├── Invoice
 └── Payment

Financial Account
 ├── Payments
 └── Bank Transactions

Payroll
 ├── Items
 ├── Adjustments
 └── Payslip
```

---

# 27. Seeder Plan

Seeder wajib idempotent menggunakan `updateOrCreate`/`firstOrCreate` dan business code unik.

## Seeder sequence

1. `CompanySeeder`
2. `BranchSeeder`
3. `DepartmentSeeder`
4. `PositionSeeder`
5. `RolePermissionSeeder`
6. `BankSeeder`
7. `UnitSeeder`
8. `ProductCategorySeeder`
9. `ServiceCategorySeeder`
10. `ExpenseCategorySeeder`
11. `DigitalAccountCategorySeeder`
12. `SalaryComponentSeeder`
13. `PaymentMethodSeeder`
14. `ShiftSeeder`
15. `BillTemplateSeeder`
16. `AdminUserSeeder`
17. `DemoProductSeeder` (optional)
18. `DemoServiceSeeder` (optional)

Demo data harus dipisahkan dari production seed.

---

# 28. Initial Master Data

Minimal seed:
- Company: Dian Mustika
- Branch: Pusat / cabang awal
- Bank: BCA, BNI
- Payment: CASH, TRANSFER, QRIS
- Salary: BASIC_SALARY, ALLOWANCE, BONUS, COMMISSION, BPJS, DEDUCTION
- Bill frequency
- Employment status
- Booking status
- Sales status
- Inventory movement type
- Roles & permissions

---

# 29. Index & Constraint Strategy

Wajib index:
- seluruh FK
- business number
- transaction_date
- branch_id + transaction_date
- employee_id + period
- product_id + warehouse_id
- financial_account_id + transaction_date
- reconciliation_status

Wajib unique jika relevan:
- employee_code
- customer_code
- SKU
- service_code
- supplier_code
- branch code
- invoice number
- PO number
- booking number
- payroll code
- payslip number

---

# 30. Transaction Integrity

Gunakan DB transaction untuk:
- Sales + payment + stock movement
- Purchase + receipt + stock movement
- Stock transfer
- Payroll approval
- Payroll payment
- Expense payment
- Bank reconciliation
- Sales return

Tidak boleh ada kondisi:
- pembayaran sukses tetapi invoice gagal
- stok berkurang tanpa transaksi
- transfer keluar tanpa penerimaan yang sesuai
- payroll approved tetapi detail belum tersimpan

---

# 31. ERD Requirement

Sebelum implementasi final, developer wajib menghasilkan ERD yang mencakup seluruh entity dan FK.

ERD harus menunjukkan:
- PK
- FK
- Cardinality
- Nullable relation
- Polymorphic relation
- Unique constraints utama

