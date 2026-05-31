# 🧱 Sistem Informasi Operasional Percetakan Batako Maros

Website operasional untuk **UMKM Percetakan Batako Maros, Ambon, Maluku** — menggantikan pencatatan manual menjadi pencatatan digital yang rapi, terukur, dan mudah dipantau oleh pemilik.

---

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Arsitektur Project](#-arsitektur-project)
- [Role & Akses](#-role--akses)
- [Prasyarat / Software Wajib](#-prasyarat--software-wajib-di-install)
- [Panduan Instalasi Lengkap](#-panduan-instalasi-lengkap-dari-0)
  - [Step 1: Install Software](#step-1-install-software)
  - [Step 2: Clone / Download Project](#step-2-clone--download-project)
  - [Step 3: Install Composer Dependencies](#step-3-install-composer-dependencies)
  - [Step 4: Setup Environment (.env)](#step-4-setup-environment-env)
  - [Step 5: Buat Database](#step-5-buat-database)
  - [Step 6: Seed Data Awal](#step-6-seed-data-awal)
  - [Step 7: Jalankan Server](#step-7-jalankan-server)
  - [Step 8: Buka Website & Login](#step-8-buka-website--login)
- [Kredensial Login](#-kredensial-login)
- [Struktur Folder Lengkap](#-struktur-folder-lengkap)
- [Keamanan](#-keamanan)
- [Desain UI](#-desain-ui)
- [Testing Checklist](#-testing-checklist)
- [Deploy Production (Gratis)](#-deploy-production-gratis)
  - [Pilih Hosting](#pilih-hosting)
  - [Buat Akun InfinityFree](#step-1-buat-akun-infinityfree)
  - [Buat Database MySQL](#step-2-buat-database-mysql)
  - [Upload File](#step-3-upload-file)
  - [Konfigurasi .env](#step-4-konfigurasi-env)
  - [Import Database](#step-5-import-database)
  - [Akses Website](#step-6-akses-website)
- [Lisensi](#-lisensi)

---

## ✨ Fitur Utama

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 1 | **Login Multi-role** | Login dengan username/email + password, redirect otomatis sesuai role |
| 2 | **Role Middleware** | Operator tidak bisa akses halaman Pemilik, dan sebaliknya |
| 3 | **Dashboard KPI** | Produksi, Penjualan, Stok, Deviasi + grafik interaktif Chart.js |
| 4 | **CRUD Lengkap** | Bahan Baku, Produksi, Penjualan, Tenaga Kerja |
| 5 | **Stok Otomatis** | Dihitung dari total produksi − total penjualan secara real-time |
| 6 | **Gaji Otomatis** | Total sak semen × tarif per sak per pekerja |
| 7 | **Validasi Stok** | Penjualan tidak boleh melebihi stok tersedia |
| 8 | **Laporan** | Produksi vs Penjualan, Keuangan, Gaji + Chart.js |
| 9 | **PDF & Excel** | Cetak laporan PDF (Dompdf) + Export data Excel (PhpSpreadsheet) |
| 10 | **Automated Tests** | 65 test scenarios — database integrity, business logic, helper functions |
| 10 | **Responsive** | Nyaman di laptop/PC dan HP (Bootstrap 5) |
| 11 | **Pagination + Filter** | Tabel dapat difilter tanggal/ukuran/search & dipaginasi |
| 12 | **Flash Messages** | Notifikasi sukses/gagal setelah setiap aksi CRUD |

---

## 🏗️ Arsitektur Project

Project menggunakan pola **PHP Native MVC Sederhana** dengan pemisahan yang bersih:

```
Request → index.php (Router) → Halaman PHP (Controller Logic) → Layouts + Views (UI)
```

### Layer Arsitektur

```
┌─────────────────────────────────────────┐
│           VIEWS / LAYOUTS               │  ← UI: header, sidebar, footer, content
├─────────────────────────────────────────┤
│         CONTROLLERS (Halaman PHP)       │  ← Logic: CRUD, validasi, redirect
├─────────────────────────────────────────┤
│           HELPERS (Fungsi)              │  ← Auth, Format, Stok, CSRF
├─────────────────────────────────────────┤
│        CONFIG (Database + .env)         │  ← Koneksi & environment
├─────────────────────────────────────────┤
│        DATABASE (MySQL)                 │  ← 7 tabel relasional
└─────────────────────────────────────────┘
```

### Alur Data

```
Operator Input → tabel produksi/penjualan → updateStok() → tabel stok
                                          ↓
Pemilik Dashboard ← tabel stok + produksi + penjualan (KPI + Chart.js)
Pemilik Laporan   ← Produksi vs Penjualan, Keuangan, Gaji
Pemilik Gaji      ← produksi.jumlah_sak_semen × pekerja.tarif_per_sak
```

### Database Relasi

```
users ────┐
           ├──→ bahan_baku (operator_id)
           ├──→ produksi (operator_id) ──→ pekerja (pekerja_id)
           ├──→ penjualan (operator_id)
           └──→ (session login)

pekerja ──→ gaji (pekerja_id)
stok    ←── produksi + penjualan (aggregate)
```

---

## 👥 Role & Akses

| Role | Menu | Hak Akses |
|------|------|-----------|
| **Pemilik** | Dashboard | Lihat KPI + grafik |
| | Bahan Baku | CRUD lengkap + filter + pagination |
| | Data Produksi | CRUD lengkap + filter + export Excel/PDF |
| | Data Penjualan | CRUD lengkap + filter + validasi stok |
| | Data Tenaga Kerja | CRUD + toggle status aktif/nonaktif |
| | Perhitungan Gaji | Hitung & simpan gaji per periode |
| | Laporan Produksi | Grafik + tabel produksi vs penjualan + PDF |
| | Laporan Keuangan | Total pendapatan + grafik bulanan + PDF |
| | Laporan Gaji | Detail per pekerja + PDF |
| **Operator** | Beranda | Ringkasan & quick action cards |
| | Input Bahan Baku | Form input harian |
| | Input Produksi | Form input harian |
| | Input Penjualan | Form input harian + auto-hitung total |

---

## 💿 Prasyarat / Software Wajib di Install

Sebelum memulai, pastikan software berikut **sudah terinstall** di komputer Anda:

| Software | Versi Minimum | Fungsi | Link Download |
|----------|--------------|--------|---------------|
| **XAMPP** | 7.4+ | Apache + MySQL + PHP | https://www.apachefriends.org |
| **Composer** | 2.0+ | PHP dependency manager | https://getcomposer.org |
| **Git** | 2.0+ | Version control (opsional) | https://git-scm.com |
| **Browser** | Chrome/Firefox | Akses website | - |

> ⚠️ **Penting**: XAMPP **WAJIB** diinstall terlebih dahulu. Pastikan PHP versi 7.4 atau lebih tinggi (cek: `php -v`).

---

## 🚀 Panduan Instalasi Lengkap (dari 0)

Ikuti langkah-langkah berikut **secara berurutan**. Jangan melompati step.

---

### Step 1: Install Software

#### 1a. Install XAMPP

1. Download XAMPP dari https://www.apachefriends.org
2. Install seperti biasa (Next → Next → Finish)
3. Buka **XAMPP Control Panel**
4. Klik **Start** pada:
   - ✅ **Apache** (port 80)
   - ✅ **MySQL** (port 3306)

```
[XAMPP Control Panel]
  Apache  → [Start]  ✅ Running (Port 80)
  MySQL   → [Start]  ✅ Running (Port 3306)
```

> 💡 Jika port 80 bentrok (misal karena IIS/Skype), ubah port Apache di Config → httpd.conf menjadi `8080`.

#### 1b. Install Composer

- **Windows**: Download dan jalankan Composer-Setup.exe dari https://getcomposer.org
- **Mac**: `brew install composer`
- **Linux**: `sudo apt install composer`

Verifikasi:

```bash
php -v        # Harus muncul PHP 7.4.x atau 8.x
composer -V   # Harus muncul Composer 2.x
```

---

### Step 2: Clone / Download Project

#### Opsi A: Clone via Git (disarankan)

```bash
cd /Applications/XAMPP/htdocs          # Mac
# ATAU
cd C:\xampp\htdocs                     # Windows

git clone https://github.com/username/umkm-percetakan-batako.git
cd umkm-percetakan-batako
```

#### Opsi B: Download ZIP & Extract

1. Download ZIP project
2. Extract ke folder `htdocs` XAMPP:

```
Windows: C:\xampp\htdocs\umkm-percetakan-batako\
Mac:     /Applications/XAMPP/htdocs/umkm-percetakan-batako/
Linux:   /opt/lampp/htdocs/umkm-percetakan-batako/
```

Pastikan struktur folder setelah extract:

```
umkm-percetakan-batako/
├── assets/
├── config/
├── helpers/
├── layouts/
├── operator/
├── pemilik/
├── vendor/          # ← Masih kosong, akan diisi di Step 3
├── database.sql
├── seeder.php
├── composer.json
├── .env.example
├── .htaccess
└── README.md
```

---

### Step 3: Install Composer Dependencies

Buka terminal/CMD di folder project, lalu jalankan:

```bash
cd umkm-percetakan-batako
composer install
```

Tunggu hingga selesai. Output yang diharapkan:

```
Installing dependencies from lock file
...
Generating autoload files
```

Folder `vendor/` sekarang akan berisi package:
- `dompdf/dompdf` — untuk cetak PDF
- `phpoffice/phpspreadsheet` — untuk export Excel
- `vlucas/phpdotenv` — untuk .env config

---

### Step 4: Setup Environment (.env)

```bash
# Copy file .env.example menjadi .env
cp .env.example .env
```

File `.env` default sudah sesuai untuk XAMPP lokal:

```env
DB_HOST=localhost
DB_NAME=db_batako_maros
DB_USER=root
DB_PASS=

APP_URL=http://localhost/umkm-percetakan-batako
APP_NAME=Percetakan Batako Maros
APP_ENV=development
```

> 💡 **Hanya ubah jika diperlukan** (misal: password MySQL Anda bukan kosong, atau port berbeda).

---

### Step 5: Buat Database

#### Opsi A: Via phpMyAdmin (disarankan untuk pemula)

1. Buka browser → `http://localhost/phpmyadmin`
2. Klik **SQL** di tab atas
3. Buka file `database.sql` di text editor, **copy semua isinya**
4. **Paste ke textarea SQL** di phpMyAdmin
5. Klik **Go** / **Kirim**

✅ Hasil: Database `db_batako_maros` dengan 7 tabel siap digunakan.

#### Opsi B: Via Command Line

```bash
# Masuk ke MySQL
mysql -u root

# (Jika ada password: mysql -u root -p)
```

Lalu jalankan di dalam MySQL prompt:

```sql
source C:\xampp\htdocs\umkm-percetakan-batako\database.sql;
-- Sesuaikan path dengan lokasi project Anda
exit;
```

✅ Verifikasi: jalankan `SHOW DATABASES;` — `db_batako_maros` harus muncul.

---

### Step 6: Seed Data Awal

Jalankan seeder dari terminal:

```bash
# Pastikan masih di folder project
php seeder.php
```

Output yang diharapkan:

```
=== Seeder: Percetakan Batako Maros ===

Membuat users...
  ✓ pemilik / admin123 — pemilik@batakomaros.com (role: pemilik)
  ✓ operator / operator123 — operator@batakomaros.com (role: operator)

Membuat data pekerja...
  ✓ Ahmad Fauzi (Rp65.000/sak, aktif)
  ✓ Budi Santoso (Rp65.000/sak, aktif)
  ... (5 pekerja total)

Membuat stok awal...
  ✓ Stok standar: 0
  ✓ Stok besar: 0

Membuat contoh data produksi...
  ✓ 14 data produksi

Membuat contoh data penjualan...
  ✓ 14 data penjualan

Memperbarui stok...
  ✓ standar: stok=3285
  ✓ besar: stok=1710

=== Selesai! ===
```

> 💡 Seeder akan membuat data contoh 7 hari terakhir (produksi & penjualan) agar dashboard langsung terisi data.

---

### Step 7: Jalankan Server

#### Opsi A: XAMPP (disarankan)

1. Buka **XAMPP Control Panel**
2. Pastikan **Apache** & **MySQL** dalam keadaan **Running** (hijau)
3. Buka browser → `http://localhost/umkm-percetakan-batako/`

#### Opsi B: PHP Built-in Server (tanpa XAMPP)

```bash
# Pastikan MySQL tetap jalan dari XAMPP
php -S localhost:8000
```

Lalu buka `http://localhost:8000`

---

### Step 8: Buka Website & Login

1. Buka browser → `http://localhost/umkm-percetakan-batako/`
2. Anda akan diarahkan ke halaman **Login**
3. Gunakan kredensial di bawah ini

---

## 🔑 Kredensial Login

### Akun Pemilik

| Field | Nilai |
|-------|-------|
| **Username** | `pemilik` |
| **Email** | `pemilik@batakomaros.com` |
| **Password** | `admin123` |
| **Role** | Pemilik |
| **Akses** | Semua menu: Dashboard, CRUD, Gaji, Laporan |

### Akun Operator

| Field | Nilai |
|-------|-------|
| **Username** | `operator` |
| **Email** | `operator@batakomaros.com` |
| **Password** | `operator123` |
| **Role** | Operator |
| **Akses** | Input harian: Bahan Baku, Produksi, Penjualan |

> 💡 Anda bisa login menggunakan **username** atau **email** — keduanya didukung.

---

## 📁 Struktur Folder Lengkap

```
umkm-percetakan-batako/
├── .env.example              # Template konfigurasi environment
├── .gitignore                # File yang di-exclude dari Git
├── .htaccess                 # URL rewrite + security rules
├── composer.json             # PHP dependencies
├── database.sql              # Struktur database (7 tabel)
├── seeder.php                # Seed data awal (users, contoh produksi, dll)
├── index.php                 # Root redirect (cek session → arahkan)
├── login.php                 # Halaman login (username/email + password)
├── logout.php                # Destroy session → redirect login
│
├── config/
│   └── database.php          # Koneksi PDO + .env loader
│
├── helpers/
│   ├── auth.php              # Auth (login, session, role, flash, format, XSS)
│   └── functions.php         # Bisnis logic (updateStok, getStok, CSRF)
│
├── layouts/
│   ├── header.php            # HTML head + topbar + flash message
│   ├── footer.php            # Scripts + closing tags
│   ├── sidebar_pemilik.php   # Sidebar menu Pemilik
│   └── sidebar_operator.php  # Sidebar menu Operator
│
├── pemilik/                  # === HALAMAN PEMILIK ===
│   ├── dashboard.php         # KPI Cards + Chart.js (produksi vs penjualan)
│   ├── bahan_baku.php        # CRUD Bahan Baku + filter + pagination
│   ├── produksi.php          # CRUD Produksi + filter + Excel/PDF export
│   ├── penjualan.php         # CRUD Penjualan + validasi stok + auto-hitung
│   ├── tenaga_kerja.php      # CRUD Pekerja + toggle status aktif/nonaktif
│   ├── gaji.php              # Hitung gaji per periode + simpan ke DB
│   ├── laporan_produksi.php  # Grafik + tabel produksi vs penjualan + PDF
│   ├── laporan_keuangan.php  # Total pendapatan + grafik bulanan + PDF
│   └── laporan_gaji.php      # Detail gaji per pekerja + PDF
│
├── operator/                 # === HALAMAN OPERATOR ===
│   ├── index.php             # Beranda + quick action cards + ringkasan
│   ├── input_bahan_baku.php  # Form input bahan baku harian
│   ├── input_produksi.php    # Form input produksi + update stok otomatis
│   └── input_penjualan.php   # Form input penjualan + validasi stok
│
├── assets/
│   ├── css/
│   │   └── style.css         # Full styling (login, sidebar, cards, tables, responsive)
│   └── js/
│       └── app.js            # Sidebar toggle, auto-hitung, delete confirm
│
├── tests/
│   └── TestRunner.php         # Automated test suite (65 tests)
└── vendor/                   # Composer dependencies (auto-generated)
```

---

## 🔒 Keamanan

| Aspek | Implementasi |
|-------|-------------|
| **Password Hashing** | `password_hash()` dengan bcrypt (default cost) |
| **Password Verify** | `password_verify()` saat login |
| **SQL Injection** | Semua query pakai PDO prepared statements (tidak ada string interpolation) |
| **Session Auth** | Session-based authentication, dicek di setiap halaman |
| **Role Protection** | `requireRole('pemilik')` / `requireRole('operator')` di setiap halaman |
| **XSS Prevention** | Fungsi `e()` = `htmlspecialchars(ENT_QUOTES, 'UTF-8')` pada semua output |
| **Input Validation** | Semua input wajib diisi, divalidasi sisi server |
| **Secure Headers** | `.htaccess` memblokir akses ke file `.env`, `.sql`, `.md`, `.json` |
| **CSRF Protection** | Tersedia via `csrfToken()` & `verifyCsrf()` di `helpers/functions.php` |
| **Session Regenerate** | Session dihancurkan total saat logout |

---

## 🎨 Desain UI

| Komponen | Warna / Spesifikasi |
|----------|---------------------|
| Sidebar | Navy `#1E293B` |
| Primary | Blue `#2563EB` |
| Accent / Batako | Orange `#D97706` |
| Success | Green `#16A34A` |
| Danger / Delete | Red `#DC2626` |
| Background | Soft gray `#F8FAFC` |
| Cards | White `#FFFFFF` + shadow |
| Font | **Inter** via Google Fonts |
| Icons | **Bootstrap Icons** 1.11 |

---

## 🧪 Testing Checklist

Semua 30 skenario sudah diuji dan PASS ✅. Lihat file `TESTING_CHECKLIST.md` untuk detail lengkap.

| Modul | Jumlah Skenario | Status |
|-------|----------------|--------|
| Login & Auth | 6 | ✅ |
| Operator Input | 3 | ✅ |
| Stok Otomatis | 1 | ✅ |
| Validasi | 1 | ✅ |
| Pemilik Dashboard | 1 | ✅ |
| Pemilik CRUD | 4 | ✅ |
| Pemilik Gaji | 2 | ✅ |
| Laporan | 3 | ✅ |
| UI Desktop & Mobile | 2 | ✅ |
| Filter & Pagination | 2 | ✅ |
| Format & Flash | 2 | ✅ |
| Delete Confirm & Auto | 2 | ✅ |
| **Total** | **30** | ✅ |

---

## 🔧 Troubleshooting

| Masalah | Solusi |
|---------|--------|
| **"Koneksi database gagal"** | Pastikan MySQL running di XAMPP. Cek `DB_USER` dan `DB_PASS` di `.env` |
| **Blank page / Error 500** | Cek versi PHP ≥ 7.4. Jalankan `composer install` ulang |
| **CSS/JS tidak muncul** | Pastikan base URL sesuai. Cek `.htaccess` Apache. |
| **Login gagal terus** | Cek user di tabel `users`. Jalankan ulang `php seeder.php` |
| **"Class 'PDO' not found"** | Aktifkan extension `pdo_mysql` di `php.ini` XAMPP |
| **"404 Not Found"** | Pastikan `mod_rewrite` aktif di Apache. Restart Apache |
| **Port 80 bentrok** | Ubah port Apache di XAMPP Config → httpd.conf → `Listen 8080` |
| **Vendor folder kosong** | Jalankan `composer install` di folder project |

### Cek Apache mod_rewrite

**Windows (XAMPP):**
1. Buka `C:\xampp\apache\conf\httpd.conf`
2. Cari baris `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Hilangkan tanda `#` di depan
4. Restart Apache

**Mac (XAMPP):**
1. Buka `/Applications/XAMPP/etc/httpd.conf`
2. Uncomment `LoadModule rewrite_module modules/mod_rewrite.so`
3. Restart Apache

**Linux (LAMPP):**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 📝 Catatan Pengembangan

### Jalankan Test Suite

Proyek memiliki **65 automated tests** yang mencakup:
- Koneksi database & struktur tabel
- Validasi data seed (users, pekerja, stok, produksi, penjualan)
- Logika bisnis: perhitungan stok, validasi stok, perhitungan gaji
- Integritas foreign key
- Fungsi helper: formatRupiah, formatTanggal, e(), CSRF
- Fungsi auth: isLoggedIn, isPemilik, isOperator
- Flash messages

```bash
# Jalankan semua test
php tests/TestRunner.php
```

### Reset Data

Jika ingin mengulang dari awal:

```bash
# Hapus semua data dan seed ulang
php seeder.php
```

---

## 🚀 Deploy Production (Gratis)

### Pilih Hosting

**Vercel / Netlify / Railway TIDAK bisa** karena project ini menggunakan **PHP + MySQL**, bukan static site.

| Platform | PHP | MySQL | Gratis? | Cocok? |
|----------|-----|-------|---------|--------|
| **InfinityFree** | ✅ Ya | ✅ Ya (2 DB) | ✅ Selamanya | ✅ **Paling cocok** |
| AwardSpace | ✅ Ya | ✅ Ya (1 DB) | ✅ 1 GB | ⚠️ Terbatas |
| 000WebHost | ✅ Ya | ✅ Ya | ✅ Tapi ada iklan | ⚠️ Iklan paksa |
| Vercel | ❌ Tidak | ❌ Tidak | ✅ | ❌ |
| Netlify | ❌ Tidak | ❌ Tidak | ✅ | ❌ |

> **Rekomendasi: InfinityFree** — gratis selamanya, 5GB disk, bandwidth tak terbatas,
> support PHP 8.0+, MySQL, .htaccess, dan tanpa kartu kredit.

---

### Langkah-langkah Deploy ke InfinityFree — Step by Step

---

#### Sebelum Mulai — Cek Informasi Akun Anda

Setelah login ke panel InfinityFree, Anda akan melihat halaman utama akun:

```
┌─────────────────────────────────────────────┐
│  Account: epiz_XXXXX                        │  ← INI ACCOUNT ID ANDA
│  Main Domain: epiz_XXXXX.infinityfreeapp.com │  ← INI URL WEBSITE ANDA
│  PHP Version: 8.0                           │
└─────────────────────────────────────────────┘
```

> 📝 **Catat 2 hal ini di notepad/HP:**
> 1. **Account ID**: `epiz_XXXXX` (angka setelah `epiz_`)
> 2. **Domain URL**: `epiz_XXXXX.infinityfreeapp.com`

---

#### Step 1: Buat Database MySQL

1. Buka [https://www.infinityfree.com](https://www.infinityfree.com)
2. Klik **Get Free Hosting**
3. Isi form pendaftaran (email, password)
4. Verifikasi email (cek inbox/spam)
5. Login ke panel InfinityFree


### Langkah-langkah Deploy ke InfinityFree — Step by Step

---

#### Sebelum Mulai — Cek Informasi Akun Anda

Setelah login ke panel InfinityFree, Anda akan melihat halaman utama akun:

```
┌─────────────────────────────────────────────┐
│  Account: epiz_XXXXX                        │  ← INI ACCOUNT ID ANDA
│  Main Domain: epiz_XXXXX.infinityfreeapp.com │  ← INI URL WEBSITE ANDA
│  PHP Version: 8.0                           │
└─────────────────────────────────────────────┘
```

> 📝 **Catat 2 hal ini di notepad/HP:**
> 1. **Account ID**: `epiz_XXXXX` (angka setelah `epiz_`)
> 2. **Domain URL**: `epiz_XXXXX.infinityfreeapp.com`

---

#### Step 1: Buat Database MySQL — PANDUAN DETAIL

Langkah ini **paling penting.** Buka panel InfinityFree dan ikuti satu per satu:

**①** Login ke panel → buka **Accounts** → klik nama akun Anda

**②** Cari dan klik **"MySQL Databases"** di sidebar kiri:

```
┌─────────────────────────────────────┐
│  📊 MySQL Databases  ← KLIK INI    │
│  📁 File Manager                    │
│  🌐 FTP Accounts                    │
└─────────────────────────────────────┘
```

**③** Akan muncul form **"Create MySQL Database"**. Isi:

```
┌──────────────────────────────────────────────────┐
│  CREATE MYSQL DATABASE                           │
│                                                  │
│  Database Name:  [ batako_maros        ]         │
│  Username:       [ batako_user         ]         │
│  Password:       [ •••••••••••         ]         │
│  (ulangi pwd):   [ •••••••••••         ]         │
│                                                  │
│  [ ✚ Create Database ]                          │
└──────────────────────────────────────────────────┘
```

| Field | Yang Diisi | Contoh |
|-------|-----------|--------|
| **Database Name** | `batako_maros` | Boleh nama lain, asal ingat |
| **Username** | `batako_user` | Boleh nama lain |
| **Password** | Buat password kuat | `BatakoMaros2025!` |
| **Repeat Password** | Ketik ulang | Sama seperti di atas |

**④** Klik **"Create Database"**

**⑤** Jika berhasil, akan muncul tabel:

```
┌──────────────────┬──────────────────────────┬──────────────┐
│ Database         │ Username                 │ Server Host  │
├──────────────────┼──────────────────────────┼──────────────┤
│ epiz_XXXXX_      │ epiz_XXXXX_batako_user   │ sqlXXX       │
│ batako_maros     │                          │ .infinity    │
│                  │                          │ free.com     │
└──────────────────┴──────────────────────────┴──────────────┘
```

⚠️ **Perhatikan**: InfinityFree OTOMATIS menambahkan prefix `epiz_XXXXX_`!

**⑥ CATAT INFORMASI INI — SANGAT PENTING!**

```text
╔══════════════════════════════════════════════════╗
║          INFORMASI DATABASE ANDA                 ║
╠══════════════════════════════════════════════════╣
║                                                  ║
║  🔹 DB_HOST (Server Host):                      ║
║     sqlXXX.infinityfree.com                     ║
║     (GANTI XXX dengan angka dari tabel,         ║
║      contoh: sql303)                            ║
║                                                  ║
║  🔹 DB_NAME (Nama Database):                    ║
║     epiz_XXXXX_batako_maros                     ║
║                                                  ║
║  🔹 DB_USER (Username):                         ║
║     epiz_XXXXX_batako_user                      ║
║                                                  ║
║  🔹 DB_PASS (Password):                         ║
║     [password yang kamu buat tadi]              ║
║                                                  ║
╚══════════════════════════════════════════════════╝
```

**Dimana letak nilai-nilai ini?**

| Variabel | Letak | Contoh |
|----------|-------|--------|
| `epiz_XXXXX` | Ada di pojok kanan atas panel | `epiz_12345` |
| `sqlXXX` | Ada di kolom **Server Host** tabel MySQL | `sql303` |
| `batako_maros` | Nama database yang kamu isi di form | `batako_maros` |
| `batako_user` | Username yang kamu isi di form | `batako_user` |
| Password | Password yang kamu buat | `BatakoMaros2025!` |

**Contoh konkret:**
- Account ID: `epiz_12345`
- Server Host: `sql303`
- Nama Database: `batako_maros`
- Username: `batako_user`

Maka:
```text
DB_HOST = sql303.infinityfree.com
DB_NAME = epiz_12345_batako_maros
DB_USER = epiz_12345_batako_user
DB_PASS = BatakoMaros2025!
```

---

#### Step 2: Siapkan File Project di Laptop

Buka terminal/CMD di folder project:

```bash
# 1. Masuk ke folder project
cd /path/to/umkm-percetakan-batako

# 2. Install composer untuk production (lebih ringan, tanpa dev)
rm -rf vendor/
composer install --no-dev --optimize-autoloader

# 3. Hapus file yang tidak perlu diupload
rm -f .env
rm -rf assets/design_ref/
rm -f analisa.jpeg
```

**File WAJIB diupload:** Semua file di folder project (37 file) + folder `vendor/` (hasil composer)

**File JANGAN diupload:** `.env` (buat baru di server), file desain (sudah dihapus)

---

#### Step 3: Upload File ke Server

Pilih salah satu cara:

##### 🅰️ Cara Mudah — File Manager (dari browser)

1. Di panel InfinityFree, klik **"File Manager"**
2. Masuk ke folder **`htdocs`**
3. Klik tombol **"Upload"** di toolbar
4. Upload file-file project (bisa zip semua file → upload zip → klik kanan → **Extract**)

##### 🅱️ Cara Cepat — FTP (FileZilla)

1. Download & install **FileZilla** dari https://filezilla-project.org
2. Di panel InfinityFree, buka **"FTP Accounts"** → **"Create FTP Account"** (atau gunakan default)
3. Dapatkan info koneksi:
   ```
   Host: ftp.infinityfree.com
   Username: epiz_XXXXX
   Password: [password FTP]
   Port: 21
   ```
4. Buka FileZilla → isi Host, Username, Password, Port 21 → klik **Quickconnect**
5. Panel kanan = folder server. Masuk ke **`htdocs`**
6. Panel kiri = folder laptop. Pilih semua file project
7. **Drag & drop** dari kiri ke kanan
8. Tunggu upload selesai

---

#### Step 4: Buat File .env di Server

File `.env` buat MANUAL di server (jangan upload dari laptop):

1. Di **File Manager**, klik kanan → **"New File"**
2. Nama file: `.env`
3. Klik file `.env` → **"Edit"**
4. **Isi dengan:**

```env
DB_HOST=sqlXXX.infinityfree.com
DB_NAME=epiz_XXXXX_batako_maros
DB_USER=epiz_XXXXX_batako_user
DB_PASS=password_yang_tadi_dibuat

APP_URL=http://epiz_XXXXX.infinityfreeapp.com
APP_NAME=Percetakan Batako Maros
APP_ENV=production
```

> ⚠️ **WAJIB GANTI:**
> - `sqlXXX` → Server Host dari tabel MySQL (contoh: `sql303`)
> - `epiz_XXXXX` → Account ID kamu
> - `password_yang_tadi_dibuat` → Password MySQL yang kamu buat

**Contoh hasil akhir yang benar:**
```env
DB_HOST=sql303.infinityfree.com
DB_NAME=epiz_12345_batako_maros
DB_USER=epiz_12345_batako_user
DB_PASS=BatakoMaros2025!

APP_URL=http://epiz_12345.infinityfreeapp.com
APP_NAME=Percetakan Batako Maros
APP_ENV=production
```

5. Klik **"Save"**

---

#### Step 5: Import Database + Seed Data

1. Di panel InfinityFree, klik **"phpMyAdmin"**
2. Login dengan **DB_USER** dan **DB_PASS** dari Step 1
   ```
   Username: epiz_XXXXX_batako_user
   Password: [password]
   ```
3. Setelah masuk, klik nama database di sidebar kiri (`epiz_XXXXX_batako_maros`)
4. Klik tab **"SQL"** di toolbar atas
5. Klik **"Choose File"** → pilih file **`database.sql`** dari folder project
6. Klik **"Go"** — tunggu query selesai
7. **Ulangi** langkah 4-6 untuk file **`seed_production.sql`**
8. ✅ Selesai! 7 tabel sudah terbuat + data awal sudah terisi

---

#### Step 6: Upload Folder vendor/

Folder `vendor/` sudah ada di laptop dari Step 2. Upload ke server:

**Via FileZilla:**
- Panel kiri (laptop) → cari folder `vendor/` → klik kanan → **Upload**
- Atau drag & drop folder `vendor/` dari kiri ke kanan (ke folder `htdocs`)

**Via File Manager:**
- Zip `vendor/` di laptop jadi `vendor.zip`
- Upload `vendor.zip` → klik kanan → **"Extract"**
- Hapus `vendor.zip`

---

#### Step 7: Verifikasi Website

1. Buka browser → ketik:
   ```
   http://epiz_XXXXX.infinityfreeapp.com
   ```
   (Ganti `epiz_XXXXX` dengan Account ID kamu)

2. Jika berhasil → **halaman login muncul!** 🎉

3. Login:

| Role | Username | Password |
|------|----------|----------|
| **Pemilik** | `pemilik` | `admin123` |
| **Operator** | `operator` | `operator123` |

4. Cek fitur:
   - Dashboard Pemilik (grafik + KPI cards)
   - Beranda Operator (3 KPI cards)
   - Tambah data Bahan Baku / Produksi / Penjualan
   - Export PDF & Excel

---

#### Troubleshooting

| Masalah | Kemungkinan Penyebab | Solusi |
|---------|---------------------|--------|
| **❌ 500 Internal Server Error** | File rusak / .htaccess error | Cek **Error Log** di panel InfinityFree |
| **❌ Blank page putih** | Ada PHP error | Edit `index.php` → tambah baris 2: `ini_set('display_errors', 1);` |
| **❌ "Koneksi database gagal"** | DB_HOST/DB_USER/DB_PASS salah | Cek file `.env` — pastikan cocok dengan data di panel MySQL |
| **❌ Login gagal** | Password hash salah | Import ulang `seed_production.sql` via phpMyAdmin |
| **❌ CSS/JS berantakan** | APP_URL salah | Cek `APP_URL` di `.env` |
| **❌ 404 halaman tidak ditemukan** | File belum terupload | Cek File Manager → folder `htdocs/` |
| **❌ Class not found** | vendor/ belum terupload | Upload folder `vendor/` |
| **❌ PDF export error** | Versi PHP | Cek PHP version di panel (min 8.0) |

---

#### Butuh Bantuan?

Jika masih error setelah semua langkah:
1. Cek **Error Log** di panel InfinityFree
2. Screenshot error-nya
3. Kirim ke sini, saya bantu debug 😊

---### Alternatif Hosting Berbayar (Jika Ingin Lebih Stabil)

| Platform | Harga | PHP | MySQL | SSH |
|----------|-------|-----|-------|-----|
| **Hostinger** | ~Rp15.000/bln | ✅ | ✅ | ✅ Ada |
| **Niagahoster** | ~Rp20.000/bln | ✅ | ✅ | ✅ Ada |
| **Domainesia** | ~Rp18.000/bln | ✅ | ✅ | ✅ Ada |

Dengan hosting berbayar, Anda bisa:
- SSH ke server → jalankan `composer install` langsung
- Jalankan `php seeder.php` langsung
- Domain sendiri (bukan subdomain)
- Lebih cepat & stabil

---

## 📄 Lisensi

Project skripsi — **Sistem Informasi Operasional Percetakan Batako Maros**  
Ambon, Maluku — 2025
