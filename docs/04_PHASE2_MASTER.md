# Phase 2 — Master Data

**Status:** Selesai
**Rujukan:** `02_PRD_ERP_Dian_Mustika_Development.md` §7–§14, §27, §28, §35

Modul master data — employee, customer, product, service, package, supplier —
sudah dapat dipakai melalui web UI dengan autentikasi, form request, policy,
branch isolation, dan audit log.

## Modul

| Modul | CRUD | Fitur khusus |
|---|---|---|
| Employee | index, create, show, edit, update, delete | keluarga, BPJS, filter per cabang/department/status, shift (read) |
| Customer | penuh | warning duplicate phone/email (live AJAX), generate customer_code |
| Product | penuh | riwayat harga per tipe dengan periode efektif |
| Product Category / Brand / Unit | penuh | kategori berjenjang (parent), hapus ditolak bila sudah dipakai |
| Service | penuh | pricing FIXED / DURATION / WEIGHT dengan validasi overlap range |
| Service Category | penuh | berjenjang |
| Package | penuh | multi-service dengan quantity, item disinkronkan atomik |
| Supplier | penuh | rekening bank (sensitif, butuh permission terpisah) |

Halaman bersama: search, filter, pagination, empty state, flash message.

## Aturan bisnis yang diimplementasikan

### Employee (PRD 02 §7)
- Status: ACTIVE / INACTIVE / RESIGNED / TERMINATED
- Employee yang sudah punya transaksi historis (payroll, komisi, absensi) **tidak bisa dihapus**
- User cabang hanya melihat employee di cabangnya sendiri
- NIK unique
- BPJS butuh permission `sensitive.bpjs` terpisah dari `employee.manage`

### Customer (PRD 02 §8)
- Duplikasi phone/email dicek sebelum create; user diberi warning dan harus mengonfirmasi dengan `force_create`
- Pengecekan berjalan live via AJAX saat mengisi form
- Soft delete, tidak pernah hard delete

### Product (PRD 02 §9)
- SKU unique, barcode unique bila diisi
- Harga historis di `product_prices` terpisah dari `default_sell_price`
- Mengubah harga master tidak mengubah transaksi lama — transaksi menyimpan snapshot `unit_price`
- Produk yang sudah pernah terjual tidak bisa dihapus

### Service (PRD 02 §10)
- DURATION → `duration_minutes` wajib, satu harga per durasi per periode
- WEIGHT → `min_weight` dan `max_weight` wajib, **range tidak boleh overlap** untuk service dan periode yang sama
- FIXED → field durasi dan berat dibersihkan otomatis
- Service yang sudah terjual atau dibooking tidak bisa dihapus

### Package
- Minimal satu service
- Service yang sama tidak boleh masuk dua kali
- Header dan item disimpan dalam satu `DB::transaction`

### Supplier
- Nomor rekening adalah data sensitif → butuh `sensitive.rekening`
- Hanya satu rekening utama per supplier
- Nomor rekening unik per supplier, boleh sama antar supplier berbeda
- Supplier yang sudah punya PO tidak bisa dihapus

## Authorization

Permission di tabel `permissions` dipetakan menjadi Gate ability dengan nama
sama, jadi Policy cukup menulis `$user->can('customer.manage')`.

Role dan permission didefinisikan di `RolePermissionSeeder` — 8 role, 64 permission.
Super admin (`OWNER`, `SUPER_ADMIN`) melewati seluruh pemeriksaan lewat
`Gate::before()`.

Permission sensitif yang butuh perhatian khusus:

| Permission | MengQY | Melindungi |
|---|---|---|
| `sensitive.nik` | NIK | Employee |
| `sensitive.bpjs` | Data BPJS | Employee |
| `sensitive.rekening` | Nomor rekening | Supplier |
| `sensitive.payroll` | Gaji | Employee (Phase 6) |
| `sensitive.digital_credential` | Credential akun digital | Phase 7 |

Branch isolation diterapkan di tiga lapis:

1. **Query scope** — `BranchScope::apply()` membatasi listing employee
2. **Policy** — `sameBranch()` menolak akses lintas cabang
3. **Controller** — user cabang tidak bisa membuat employee di luar cabangnya

## Audit log

`Auditable` trait mencatat create/update/delete pada master data. Nilai lama
diambil saat event `updating` (sebelum save) sehingga `old_values` benar-benar
menyimpan nilai sebelumnya.

Kegagalan audit **tidak** menggagalkan operasi bisnis — error dicatat ke log
aplikasi.

Audit yang aktif saat ini: employee, customer, product, product_price, service,
service_price, service_package, supplier, supplier_bank_account, employee_family,
employee_insurance, serta seluruh master sederhana, company, branch,
department, position, bank, financial_account, payment_method, shift,
salary_component, bill_template.

## Tests

389 test, 2490 assertion.

| File | Test | Cakupan |
|---|---|---|
| `AuthenticationTest` | 12 | hashing, rate limit, session regeneration, audit login |
| `BranchScopeTest` | 6 | filter query, super admin, user tanpa employee |
| `AuditLogTest` | 8 | old/new values, audit failure tidak breaking |
| `CustomerTest` | 16 | duplicate warning, soft delete, search |
| `EmployeeTest` | 19 | branch isolation, BPJS permission, hapus dengan histori |
| `ProductTest` | 14 | SKU/barcode unique, price history, hapus dengan histori |
| `ServiceTest` | 21 | duration/weight pricing, overlap, snapshot harga |
| `PackageAndSupplierTest` | 16 | item sync, rekening sensitif, primary account |
| `SimpleMasterTest` | 11 | 4 master via base controller, hapus bila dipakai |
| `ModelRelationshipTest` | 6 | FK dan pivot key semua relasi |
| `RouteBindingTest` | 2 | nama parameter controller vs segment route |
| `PageSmokeTest` | 36 | semua halaman render, dengan dan tanpa data |
| `DatabaseSchemaTest` | 218 | schema vs PRD 01 |
| `FactoryTest` | 4 | factory master data |

### Dua bug yang tertangkap test struktural

`ModelRelationshipTest` menemukan `ProductCategory::products()` dan
`ServiceCategory::services()` memakai FK default Laravel
(`product_category_id`) padahal kolomnya `category_id`.

`RouteBindingTest` menemukan route `packages/{servicePackage}` dipasangkan dengan
controller `destroy(ServicePackage $package)`. Laravel tidak me-resolve binding
dan `$package` diterima sebagai instance kosong — DELETE mengembalikan 302 ke
index, tapi tidak ada baris yang terhapus. Tanpa test ini, bug tersebut lolos
diam-diam.

## Struktur

```text
app/
  Http/
    Controllers/
      Auth/LoginController.php
      SimpleMasterController.php      base untuk 4 master sederhana
      ResolvesRouteKey.php            marker interface
      *Controller.php                 16 controller
    Requests/                         15 form request
  Models/Concerns/                    Auditable, HasApprover, HasBranch, HasCreator, HasStatus
  Policies/                           7 policy
  Services/                           AuditService, CustomerService, ServicePricingService
  Support/BranchScope.php
resources/views/
  layouts/                             app, navigation, flash
  auth/login.blade.php
  dashboard.blade.php
  master/                              customer, employee, package, product, service, simple-master, supplier
tests/Feature/                         14 test file
```

## Yang Belum

- Upload dokumen employee (Phase 7)
- Shift CRUD dan attendance (Phase 6)
- Harga package dihitung otomatis dari harga service (saat ini admin yang menetapkan)
- Export daftar ke Excel/PDF (Phase 8)