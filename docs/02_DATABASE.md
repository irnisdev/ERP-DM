# Database Documentation — ERP Dian Mustika

**Sumber kebenaran:** `01_PRD_ERP_Dian_Mustika_Database_Architecture.md`
**Dokumen terkait:** `docs/03_ERD.md` (ERD lengkap per §31)
**Status:** Fase 1 selesai — 79 tabel bisnis, 143 FK, 218 test schema hijau

---

## 1. Koneksi

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_dian_mustika
DB_USERNAME=erp
DB_PASSWORD=erp_secret_2026
```

Database `erp_dian_mustika_test` dipakai PHPUnit. Jangan arahkan test ke
database production — `RefreshDatabase` akan menghapus isinya.

Server MariaDB bawaan LAMPPP di `/opt/lampp/bin/mysql`, socket
`/opt/lampp/var/mysql/mysql.sock`, port 3306.

## 2. Menjalankan

```bash
export PATH=/opt/lampp/bin:$HOME/.local/bin:$PATH

php artisan migrate:fresh --seed    # reset + isi master data
php artisan migrate --seed          # inkremental
php artisan db:seed                 # seed saja (idempotent)
php artisan test                    # 222 test
```

## 3. Konvensi Kolom (PRD 01 §3)

| Kategori | Tipe | Catatan |
|---|---|---|
| PK | `BIGINT UNSIGNED` AUTO_INCREMENT `id` | semua tabel |
| FK | `<singular_table>_id` BIGINT UNSIGNED | selalu ter-index |
| Uang | `DECIMAL(20,2)` | tidak ada FLOAT/DOUBLE di seluruh skema |
| Quantity | `DECIMAL(20,4)` | termasuk `min_weight`, `max_weight`, `weight` |
| Waktu | `DATE` untuk tanggal bisnis, `DATETIME` untuk jam transaksi | |
| Timestamp | `created_at`, `updated_at` | `audit_logs` hanya `created_at` (append-only) |
| Soft delete | `deleted_at` | hanya 7 master, tabel transaksi tidak |
| Nomor dokumen | kolom terpisah dari PK | unique |
| Enum | `VARCHAR` + komentar | bukan DB ENUM, agar extensible tanpa ALTER |

## 4. Urutan Migration

| File | Isi |
|---|---|
| `2026_01_01_000001_create_organization_tables.php` | companies, branches, departments, positions |
| `2026_01_01_000002_create_employee_tables.php` | employees, family, insurance |
| `2026_01_01_000003_create_user_role_permission_tables.php` | users, roles, permissions, pivot + tabel infrastruktur Laravel |
| `2026_01_01_000004_create_customer_tables.php` | customers |
| `2026_01_01_000005_create_product_tables.php` | product_categories, brands, units, products, product_prices |
| `2026_01_01_000006_create_service_package_tables.php` | service_categories, services, service_prices, service_packages, service_package_items |
| `2026_01_01_000007_create_financial_account_tables.php` | banks, financial_accounts, payment_methods |
| `2026_01_01_000008_create_supplier_purchasing_tables.php` | suppliers, supplier_bank_accounts, PO, PO items, goods receipts, purchase invoices |
| `2026_01_01_000009_create_inventory_tables.php` | warehouses, stocks, movements, transfers, opnames |
| `2026_01_01_000010_create_booking_sales_tables.php` | bookings, booking_items, sales, sales items, returns |
| `2026_01_01_000011_create_payment_bank_tables.php` | payments, bank_transactions, bank_reconciliations |
| `2026_01_01_000012_create_bill_expense_tables.php` | bill_templates, bills, expense_categories, expenses |
| `2026_01_01_000013_create_payroll_commission_payslip_tables.php` | payroll periods, salary components, structures, payrolls, items, adjustments, commissions, payslips |
| `2026_01_01_000014_create_attendance_shift_tables.php` | shifts, employee_shifts, attendances, overtime_records |
| `2026_01_01_000015_create_asset_media_digital_account_tables.php` | assets, movements, media, digital accounts |
| `2026_01_01_000016_create_approval_audit_tables.php` | approval_requests, audit_logs |

Migration dipecah per domain, bukan satu file besar, supaya setiap domain bisa
di-review dan di-rollback sendiri. banks sengaja dibuat sebelum supplier karena
`supplier_bank_accounts` mereferensikan `banks.id`.

## 5. Model Layer

74 model di `app/Models/` plus 3 trait di `app/Models/Concerns/`:

| Trait | Fungsi |
|---|---|
| `HasStatus` | scope `active()`, `withStatus()`, `isActive()` |
| `HasBranch` | relasi `branch()` + scope `inBranches()` untuk branch isolation |
| `HasCreator` | relasi `creator()` dan `approver()` |

Model business helper yang perlu diketahui:

| Model | Helper |
|---|---|
| `User` | `accessibleBranchIds()` — null = semua cabang (super admin), array = daftar branch_id |
| `Product` | `priceFor($type, $date)` — harga efektif, `scopeLowStock()` |
| `ServicePrice` | `scopeForDuration()`, `scopeForWeightRange()`, `scopeOverlapsWeightRange()` |
| `CommissionRule` | `calculate($base)` — 20% dari Rp200.000 = Rp40.000 |
| `SalesTransactionItem` | `validateReference()` — PRODUCT/SERVICE/PACKAGE wajib kolom sesuai |
| `Payroll` | `isLocked()`, `recalculateTotals()` |
| `Payslip` | `isLocked()`, `isEditable()` |
| `Bill` | `isOverdue()` |
| `ApprovalRequest` | `target()`, `modelForType()` |

## 6. Master Data (Phase 1)

18 seeder idempotent memakai `updateOrCreate` berbasis business code. Dijalankan
dua kali menghasilkan jumlah baris yang sama.

| Seeder | Isi |
|---|---|
| CompanySeeder | Dian Mustika |
| BranchSeeder | Pusat, Jakarta Selatan, Bandung |
| DepartmentSeeder | OPS, TREATMENT, SALES, FINANCE, HR, GA |
| PositionSeeder | 12 posisi di 6 department |
| RolePermissionSeeder | 8 role, 64 permission |
| BankSeeder | BCA, BNI, BRI, Mandiri, BSI |
| CashBankAccountSeeder | kas + rekening per cabang, rekening operasional BNI |
| UnitSeeder | 9 satuan |
| BrandSeeder | 5 brand |
| ProductCategorySeeder | 7 kategori |
| ServiceCategorySeeder | 7 kategori |
| ExpenseCategorySeeder | 8 kategori induk + 6 anak |
| DigitalAccountCategorySeeder | 7 kategori |
| SalaryComponentSeeder | 12 komponen (6 earning, 6 deduction) |
| PaymentMethodSeeder | CASH, TRANSFER, QRIS, DEBIT_CARD, EWALLET |
| ShiftSeeder | 4 shift |
| BillTemplateSeeder | 10 template (BPJS, PAM, PLN, PBB, sewa, ...) |
| AdminUserSeeder | admin + kasir dengan role |

### Akun default

| Email | Password | Role |
|---|---|---|
| `admin@dianmustika.co.id` | `password` | SUPER_ADMIN, OWNER |
| `kasir@dianmustika.co.id` | `password` | CASHIER |

Ganti password sebelum dipakai di environment yang dapat diakses user.

## 7. Validasi Schema

`tests/Feature/DatabaseSchemaTest.php` — 218 test, 2105 assertion:

| Kategori | Yang diuji |
|---|---|
| Keberadaan tabel | 79 tabel PRD, satu test per tabel |
| Unique constraint | email, employee_code, SKU, barcode, service_code, branch+code, warehouse+product, pivot pair, payroll period+employee, attendance employee+date |
| Business number | 14 nomor dokumen wajib unique |
| Soft delete | 7 master punya `deleted_at`, 14 tabel transaksi tidak punya |
| Tipe uang | 60 kolom eksplisit harus `DECIMAL(20,2)` |
| Tipe quantity | harus `DECIMAL(20,4)` |
| Larangan float | tidak ada FLOAT/DOUBLE di 79 tabel |
| Index FK | 34 FK ter-index |
| Index tanggal | 11 kolom tanggal transaksi ter-index |
| Branch scope | 9 tabel operasional punya `branch_id` ter-index |
| Integritas FK | constraint ada di 5 tabel kritis |
| Polymorphic | kolom ada tanpa FK constraint |
| Relasi logis | payments, approval_requests, inventory_movements punya kolom source |
| Snapshot | item_name, unit_price, component_name ada |
| Larangan desain | tidak ada tabel per bank, per cabang, per jenis tagihan |
| Single sales engine | produk/jasa/paket di satu tabel `sales_transaction_items` |

## 8. Aturan yang Dijaga Layer Ini

- Stok tidak pernah diubah langsung. `inventory_movements` adalah ledger,
  `inventory_stocks` adalah cache yang harus konsisten — keduanya wajib lewat
  service layer (PRD 01 §12, PRD 02 §37.8).
- Transaksi tidak pernah hard delete. Validasi di
  `transaction_tables_are_not_soft_deleted()`.
- `sales_transaction_items` menyimpan `item_name` + `unit_price` sebagai
  snapshot. Perubahan master tidak mengubah transaksi lama.
- Semua bank memakai `bank_transactions` yang sama. Tidak ada tabel per bank.
- Semua tagihan memakai `bills`. Tidak ada tabel per jenis tagihan.
- Penjualan produk, jasa, dan paket lewat `sales_transaction_items` yang sama.
