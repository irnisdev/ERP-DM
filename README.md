# ERP Dian Mustika

Sistem ERP multi-cabang untuk Dian Mustika — klinik dan pusat treatment yang
menjual produk, jasa treatment, dan paket.

## Sumber Kebenaran

| Dokumen | Isi |
|---|---|
| `01_PRD_ERP_Dian_Mustika_Database_Architecture.md` | Struktur database, 79 tabel, relasi, index, seeder plan |
| `02_PRD_ERP_Dian_Mustika_Development.md` | Standar implementasi, workflow, acceptance criteria |
| `docs/02_DATABASE.md` | Dokumentasi database, konvensi, cara menjalankan |
| `docs/03_ERD.md` | ERD lengkap per PRD 01 §31 |

PRD 01 adalah sumber kebenaran schema. PRD 02 adalah sumber kebenaran
implementasi. Perubahan yang bertentangan dengan PRD harus didokumentasikan
dahulu.

## Stack

| Komponen | Versi |
|---|---|
| PHP | 8.2.12 (LAMPPP) |
| Laravel | 12.69.3 |
| Database | MariaDB 10.4.32 (MySQL-compatible) |
| ORM | Eloquent |
| Test | PHPUnit |

Lihat [Deviasi dari PRD](#deviasi-dari-prd) untuk ketidaksesuaian versi.

## Menjalankan

`php` dan `composer` tidak ada di PATH sistem. Tambahkan dulu:

```bash
export PATH=/opt/lampp/bin:$HOME/.local/bin:$PATH
```

Setup database:

```bash
php artisan migrate:fresh --seed
```

Jalankan server:

```bash
php artisan serve
```

Test:

```bash
php artisan test
```

Akun seed (ganti password sebelum dipakai sungguhan):

| Email | Password | Role |
|---|---|---|
| `admin@dianmustika.co.id` | `password` | SUPER_ADMIN, OWNER |
| `kasir@dianmustika.co.id` | `password` | CASHIER |

## Status Progress

PRD 02 §36 menetapkan 8 phase implementasi.

| Phase | Scope | Status |
|---|---|---|
| 1 — Foundation | Schema database, master organization, user/role/permission | **Selesai** |
| 2 — Master | Employee, customer, product, service, package, supplier | **Selesai** |
| 3 — Sales & Booking | Booking, POS, payment, commission | Belum |
| 4 — Inventory & Purchasing | Warehouse, stock, movement, PO, receipt, transfer, opname | Belum |
| 5 — Finance | Cash, bank mutation, reconciliation, bills, expense | Belum |
| 6 — HR & Payroll | Shift, attendance, overtime, salary, payroll, payslip | Belum |
| 7 — Asset & Document | Asset, movement, file manager, digital account | Belum |
| 8 — Reporting | Dashboards, reports, export, notifications | Belum |

### Phase 1 — Foundation (selesai)

- 16 migration, 79 tabel bisnis, 143 foreign key constraint
- 74 model Eloquent dengan 247 relasi
- 18 seeder idempotent + 20 factory
- ERD lengkap di `docs/03_ERD.md`

Schema sudah mencakup seluruh domain PRD 01 (§4–§25) meskipun UI dan logic
bisnis dibangun bertahap sesuai phase.

### Phase 2 — Master (selesai)

Detail di `docs/04_PHASE2_MASTER.md`.

- Auth: login, rate limiting, session regeneration, audit login
- CRUD employee + keluarga + BPJS, dengan branch isolation
- CRUD customer dengan warning duplicate phone/email
- CRUD product + category/brand/unit + riwayat harga
- CRUD service dengan pricing FIXED / DURATION / WEIGHT dan validasi overlap
- CRUD package dengan multi-service
- CRUD supplier + rekening bank (data sensitif)
- Audit log pada seluruh master data
- 19 controller, 15 form request, 7 policy, 3 service

## Test

```bash
php artisan test
```

389 test, 2490 assertion. Selain test per fitur, ada test struktural yang menjaga
kontrak database dan routing:

| Test | Menjaga |
|---|---|
| `DatabaseSchemaTest` | 79 tabel, tipe kolom, index, unique constraint, larangan tabel per bank/cabang/tagihan |
| `ModelRelationshipTest` | setiap relasi menunjuk FK yang benar-benar ada |
| `RouteBindingTest` | nama parameter controller cocok dengan segment route |
| `PageSmokeTest` | setiap halaman render dengan dan tanpa data |

## Aturan yang Tidak Boleh Dilanggar

Berlaku di seluruh modul berikutnya (PRD 02 §37):

1. Tidak membuat tabel baru hanya karena ada menu baru
2. Tidak ada tabel per cabang, per bank, atau per jenis tagihan
3. Produk dan jasa memakai satu engine transaksi yang sama
4. Mengubah master tidak boleh mengubah histori transaksi
5. Transaksi tidak boleh hard delete
6. Stok tidak boleh di-update tanpa `inventory_movement`
7. Payroll approved tidak boleh diubah langsung
8. Password digital account tidak boleh plaintext
9. Semua perubahan sensitif harus masuk audit log
10. Semua workflow multi-table harus atomic (`DB::transaction`)

Test di `tests/Feature/DatabaseSchemaTest.php` menjaga sebagian aturan ini
secara otomatis, termasuk larangan tabel per bank/per cabang/per jenis tagihan.

## Deviasi dari PRD

Dokumen sesuai PRD 02 §39 — perubahan harus terdokumentasi.

| Item | PRD | Implementasi | Alasan |
|---|---|---|---|
| PHP | 8.3+ (§2) | 8.2.12 | Disetujui user. Laravel 12 mensyaratkan `^8.2`, tidak ada fitur PHP 8.3 yang dipakai ERP ini. |
| Database | MySQL 8+ (§2) | MariaDB 10.4.32 | MariaDB bawaan LAMPPP, tidak ada MySQL 8 di mesin ini. Kompatibel untuk seluruh fitur yang dipakai: DECIMAL, JSON, FK, window function tidak dibutuhkan. |
| Composer | — | 2.10.3 di `~/.local/bin` | `/opt/lampp/bin` dimiliki root dan `sudo` butuh password. Shebang diarahkan ke `/opt/lampp/bin/php`. |
| ERD | Wajib sebelum implementasi final (§31 PRD 01) | Dibuat setelah migration | Dihasilkan dari skema MySQL aktual, bukan asumsi manual, sehingga akurat. |

## Struktur Direktori

```text
app/
  Http/
    Controllers/    19 controller (Auth, master, simple-master)
    Requests/       15 form request
  Models/           74 model Eloquent
    Concerns/       Auditable, HasApprover, HasBranch, HasCreator, HasStatus
  Policies/         7 policy
  Services/         AuditService, CustomerService, ServicePricingService
  Support/          BranchScope
database/
  migrations/       16 migration, satu file per domain
  seeders/          18 seeder master data
  factories/        20 factory
docs/
  02_DATABASE.md    Dokumentasi database
  03_ERD.md         ERD lengkap
  04_PHASE2_MASTER.md  Dokumentasi Phase 2
resources/views/
  layouts/          app, navigation, flash
  master/           customer, employee, package, product, service, simple-master, supplier
tests/Feature/      14 test file
```

## Role & Permission

8 role dan 64 permission, di-seed oleh `RolePermissionSeeder`. Permission
dipetakan menjadi Gate ability dengan nama sama, sehingga Policy menulis
`$user->can('customer.manage')`.

| Role | Cakupan |
|---|---|
| OWNER, SUPER_ADMIN | Semua, lintas cabang |
| BRANCH_MANAGER | Semua modul di cabang sendiri |
| ADMIN | Operasional harian di cabang sendiri |
| CASHIER | POS, customer, booking |
| THERAPIST | Booking dan treatment |
| ACCOUNTING | Finance, purchasing, rekonsiliasi |
| HR_STAFF | Employee, payroll, payslip |

Data sensitif (NIK, BPJS, rekening, gaji, credential) butuh permission
tersendiri, terpisah dari permission modul.