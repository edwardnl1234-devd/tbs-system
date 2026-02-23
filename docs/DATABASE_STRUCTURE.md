# TBS System - Struktur Database

> **Sistem Manajemen Pabrik Kelapa Sawit (PKS)**  
> Dokumentasi Struktur Database

---

## 📋 Daftar Isi

1. [Entity Relationship Diagram (ERD)](#1-entity-relationship-diagram-erd)
2. [Daftar Tabel](#2-daftar-tabel)
3. [Detail Struktur Tabel](#3-detail-struktur-tabel)
4. [Relasi Antar Tabel](#4-relasi-antar-tabel)
5. [Enum & Status Values](#5-enum--status-values)

---

## 1. Entity Relationship Diagram (ERD)

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   USERS     │       │  SUPPLIERS  │       │  CUSTOMERS  │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id (PK)     │       │ id (PK)     │       │ id (PK)     │
│ name        │       │ code        │       │ code        │
│ email       │       │ name        │       │ name        │
│ phone       │       │ type        │       │ contact     │
│ password    │       │ contact     │       │ email       │
│ role        │       │ phone       │       │ phone       │
│ status      │       │ address     │       │ address     │
└──────┬──────┘       │ status      │       │ product_types│
       │              └──────┬──────┘       │ status      │
       │                     │              └──────┬──────┘
       │                     │                     │
       │    ┌────────────────┴────────────┐       │
       │    │                             │       │
       │    ▼                             │       │
       │ ┌─────────────┐                  │       │
       │ │   TRUCKS    │                  │       │
       │ ├─────────────┤                  │       │
       │ │ id (PK)     │                  │       │
       │ │ plate_number│                  │       │
       │ │ driver_name │                  │       │
       │ │ capacity    │                  │       │
       │ │ type        │                  │       │
       │ │ status      │                  │       │
       │ └──────┬──────┘                  │       │
       │        │                         │       │
       │        ▼                         │       │
       │ ┌─────────────┐                  │       │
       │ │   QUEUES    │◄─────────────────┘       │
       │ ├─────────────┤                          │
       │ │ id (PK)     │                          │
       │ │ truck_id(FK)│                          │
       │ │ supplier_id │                          │
       │ │ queue_number│                          │
       │ │ bank        │                          │
       │ │ arrival_time│                          │
       │ │ status      │                          │
       │ └──────┬──────┘                          │
       │        │                                 │
       │        ▼                                 │
       │ ┌─────────────┐                          │
       ├►│  WEIGHINGS  │                          │
       │ ├─────────────┤                          │
       │ │ id (PK)     │                          │
       │ │ queue_id(FK)│                          │
       │ │ operator_id │                          │
       │ │ ticket_no   │                          │
       │ │ bruto_weight│                          │
       │ │ tara_weight │                          │
       │ │ netto_weight│                          │
       │ │ price_per_kg│                          │
       │ │ total_price │                          │
       │ │ status      │                          │
       │ └──────┬──────┘                          │
       │        │                                 │
       │        ▼                                 │
       │ ┌─────────────┐     ┌─────────────┐      │
       ├►│ SORTATIONS  │────►│  STOCK_TBS  │      │
       │ ├─────────────┤     ├─────────────┤      │
       │ │ id (PK)     │     │ id (PK)     │      │
       │ │ weighing_id │     │ weighing_id │      │
       │ │ mandor_id   │     │ sortation_id│      │
       │ │ good_weight │     │ quantity    │      │
       │ │ medium_wght │     │ quality     │      │
       │ │ poor_weight │     │ status      │      │
       │ │ reject_wght │     │ location    │      │
       │ │ final_wght  │     └──────┬──────┘      │
       │ └─────────────┘            │             │
       │                            ▼             │
       │                    ┌─────────────┐       │
       ├───────────────────►│ PRODUCTIONS │       │
       │                    ├─────────────┤       │
       │                    │ id (PK)     │       │
       │                    │ stock_tbs_id│       │
       │                    │ supervisor  │       │
       │                    │ tbs_input   │       │
       │                    │ cpo_output  │       │
       │                    │ kernel_out  │       │
       │                    │ shell_output│       │
       │                    │ OER/KER     │       │
       │                    │ status      │       │
       │                    └──────┬──────┘       │
       │                           │              │
       │         ┌─────────────────┼─────────────────┐
       │         ▼                 ▼                 ▼
       │  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐
       │  │  STOCK_CPO  │   │STOCK_KERNEL │   │ STOCK_SHELL │
       │  ├─────────────┤   ├─────────────┤   ├─────────────┤
       │  │ id (PK)     │   │ id (PK)     │   │ id (PK)     │
       │  │ production  │   │ production  │   │ production  │
       │  │ quantity    │   │ quantity    │   │ quantity    │
       │  │ tank_number │   │ quality     │   │ location    │
       │  │ quality     │   │ location    │   │ status      │
       │  │ status      │   │ status      │   │ stock_date  │
       │  └──────┬──────┘   └──────┬──────┘   └──────┬──────┘
       │         │                 │                 │
       │         └─────────────────┼─────────────────┘
       │                           ▼
       │                    ┌─────────────┐     ┌─────────────┐
       │                    │    SALES    │────►│SALES_DETAILS│
       │                    ├─────────────┤     ├─────────────┤
       │                    │ id (PK)     │     │ id (PK)     │
       │                    │ customer_id │◄────┼─────────────┤
       │                    │ so_number   │     │ sales_id    │
       │                    │ product_type│     │ stock_cpo_id│
       │                    │ quantity    │     │ stock_kernel│
       │                    │ price       │     │ stock_shell │
       │                    │ status      │     │ qty_sold    │
       │                    └─────────────┘     └─────────────┘
       │
       │  ┌─────────────────────────────────────────────────┐
       │  │              SUPPORTING TABLES                  │
       │  ├─────────────┬──────────────┬────────────────────┤
       │  │             │              │                    │
       │  ▼             ▼              ▼                    ▼
┌──────────────┐ ┌────────────┐ ┌──────────────┐ ┌─────────────┐
│ TBS_PRICES   │ │STOCK_OPNAME│ │STOCK_ADJUST  │ │ACTIVITY_LOGS│
├──────────────┤ ├────────────┤ ├──────────────┤ ├─────────────┤
│ id (PK)      │ │ id (PK)    │ │ id (PK)      │ │ id (PK)     │
│ effective_dt │ │ opname_date│ │ product_type │ │ user_id     │
│ supplier_type│ │ product    │ │ system_stock │ │ action      │
│ price_per_kg │ │ physical   │ │ physical     │ │ model_type  │
│ notes        │ │ system     │ │ difference   │ │ model_id    │
└──────────────┘ │ variance   │ │ adjusted_by  │ │ old/new_val │
                 │ counted_by │ │ approved_by  │ │ ip_address  │
                 │ verified_by│ │ status       │ └─────────────┘
                 │ status     │ └──────────────┘
                 └────────────┘
```

---

## 2. Daftar Tabel

### 2.1 Tabel Master

| No | Nama Tabel | Deskripsi | Jumlah Kolom |
|----|------------|-----------|--------------|
| 1 | `users` | Data pengguna sistem | 10 |
| 2 | `suppliers` | Data supplier TBS | 9 |
| 3 | `customers` | Data pelanggan/pembeli | 10 |
| 4 | `trucks` | Data kendaraan/truk | 8 |
| 5 | `tbs_prices` | Harga TBS per tanggal dan tipe supplier | 6 |

### 2.2 Tabel Transaksi

| No | Nama Tabel | Deskripsi | Jumlah Kolom |
|----|------------|-----------|--------------|
| 6 | `queues` | Antrian kedatangan truk | 14 |
| 7 | `weighings` | Data penimbangan | 15 |
| 8 | `sortations` | Data sortasi/grading TBS | 14 |
| 9 | `productions` | Data produksi (pengolahan TBS) | 16 |
| 10 | `sales` | Transaksi penjualan | 14 |
| 11 | `sales_details` | Detail item penjualan | 7 |

### 2.3 Tabel Stok

| No | Nama Tabel | Deskripsi | Jumlah Kolom |
|----|------------|-----------|--------------|
| 12 | `stock_tbs` | Stok TBS (Tandan Buah Segar) | 11 |
| 13 | `stock_cpo` | Stok CPO (Crude Palm Oil) | 15 |
| 14 | `stock_kernel` | Stok Palm Kernel | 9 |
| 15 | `stock_shell` | Stok Palm Shell | 8 |
| 16 | `stock_opnames` | Catatan stock opname | 13 |
| 17 | `stock_adjustments` | Penyesuaian stok | 12 |

### 2.4 Tabel Sistem

| No | Nama Tabel | Deskripsi | Jumlah Kolom |
|----|------------|-----------|--------------|
| 18 | `activity_logs` | Log aktivitas pengguna | 12 |
| 19 | `personal_access_tokens` | Token autentikasi (Sanctum) | 10 |
| 20 | `password_reset_tokens` | Token reset password | 3 |
| 21 | `sessions` | Sesi pengguna | 6 |
| 22 | `cache` | Cache aplikasi | 3 |
| 23 | `jobs` | Background jobs queue | 8 |

---

## 3. Detail Struktur Tabel

### 3.1 Tabel `users`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `name` | VARCHAR(255) | NO | - | Nama lengkap |
| `email` | VARCHAR(255) | NO | - | Email (unique) |
| `email_verified_at` | TIMESTAMP | YES | NULL | Waktu verifikasi email |
| `phone` | VARCHAR(20) | YES | NULL | Nomor telepon |
| `password` | VARCHAR(255) | NO | - | Password (hashed) |
| `role` | ENUM | NO | 'staff' | Role pengguna |
| `status` | ENUM | NO | 'active' | Status akun |
| `remember_token` | VARCHAR(100) | YES | NULL | Token remember me |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`email`)

---

### 3.2 Tabel `suppliers`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `code` | VARCHAR(50) | NO | - | Kode supplier (unique) |
| `name` | VARCHAR(200) | NO | - | Nama supplier |
| `type` | ENUM('inti','plasma','umum') | NO | 'umum' | Tipe supplier |
| `contact_person` | VARCHAR(100) | YES | NULL | Nama kontak |
| `phone` | VARCHAR(20) | YES | NULL | Nomor telepon |
| `address` | TEXT | YES | NULL | Alamat |
| `status` | ENUM('active','inactive') | NO | 'active' | Status |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`code`)
- INDEX (`type`)
- INDEX (`status`)

---

### 3.3 Tabel `customers`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `code` | VARCHAR(50) | NO | - | Kode customer (unique) |
| `name` | VARCHAR(200) | NO | - | Nama customer |
| `contact_person` | VARCHAR(100) | YES | NULL | Nama kontak |
| `phone` | VARCHAR(20) | YES | NULL | Nomor telepon |
| `email` | VARCHAR(100) | YES | NULL | Email |
| `address` | TEXT | YES | NULL | Alamat |
| `product_types` | JSON | YES | NULL | Jenis produk yang dibeli |
| `status` | ENUM('active','inactive') | NO | 'active' | Status |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`code`)
- INDEX (`status`)

---

### 3.4 Tabel `trucks`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `plate_number` | VARCHAR(20) | NO | - | Nomor plat (unique) |
| `driver_name` | VARCHAR(100) | YES | NULL | Nama supir |
| `driver_phone` | VARCHAR(20) | YES | NULL | Telepon supir |
| `capacity` | DECIMAL(10,2) | YES | NULL | Kapasitas (ton) |
| `type` | VARCHAR(50) | YES | NULL | Jenis truk |
| `status` | ENUM('active','inactive') | NO | 'active' | Status |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`plate_number`)
- INDEX (`status`)

---

### 3.5 Tabel `queues`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `truck_id` | BIGINT UNSIGNED | NO | - | FK → trucks.id |
| `supplier_id` | BIGINT UNSIGNED | YES | NULL | FK → suppliers.id |
| `queue_number` | VARCHAR(20) | NO | - | Nomor antrian (unique) |
| `supplier_type` | ENUM('inti','plasma','umum') | NO | 'umum' | Tipe supplier |
| `bank` | TINYINT | YES | NULL | Bank timbangan (1-4) |
| `arrival_time` | DATETIME | NO | - | Waktu kedatangan |
| `call_time` | DATETIME | YES | NULL | Waktu dipanggil |
| `estimated_call_time` | DATETIME | YES | NULL | Estimasi waktu panggil |
| `status` | ENUM | NO | 'waiting' | Status antrian |
| `priority` | TINYINT | NO | 0 | Prioritas (0=normal) |
| `notes` | TEXT | YES | NULL | Catatan |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`queue_number`)
- INDEX (`status`, `arrival_time`)
- INDEX (`bank`)
- INDEX (`supplier_type`)

**Foreign Keys:**
- `truck_id` → `trucks(id)` ON DELETE CASCADE
- `supplier_id` → `suppliers(id)` ON DELETE SET NULL

---

### 3.6 Tabel `weighings`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `queue_id` | BIGINT UNSIGNED | NO | - | FK → queues.id |
| `operator_id` | BIGINT UNSIGNED | YES | NULL | FK → users.id |
| `ticket_number` | VARCHAR(50) | NO | - | Nomor tiket (unique) |
| `bruto_weight` | DECIMAL(10,2) | YES | NULL | Berat bruto (kg) |
| `tara_weight` | DECIMAL(10,2) | YES | NULL | Berat tara (kg) |
| `netto_weight` | DECIMAL(10,2) | YES | NULL | Berat netto (kg) |
| `price_per_kg` | DECIMAL(10,2) | YES | NULL | Harga per kg |
| `total_price` | DECIMAL(15,2) | YES | NULL | Total harga |
| `weigh_in_time` | DATETIME | YES | NULL | Waktu timbang masuk |
| `weigh_out_time` | DATETIME | YES | NULL | Waktu timbang keluar |
| `status` | ENUM | NO | 'pending' | Status penimbangan |
| `notes` | TEXT | YES | NULL | Catatan |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`ticket_number`)
- INDEX (`status`, `weigh_in_time`)
- INDEX (`ticket_number`)

**Foreign Keys:**
- `queue_id` → `queues(id)` ON DELETE CASCADE
- `operator_id` → `users(id)` ON DELETE SET NULL

---

### 3.7 Tabel `sortations`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `weighing_id` | BIGINT UNSIGNED | NO | - | FK → weighings.id |
| `mandor_id` | BIGINT UNSIGNED | YES | NULL | FK → users.id |
| `good_quality_weight` | DECIMAL(10,2) | NO | 0 | Berat kualitas baik |
| `medium_quality_weight` | DECIMAL(10,2) | NO | 0 | Berat kualitas sedang |
| `poor_quality_weight` | DECIMAL(10,2) | NO | 0 | Berat kualitas buruk |
| `reject_weight` | DECIMAL(10,2) | NO | 0 | Berat reject |
| `assistant_deduction` | DECIMAL(10,2) | NO | 0 | Potongan asisten |
| `deduction_reason` | TEXT | YES | NULL | Alasan potongan |
| `final_accepted_weight` | DECIMAL(10,2) | NO | - | Berat final diterima |
| `mandor_score` | TINYINT | YES | NULL | Skor mandor |
| `operator_discipline_score` | TINYINT | YES | NULL | Skor disiplin operator |
| `sortation_time` | DATETIME | YES | NULL | Waktu sortasi |
| `notes` | TEXT | YES | NULL | Catatan |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`sortation_time`)

**Foreign Keys:**
- `weighing_id` → `weighings(id)` ON DELETE CASCADE
- `mandor_id` → `users(id)` ON DELETE SET NULL

---

### 3.8 Tabel `stock_tbs`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `weighing_id` | BIGINT UNSIGNED | YES | NULL | FK → weighings.id |
| `sortation_id` | BIGINT UNSIGNED | YES | NULL | FK → sortations.id |
| `quantity` | DECIMAL(10,2) | NO | - | Jumlah (kg) |
| `quality_grade` | ENUM('A','B','C') | YES | NULL | Grade kualitas |
| `status` | ENUM | NO | 'ready' | Status stok |
| `location` | VARCHAR(100) | YES | NULL | Lokasi penyimpanan |
| `received_date` | DATE | NO | - | Tanggal diterima |
| `processed_date` | DATE | YES | NULL | Tanggal diproses |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`status`, `received_date`)
- INDEX (`quality_grade`)

**Foreign Keys:**
- `weighing_id` → `weighings(id)` ON DELETE SET NULL
- `sortation_id` → `sortations(id)` ON DELETE SET NULL

---

### 3.9 Tabel `productions`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `stock_tbs_id` | BIGINT UNSIGNED | YES | NULL | FK → stock_tbs.id |
| `supervisor_id` | BIGINT UNSIGNED | YES | NULL | FK → users.id |
| `tbs_input_weight` | DECIMAL(10,2) | NO | - | Input TBS (kg) |
| `cpo_output` | DECIMAL(10,2) | NO | 0 | Output CPO (kg) |
| `kernel_output` | DECIMAL(10,2) | NO | 0 | Output Kernel (kg) |
| `shell_output` | DECIMAL(10,2) | NO | 0 | Output Shell (kg) |
| `empty_bunch_output` | DECIMAL(10,2) | NO | 0 | Output Tandan Kosong (kg) |
| `cpo_extraction_rate` | DECIMAL(5,2) | YES | NULL | OER (%) |
| `kernel_extraction_rate` | DECIMAL(5,2) | YES | NULL | KER (%) |
| `production_date` | DATE | NO | - | Tanggal produksi |
| `shift` | ENUM('pagi','siang','malam') | YES | NULL | Shift kerja |
| `batch_number` | VARCHAR(50) | YES | NULL | Nomor batch |
| `status` | ENUM | NO | 'processing' | Status produksi |
| `notes` | TEXT | YES | NULL | Catatan |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`production_date`, `status`)
- INDEX (`batch_number`)

**Foreign Keys:**
- `stock_tbs_id` → `stock_tbs(id)` ON DELETE SET NULL
- `supervisor_id` → `users(id)` ON DELETE SET NULL

---

### 3.10 Tabel `stock_cpo`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `production_id` | BIGINT UNSIGNED | YES | NULL | FK → productions.id |
| `quantity` | DECIMAL(10,2) | NO | - | Jumlah (kg) |
| `quality_grade` | ENUM('premium','standard','low') | YES | NULL | Grade kualitas |
| `tank_number` | VARCHAR(20) | YES | NULL | Nomor tangki |
| `tank_capacity` | DECIMAL(10,2) | YES | NULL | Kapasitas tangki |
| `stock_type` | ENUM | NO | 'production' | Tipe stok |
| `movement_type` | ENUM('in','out','adjustment') | NO | - | Tipe pergerakan |
| `reference_number` | VARCHAR(50) | YES | NULL | Nomor referensi |
| `stock_date` | DATE | NO | - | Tanggal stok |
| `expiry_date` | DATE | YES | NULL | Tanggal kadaluarsa |
| `status` | ENUM | NO | 'available' | Status stok |
| `notes` | TEXT | YES | NULL | Catatan |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`stock_date`, `status`)
- INDEX (`tank_number`)
- INDEX (`movement_type`)

**Foreign Keys:**
- `production_id` → `productions(id)` ON DELETE SET NULL

---

### 3.11 Tabel `stock_kernel`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `production_id` | BIGINT UNSIGNED | YES | NULL | FK → productions.id |
| `quantity` | DECIMAL(10,2) | NO | - | Jumlah (kg) |
| `quality_grade` | VARCHAR(20) | YES | NULL | Grade kualitas |
| `location` | VARCHAR(100) | YES | NULL | Lokasi gudang |
| `status` | ENUM('available','sold','transit') | NO | 'available' | Status |
| `stock_date` | DATE | NO | - | Tanggal stok |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`stock_date`, `status`)

**Foreign Keys:**
- `production_id` → `productions(id)` ON DELETE SET NULL

---

### 3.12 Tabel `stock_shell`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `production_id` | BIGINT UNSIGNED | YES | NULL | FK → productions.id |
| `quantity` | DECIMAL(10,2) | NO | - | Jumlah (kg) |
| `location` | VARCHAR(100) | YES | NULL | Lokasi gudang |
| `status` | ENUM('available','sold') | NO | 'available' | Status |
| `stock_date` | DATE | NO | - | Tanggal stok |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`stock_date`, `status`)

**Foreign Keys:**
- `production_id` → `productions(id)` ON DELETE SET NULL

---

### 3.13 Tabel `sales`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `customer_id` | BIGINT UNSIGNED | NO | - | FK → customers.id |
| `so_number` | VARCHAR(50) | NO | - | Nomor SO (unique) |
| `product_type` | ENUM | NO | - | Jenis produk |
| `quantity` | DECIMAL(10,2) | NO | - | Jumlah (kg) |
| `price_per_kg` | DECIMAL(10,2) | NO | - | Harga per kg |
| `total_amount` | DECIMAL(15,2) | NO | - | Total harga |
| `order_date` | DATE | NO | - | Tanggal order |
| `delivery_date` | DATE | YES | NULL | Tanggal kirim |
| `truck_plate` | VARCHAR(20) | YES | NULL | Plat truk pengiriman |
| `driver_name` | VARCHAR(100) | YES | NULL | Nama supir |
| `status` | ENUM | NO | 'pending' | Status penjualan |
| `notes` | TEXT | YES | NULL | Catatan |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`so_number`)
- INDEX (`order_date`, `status`)
- INDEX (`product_type`)

**Foreign Keys:**
- `customer_id` → `customers(id)` ON DELETE CASCADE

---

### 3.14 Tabel `sales_details`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `sales_id` | BIGINT UNSIGNED | NO | - | FK → sales.id |
| `stock_cpo_id` | BIGINT UNSIGNED | YES | NULL | FK → stock_cpo.id |
| `stock_kernel_id` | BIGINT UNSIGNED | YES | NULL | FK → stock_kernel.id |
| `stock_shell_id` | BIGINT UNSIGNED | YES | NULL | FK → stock_shell.id |
| `quantity_sold` | DECIMAL(10,2) | NO | - | Jumlah terjual |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`sales_id`)

**Foreign Keys:**
- `sales_id` → `sales(id)` ON DELETE CASCADE
- `stock_cpo_id` → `stock_cpo(id)` ON DELETE SET NULL
- `stock_kernel_id` → `stock_kernel(id)` ON DELETE SET NULL
- `stock_shell_id` → `stock_shell(id)` ON DELETE SET NULL

---

### 3.15 Tabel `tbs_prices`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `effective_date` | DATE | NO | - | Tanggal berlaku |
| `supplier_type` | ENUM('inti','plasma','umum') | NO | 'umum' | Tipe supplier |
| `price_per_kg` | DECIMAL(10,2) | NO | - | Harga per kg |
| `notes` | TEXT | YES | NULL | Catatan |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- UNIQUE (`effective_date`, `supplier_type`)
- INDEX (`effective_date`)
- INDEX (`supplier_type`)

---

### 3.16 Tabel `stock_opnames`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `opname_date` | DATE | NO | - | Tanggal opname |
| `product_type` | ENUM | NO | - | Jenis produk |
| `location` | VARCHAR(100) | YES | NULL | Lokasi |
| `physical_quantity` | DECIMAL(10,2) | NO | - | Jumlah fisik |
| `system_quantity` | DECIMAL(10,2) | NO | - | Jumlah sistem |
| `variance` | DECIMAL(10,2) | NO | - | Selisih |
| `variance_percentage` | DECIMAL(5,2) | YES | NULL | Persentase selisih |
| `counted_by` | BIGINT UNSIGNED | NO | - | FK → users.id |
| `verified_by` | BIGINT UNSIGNED | YES | NULL | FK → users.id |
| `remarks` | TEXT | YES | NULL | Catatan |
| `status` | ENUM('draft','verified','approved') | NO | 'draft' | Status |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`opname_date`, `product_type`)

**Foreign Keys:**
- `counted_by` → `users(id)` ON DELETE RESTRICT
- `verified_by` → `users(id)` ON DELETE SET NULL

---

### 3.17 Tabel `stock_adjustments`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `product_type` | ENUM | NO | - | Jenis produk |
| `system_stock` | DECIMAL(10,2) | NO | - | Stok sistem |
| `physical_stock` | DECIMAL(10,2) | NO | - | Stok fisik |
| `difference` | DECIMAL(10,2) | NO | - | Selisih |
| `adjustment_type` | ENUM('plus','minus','correction') | NO | - | Tipe adjustment |
| `reason` | TEXT | YES | NULL | Alasan |
| `adjusted_by` | BIGINT UNSIGNED | NO | - | FK → users.id |
| `approved_by` | BIGINT UNSIGNED | YES | NULL | FK → users.id |
| `adjustment_date` | DATE | NO | - | Tanggal adjustment |
| `status` | ENUM('pending','approved','rejected') | NO | 'pending' | Status |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`adjustment_date`, `status`)
- INDEX (`product_type`)

**Foreign Keys:**
- `adjusted_by` → `users(id)` ON DELETE RESTRICT
- `approved_by` → `users(id)` ON DELETE SET NULL

---

### 3.18 Tabel `activity_logs`

| Kolom | Tipe Data | Nullable | Default | Keterangan |
|-------|-----------|----------|---------|------------|
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| `user_id` | BIGINT UNSIGNED | YES | NULL | FK → users.id |
| `action` | VARCHAR(50) | NO | - | Aksi (created/updated/deleted) |
| `model_type` | VARCHAR(255) | NO | - | Nama model (App\Models\...) |
| `model_id` | BIGINT UNSIGNED | NO | - | ID record yang diubah |
| `old_values` | JSON | YES | NULL | Nilai sebelum diubah |
| `new_values` | JSON | YES | NULL | Nilai sesudah diubah |
| `ip_address` | VARCHAR(45) | YES | NULL | IP address |
| `user_agent` | TEXT | YES | NULL | Browser user agent |
| `description` | TEXT | YES | NULL | Deskripsi aktivitas |
| `created_at` | TIMESTAMP | YES | NULL | Waktu dibuat |
| `updated_at` | TIMESTAMP | YES | NULL | Waktu diupdate |

**Indexes:**
- PRIMARY KEY (`id`)
- INDEX (`model_type`, `model_id`)
- INDEX (`action`)
- INDEX (`created_at`)

**Foreign Keys:**
- `user_id` → `users(id)` ON DELETE SET NULL

---

## 4. Relasi Antar Tabel

### 4.1 Diagram Relasi

```
users (1) ──────────── (N) queues.operator_id
users (1) ──────────── (N) weighings.operator_id
users (1) ──────────── (N) sortations.mandor_id
users (1) ──────────── (N) productions.supervisor_id
users (1) ──────────── (N) stock_opnames.counted_by
users (1) ──────────── (N) stock_opnames.verified_by
users (1) ──────────── (N) stock_adjustments.adjusted_by
users (1) ──────────── (N) stock_adjustments.approved_by
users (1) ──────────── (N) activity_logs.user_id

suppliers (1) ───────── (N) queues.supplier_id

customers (1) ───────── (N) sales.customer_id

trucks (1) ──────────── (N) queues.truck_id

queues (1) ──────────── (1) weighings.queue_id

weighings (1) ───────── (1) sortations.weighing_id
weighings (1) ───────── (N) stock_tbs.weighing_id

sortations (1) ──────── (N) stock_tbs.sortation_id

stock_tbs (1) ───────── (N) productions.stock_tbs_id

productions (1) ─────── (N) stock_cpo.production_id
productions (1) ─────── (N) stock_kernel.production_id
productions (1) ─────── (N) stock_shell.production_id

sales (1) ──────────── (N) sales_details.sales_id

stock_cpo (1) ───────── (N) sales_details.stock_cpo_id
stock_kernel (1) ────── (N) sales_details.stock_kernel_id
stock_shell (1) ─────── (N) sales_details.stock_shell_id
```

### 4.2 Tabel Foreign Keys

| Tabel | Kolom FK | Referensi | ON DELETE |
|-------|----------|-----------|-----------|
| `queues` | `truck_id` | `trucks(id)` | CASCADE |
| `queues` | `supplier_id` | `suppliers(id)` | SET NULL |
| `weighings` | `queue_id` | `queues(id)` | CASCADE |
| `weighings` | `operator_id` | `users(id)` | SET NULL |
| `sortations` | `weighing_id` | `weighings(id)` | CASCADE |
| `sortations` | `mandor_id` | `users(id)` | SET NULL |
| `stock_tbs` | `weighing_id` | `weighings(id)` | SET NULL |
| `stock_tbs` | `sortation_id` | `sortations(id)` | SET NULL |
| `productions` | `stock_tbs_id` | `stock_tbs(id)` | SET NULL |
| `productions` | `supervisor_id` | `users(id)` | SET NULL |
| `stock_cpo` | `production_id` | `productions(id)` | SET NULL |
| `stock_kernel` | `production_id` | `productions(id)` | SET NULL |
| `stock_shell` | `production_id` | `productions(id)` | SET NULL |
| `sales` | `customer_id` | `customers(id)` | CASCADE |
| `sales_details` | `sales_id` | `sales(id)` | CASCADE |
| `sales_details` | `stock_cpo_id` | `stock_cpo(id)` | SET NULL |
| `sales_details` | `stock_kernel_id` | `stock_kernel(id)` | SET NULL |
| `sales_details` | `stock_shell_id` | `stock_shell(id)` | SET NULL |
| `stock_opnames` | `counted_by` | `users(id)` | RESTRICT |
| `stock_opnames` | `verified_by` | `users(id)` | SET NULL |
| `stock_adjustments` | `adjusted_by` | `users(id)` | RESTRICT |
| `stock_adjustments` | `approved_by` | `users(id)` | SET NULL |
| `activity_logs` | `user_id` | `users(id)` | SET NULL |

---

## 5. Enum & Status Values

### 5.1 Daftar ENUM per Tabel

| Tabel | Kolom | Values |
|-------|-------|--------|
| `users` | `role` | owner, manager, supervisor, operator, staff, mandor, admin, accounting, finance, operator_timbangan |
| `users` | `status` | active, inactive |
| `suppliers` | `type` | inti, plasma, umum |
| `suppliers` | `status` | active, inactive |
| `customers` | `status` | active, inactive |
| `trucks` | `status` | active, inactive |
| `queues` | `supplier_type` | inti, plasma, umum |
| `queues` | `status` | waiting, processing, completed, cancelled |
| `weighings` | `status` | pending, weigh_in, weigh_out, completed |
| `stock_tbs` | `quality_grade` | A, B, C |
| `stock_tbs` | `status` | ready, processing, processed |
| `productions` | `shift` | pagi, siang, malam |
| `productions` | `status` | processing, completed, quality_check |
| `stock_cpo` | `quality_grade` | premium, standard, low |
| `stock_cpo` | `stock_type` | production, persediaan, reserved |
| `stock_cpo` | `movement_type` | in, out, adjustment |
| `stock_cpo` | `status` | available, reserved, sold, transit |
| `stock_kernel` | `status` | available, sold, transit |
| `stock_shell` | `status` | available, sold |
| `sales` | `product_type` | CPO, Kernel, Shell, Empty_Bunch |
| `sales` | `status` | pending, delivered, completed, cancelled |
| `stock_opnames` | `product_type` | CPO, Kernel, Shell, TBS |
| `stock_opnames` | `status` | draft, verified, approved |
| `stock_adjustments` | `product_type` | CPO, Kernel, Shell, TBS |
| `stock_adjustments` | `adjustment_type` | plus, minus, correction |
| `stock_adjustments` | `status` | pending, approved, rejected |

### 5.2 Status Flow Diagram

```
QUEUES STATUS:
    waiting ──► processing ──► completed
       │                          
       └──────────────────────► cancelled

WEIGHINGS STATUS:
    pending ──► weigh_in ──► weigh_out ──► completed

STOCK_TBS STATUS:
    ready ──► processing ──► processed

PRODUCTIONS STATUS:
    processing ──► quality_check ──► completed

STOCK (CPO/Kernel/Shell) STATUS:
    available ──► reserved ──► sold
        │            │
        └──► transit─┘

SALES STATUS:
    pending ──► delivered ──► completed
       │
       └──────────────────► cancelled

STOCK_OPNAMES STATUS:
    draft ──► verified ──► approved

STOCK_ADJUSTMENTS STATUS:
    pending ──► approved
       │
       └────► rejected
```

---

*Dokumen ini dibuat berdasarkan file migrations di `database/migrations/`*  
*Terakhir diperbarui: 23 Januari 2026*
