# PRD 02 — ERP Dian Mustika
## Software Development, Laravel Implementation, Workflow & Acceptance Criteria

**Version:** 1.0  
**Status:** Development Baseline  
**Target:** Laravel + MySQL  
**Database Source of Truth:** `01_PRD_ERP_Dian_Mustika_Database_Architecture.md`

---

# 1. Tujuan Development

Membangun ERP Dian Mustika sebagai aplikasi web modular, multi-cabang, secure, auditable, dan scalable untuk mengintegrasikan:

- operasional cabang
- penjualan produk
- penjualan jasa/treatment
- booking
- inventory
- purchasing
- finance
- bank
- tagihan
- HR
- payroll
- komisi
- slip gaji
- asset
- document management

Developer TIDAK BOLEH membuat struktur database baru yang bertentangan dengan PRD Database tanpa perubahan terdokumentasi.

---

# 2. Stack

Recommended baseline:

- PHP 8.3+
- Laravel current LTS-compatible release
- MySQL 8+
- Blade + Bootstrap 5 untuk admin UI
- Laravel Eloquent ORM
- Laravel Validation/Form Request
- Laravel Policies/Gates
- Laravel Queue untuk pekerjaan asynchronous
- Laravel Scheduler untuk pekerjaan berkala
- Storage abstraction untuk file
- PDF generator yang kompatibel Laravel
- PHPUnit/Pest untuk testing

Package tambahan harus digunakan hanya jika memberi manfaat jelas dan tidak mengubah arsitektur inti.

---

# 3. Architecture

Gunakan modular monolith Laravel.

Struktur konseptual:

```text
app/
 ├── Models/
 ├── Http/
 │   ├── Controllers/
 │   ├── Requests/
 │   └── Resources/
 ├── Services/
 ├── Actions/
 ├── Policies/
 ├── Jobs/
 ├── Notifications/
 ├── Events/
 └── Support/
```

Domain service wajib digunakan untuk proses bisnis kompleks, bukan menumpuk business logic di controller.

Contoh:
- SalesService
- InventoryService
- PurchaseService
- PayrollService
- PayslipService
- PaymentService
- BankReconciliationService

---

# 4. Development Rules

## 4.1 Jangan duplikasi business logic
Controller hanya:
1. validasi/request
2. authorize
3. panggil service
4. return response

## 4.2 Database access
Gunakan Eloquent relationships, scopes dan service/query layer bila query kompleks.

## 4.3 Mass assignment
Gunakan `$fillable`/`$guarded` secara aman.

## 4.4 Validation
Semua input penting wajib memakai Form Request.

## 4.5 Authorization
Semua operasi sensitif harus melalui Policy/Permission.

## 4.6 Transaction
Gunakan `DB::transaction()` untuk workflow multi-tabel.

---

# 5. Navigation / Menu

## Dashboard
- Executive Dashboard
- Branch Dashboard
- Sales Summary
- Finance Summary
- Inventory Alert
- Payroll Summary
- Pending Approval

## Organization
- Company
- Branch
- Department
- Position

## Master Data
- Customer
- Product
- Product Category
- Brand
- Unit
- Service
- Service Category
- Service Package
- Supplier
- Bank
- Financial Account
- Payment Method
- Digital Account
- Expense Category
- Bill Template

## Employee
- Employee
- Family
- BPJS
- Shift
- Attendance
- Overtime
- Salary Structure
- Employee Documents

## Treatment
- Booking
- Schedule
- Treatment
- Package
- Therapist/Employee Assignment

## Sales
- POS
- Sales History
- Payment
- Return
- Commission

## Purchasing
- Purchase Order
- Goods Receipt
- Purchase Invoice
- Supplier Payment
- Purchase Return

## Inventory
- Stock
- Stock Movement
- Stock Transfer
- Stock Opname
- Adjustment

## Finance
- Cash
- Bank
- Bank Mutation
- Reconciliation
- Expense
- Bills
- Financial Reports

## Payroll
- Payroll Period
- Payroll
- Adjustment
- Commission
- Payslip
- Payslip Template
- Payroll Payment

## Asset & Documents
- Asset
- Asset Movement
- File Manager

## Reports
- Sales
- Product
- Service
- Customer
- Inventory
- Purchasing
- Supplier
- Finance
- Bank
- Payroll
- Employee
- Branch
- Asset

## System
- Users
- Roles
- Permissions
- Approval
- Audit Log
- Settings

---

# 6. Multi-Branch

Owner/Super Admin:
- melihat semua cabang
- dapat filter dashboard
- dapat melihat consolidated report

Branch user:
- hanya dapat melihat branch yang diizinkan
- tidak boleh mengakses data cabang lain kecuali permission khusus

Implementasikan BranchScope/Policy secara konsisten.

---

# 7. Employee Workflow

## Create Employee
1. Admin membuka Employee.
2. Isi data personal.
3. Pilih branch, department, position.
4. Simpan.
5. Tambahkan keluarga.
6. Tambahkan BPJS.
7. Upload dokumen.

## Employee status
ACTIVE / INACTIVE / RESIGNED / TERMINATED sesuai kebutuhan bisnis.

Tidak menghapus employee yang memiliki transaksi historis.

---

# 8. Customer Workflow

Customer dapat dibuat dari:
- Master Customer
- POS
- Booking

Jika customer sudah memiliki nomor HP/email yang teridentifikasi, sistem harus memberikan warning duplicate sebelum membuat record baru.

Customer history berasal dari transaksi:
- sales
- booking
- treatment
- payment

---

# 9. Product Workflow

Create:
1. Category
2. Brand
3. Unit
4. Product
5. Price

Product dapat memiliki beberapa historical prices.

Ketika harga berubah:
- harga master berubah
- transaksi lama tidak berubah

---

# 10. Service / Treatment Workflow

Create service:
1. Category
2. Service
3. Pricing method
4. Price rules

### Duration pricing
Contoh:
- 60 min
- 90 min
- 120 min

### Weight pricing
Contoh:
- 40–50 kg
- 51–60 kg
- 61–70 kg

Sistem harus menolak overlap range.

---

# 11. Booking Workflow

```text
Booking
 ↓
Confirmation
 ↓
Check-in
 ↓
Treatment
 ↓
Completed
 ↓
Sales Invoice
 ↓
Payment
```

Booking yang cancelled/no-show tidak boleh menghasilkan pendapatan kecuali ada aturan bisnis khusus.

---

# 12. POS / Sales Workflow

POS harus dapat menjual:
- Product
- Service
- Package

Contoh cart:

```text
Minyak Massage       150.000
Massage 60 menit     180.000
Paket Treatment      300.000
-----------------------------
Subtotal             630.000
Discount              30.000
Grand Total           600.000
```

Saat checkout:
1. Validate cart.
2. Lock relevant stock.
3. Create sales header.
4. Create items.
5. Generate inventory movement untuk product.
6. Generate commission jika applicable.
7. Create payment.
8. Update transaction status.
9. Commit.

Semua dalam satu DB transaction.

---

# 13. Inventory Workflow

## Purchase
```text
PO
 ↓
Goods Receipt
 ↓
Inventory IN
```

## Sale
```text
Sales
 ↓
Inventory OUT
```

## Transfer
```text
Transfer Request
 ↓
Approval
 ↓
OUT Source
 ↓
IN Destination
```

## Stock Opname
```text
System Qty
 ↓
Physical Count
 ↓
Variance
 ↓
Approval
 ↓
Adjustment Movement
```

Negative stock:
- default: tidak diizinkan
- hanya role khusus dengan permission dapat override

---

# 14. Purchasing

PO status:
- DRAFT
- SUBMITTED
- APPROVED
- PARTIAL
- RECEIVED
- CLOSED
- CANCELLED

Goods receipt dapat partial.

Purchase invoice dapat:
- one PO → one invoice
- one PO → multiple invoice
sesuai konfigurasi.

---

# 15. Finance

Finance harus membedakan:
- operational transaction
- payment
- bank transaction
- expense
- bill

Tidak boleh mencatat pendapatan dua kali hanya karena payment dan bank mutation sama-sama masuk.

---

# 16. Bank Import & Reconciliation

Bank mutation dapat diinput manual atau melalui import file jika fitur import diaktifkan.

Import harus:
- validasi format
- deteksi duplicate berdasarkan external reference/hash
- preview sebelum commit
- simpan hasil import

Reconciliation:
1. pilih bank transaction
2. cari payment candidate
3. tampilkan nominal/date/reference
4. match
5. simpan reconciliation
6. audit

---

# 17. Bills

Bill template mendukung:
- monthly
- quarterly
- yearly
- one-time

Scheduler dapat membuat bill periodik dari template.

Contoh:
- Internet monthly
- PAM monthly
- BPJS monthly
- PBB yearly
- Cicilan sesuai periode

Reminder overdue dapat menggunakan notification/scheduler.

---

# 18. Payroll

Payroll harus mendukung:

### Automatic
- basic salary
- allowance
- attendance
- overtime
- commission
- BPJS
- deduction

### Manual
- bonus
- correction
- special allowance
- manual deduction
- other adjustment

Flow:

```text
Create Period
 ↓
Generate Payroll
 ↓
Review
 ↓
Manual Adjustment
 ↓
Approval
 ↓
Generate Payslip
 ↓
Payment
 ↓
Close Period
```

---

# 19. Payroll Calculation Rule

Formula dasar:

```text
Gross Earnings
= Basic Salary
+ Allowances
+ Bonus
+ Commission
+ Overtime
+ Other Earnings

Net Salary
= Gross Earnings
- Deductions
```

Setiap komponen harus dapat dikonfigurasi.

Jangan hard-code semua jenis tunjangan.

---

# 20. Manual Payroll Adjustment

UI wajib menyediakan:

- Add earning
- Add deduction
- Edit adjustment
- Delete draft adjustment
- Reason
- Attachment optional
- Approval

Manual adjustment:
- tidak mengubah employee master
- tidak mengubah salary structure
- tercatat di audit log
- jika payroll sudah approved, tidak dapat diedit langsung; gunakan reversal/correction workflow

---

# 21. Payslip

## Template
Admin dapat memilih template default.

Template memuat:
- logo
- company information
- employee identity
- period
- earnings
- deductions
- net salary
- notes
- signature
- verification code/QR optional

## Editing
Slip yang masih DRAFT/REVIEW dapat diedit secara manual.

Setelah APPROVED:
- locked
- tidak dapat diubah langsung
- perubahan harus melalui correction/reissue

## Output
- preview
- print
- PDF
- batch PDF
- download
- employee self-service

---

# 22. Employee Self Service

Jika modul portal employee diaktifkan:
- login
- profile
- attendance
- payslip
- download payslip
- pengajuan tertentu

Employee hanya dapat melihat datanya sendiri.

---

# 23. Commission

Commission dapat berasal dari sales item service.

Contoh:
```text
Treatment = Rp200.000
Rule = 20%
Commission = Rp40.000
```

Commission masuk ke `employee_commissions`, lalu dapat diambil payroll.

---

# 24. Asset Management

Asset:
- register
- upload invoice/dokumen
- lokasi
- kondisi
- branch
- movement
- maintenance note jika nanti ditambahkan

File manager harus mendukung:
- upload
- preview jika tipe didukung
- download
- metadata
- relasi entity
- permission

---

# 25. Digital Account

Data:
- Instagram
- Gmail
- Facebook
- TikTok
- Marketplace
- Domain
- Hosting
- Software

Password tidak ditampilkan kepada user biasa.

Jika credential storage diimplementasikan:
- encrypted at rest
- permission khusus
- audit access
- tidak pernah ditampilkan di log

---

# 26. Approval Engine

Minimal approval untuk:
- Purchase Order
- Expense
- Stock Transfer
- Stock Opname
- Payroll
- Manual Payroll Adjustment
- Bank Reconciliation jika diperlukan

Status:
DRAFT → SUBMITTED → APPROVED/REJECTED

---

# 27. Audit Log

Audit minimal untuk:
- login
- create/update/delete master penting
- perubahan harga
- perubahan stok
- sales cancellation
- refund
- payroll adjustment
- payroll approval
- payslip correction
- financial reconciliation
- perubahan permission

---

# 28. Security

Wajib:
- password hashing
- CSRF
- validation
- authorization
- rate limiting login
- secure file upload
- MIME validation
- maximum file size
- private storage untuk dokumen sensitif
- audit log
- session security
- least privilege

Sensitive fields:
- BPJS
- NIK
- rekening
- payroll
- credential digital account

harus memiliki permission khusus.

---

# 29. Reporting

## Sales
- Daily sales
- Monthly sales
- Product sales
- Service sales
- Package sales
- Sales by branch
- Sales by cashier
- Sales by employee
- Payment method

## Inventory
- Current stock
- Low stock
- Stock movement
- Stock valuation
- Transfer
- Stock opname

## Finance
- Cash flow
- Income
- Expense
- Bills
- Bank
- Reconciliation
- Profit/Loss summary

## HR
- Employee count
- Attendance
- Overtime
- Payroll
- Commission

## Customer
- New customer
- Returning customer
- Top customer
- Treatment history

---

# 30. Dashboard

Owner dashboard:
- omzet hari ini
- omzet bulan ini
- transaksi
- outstanding bills
- cash balance
- bank balance
- low stock
- payroll pending
- approval pending
- branch performance

Branch dashboard:
- branch sales
- branch transaction
- stock
- booking
- staff attendance
- expenses

---

# 31. Notifications

Optional but recommended:
- low stock
- overdue bill
- upcoming bill
- pending approval
- booking reminder
- payroll ready
- payslip published
- expiring asset/document
- employee document expiration

Use Laravel Notification + Queue.

---

# 32. API Readiness

Walaupun aplikasi awal web-based, service layer harus dibuat agar API dapat ditambahkan kemudian.

Future API:
- POS
- mobile employee
- booking
- customer
- reporting

Jangan menaruh business logic khusus Blade/controller.

---

# 33. Testing

Minimum automated tests:

### Unit
- service pricing duration
- service pricing weight
- commission calculation
- payroll calculation
- bill recurrence
- inventory calculation

### Feature
- create sale
- sale with product reduces stock
- sale service creates commission
- payment recorded
- purchase receipt increases stock
- stock transfer
- payroll generation
- payroll adjustment
- payslip generation
- bank reconciliation

### Security
- branch isolation
- role permission
- employee cannot see another employee payslip
- unauthorized payroll modification denied

---

# 34. Acceptance Criteria

System dianggap MVP siap apabila:

1. Multi-branch bekerja.
2. User/role/permission bekerja.
3. Employee + family + BPJS bekerja.
4. Product/category/price bekerja.
5. Service/category/duration/weight pricing bekerja.
6. Package bekerja.
7. Customer bekerja.
8. Booking bekerja.
9. POS product/service/package bekerja.
10. Payment bekerja.
11. Inventory ledger bekerja.
12. Purchase bekerja.
13. Stock transfer bekerja.
14. Bank account dan bank mutation bekerja.
15. Reconciliation bekerja.
16. Bill recurring bekerja.
17. Expense bekerja.
18. Attendance bekerja.
19. Payroll otomatis bekerja.
20. Payroll manual adjustment bekerja.
21. Payslip template dapat dipilih.
22. Payslip dapat diedit sebelum approval.
23. Payslip approved terkunci.
24. PDF/print berhasil.
25. Commission masuk payroll.
26. Asset/document upload bekerja.
27. Audit log bekerja.
28. Dashboard dan report utama bekerja.
29. Branch isolation bekerja.
30. Automated tests utama lulus.

---

# 35. Definition of Done

Setiap modul dianggap selesai apabila:

- migration selesai
- model dan relationship selesai
- factory/seeder relevan selesai
- validation selesai
- authorization selesai
- CRUD/UI selesai
- service/business logic selesai
- audit event sesuai kebutuhan
- test selesai
- error handling selesai
- empty/loading/error state tersedia
- pagination/filter/search tersedia untuk listing besar
- tidak ada N+1 query yang diketahui
- database transaction diterapkan pada workflow multi-tabel

---

# 36. Development Order

Implementasi bertahap:

### Phase 1 — Foundation
- auth
- users
- roles
- permissions
- company
- branch
- settings
- audit

### Phase 2 — Master
- employee
- family
- BPJS
- customer
- product
- service
- category
- package
- supplier
- bank
- financial account

### Phase 3 — Sales & Booking
- booking
- POS
- payment
- commission

### Phase 4 — Inventory & Purchasing
- warehouse
- stock
- movement
- PO
- goods receipt
- transfer
- opname

### Phase 5 — Finance
- cash
- bank mutation
- reconciliation
- bills
- expense

### Phase 6 — HR & Payroll
- shift
- attendance
- overtime
- salary structure
- payroll
- adjustment
- payslip
- payroll payment

### Phase 7 — Asset & Document
- asset
- movement
- document manager
- digital account

### Phase 8 — Reporting & Optimization
- dashboards
- reports
- export
- notifications
- performance optimization

---

# 37. Critical Development Rules

1. Jangan membuat tabel baru hanya karena ada menu baru jika entity existing masih dapat digunakan.
2. Jangan membuat tabel per cabang.
3. Jangan membuat tabel per bank.
4. Jangan membuat tabel per jenis tagihan.
5. Jangan membuat transaksi produk dan jasa menjadi dua engine berbeda.
6. Jangan mengubah histori transaksi ketika master berubah.
7. Jangan menghapus hard-delete data transaksi.
8. Jangan update stock langsung tanpa inventory movement.
9. Jangan mengubah payroll approved secara langsung.
10. Jangan menyimpan password digital account plaintext.
11. Semua perubahan sensitif harus audited.
12. Semua foreign key wajib konsisten.
13. Semua business workflow multi-table harus atomic.
14. Semua akses cabang harus melewati authorization scope.
15. PRD Database adalah source of truth untuk schema.

---

# 38. Expected Deliverables

Developer/OpenCode menghasilkan:

- Laravel project
- migrations
- models
- relationships
- factories
- seeders
- policies
- form requests
- services/actions
- controllers
- views
- routes
- jobs
- notifications
- tests
- database documentation
- ERD
- deployment documentation
- `.env.example`
- README

---

# 39. Final Principle

ERP Dian Mustika harus dibangun sebagai satu sistem terintegrasi:

```text
MASTER
  ↓
TRANSACTION
  ↓
INVENTORY / FINANCE / COMMISSION
  ↓
PAYROLL / REPORT
  ↓
AUDIT
```

Bukan sebagai kumpulan CRUD yang berdiri sendiri.

`01_PRD_ERP_Dian_Mustika_Database_Architecture.md` adalah sumber kebenaran struktur database.

`02_PRD_ERP_Dian_Mustika_Development.md` adalah sumber kebenaran implementasi.

Jika terdapat konflik, perubahan harus didokumentasikan terlebih dahulu sebelum migration/code diubah.
