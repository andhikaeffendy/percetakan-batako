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

### Langkah-langkah Deploy ke InfinityFree

#### Step 1: Buat Akun InfinityFree

1. Buka [https://www.infinityfree.com](https://www.infinityfree.com)
2. Klik **Get Free Hosting**
3. Isi form pendaftaran (email, password)
4. Verifikasi email (cek inbox/spam)
5. Login ke panel InfinityFree

#### Step 2: Buat Database MySQL

1. Di panel InfinityFree, klik **Accounts** → pilih akun Anda
2. Klik **MySQL Databases** di sidebar kiri
3. Masukkan:
   - **Database Name**: `batako_maros` (atau nama lain)
   - **Username**: `batako_user` (atau nama lain)
   - **Password**: buat password kuat
4. Klik **Create Database**
5. **Catat info berikut** (akan dipakai di Step 4 & 5):
   ```
   Database Name: epiz_XXXXX_batako_maros
   Username: epiz_XXXXX_batako_user
   Password: [password yang dibuat]
   Server Host: sqlXXX.infinityfree.com  (atau localhost)
   ```

#### Step 3: Upload File

##### Opsi A: Via File Manager (Mudah)

1. Di panel InfinityFree, klik **File Manager**
2. Buka folder `htdocs`
3. Upload semua file project **KECUALI**:
   - Folder `vendor/` (akan diinstall ulang)
   - File `.env` (jangan upload, akan dibuat manual)
4. Atau upload file `zip` project → klik kanan → **Extract**

##### Opsi B: Via FTP (Cepat untuk file banyak)

1. Download **FileZilla** (gratis) dari [https://filezilla-project.org](https://filezilla-project.org)
2. Di panel InfinityFree, buka **FTP Accounts**
3. Buat FTP account (atau gunakan default)
4. Konek via FileZilla:
   ```
   Host: ftp.infinityfree.com
   Username: epiz_XXXXX
   Password: [password FTP]
   Port: 21
   ```
5. Upload semua file project ke folder `htdocs/`
6. **Jangan upload folder `vendor/`**

#### Step 4: Konfigurasi .env

Setelah file terupload, buat file `.env` di folder `htdocs/` melalui **File Manager**:

1. Klik **New File** → namai: `.env`
2. Isi dengan konfigurasi database production:

```env
DB_HOST=sqlXXX.infinityfree.com
DB_NAME=epiz_XXXXX_batako_maros
DB_USER=epiz_XXXXX_batako_user
DB_PASS=password_yang_dibuat

APP_URL=http://epiz_XXXXX.infinityfreeapp.com
APP_NAME=Percetakan Batako Maros
APP_ENV=production
```

> **Ganti**:
> - `sqlXXX` dengan server host dari Step 2
> - `epiz_XXXXX` dengan account ID Anda
> - `password_yang_dibuat` dengan password MySQL

#### Step 5: Import Database

Ada 2 cara:

##### Cara A: Via phpMyAdmin (Mudah)

1. Di panel InfinityFree, klik **phpMyAdmin**
2. Login dengan username & password database (dari Step 2)
3. Klik database Anda di sidebar kiri
4. Klik tab **SQL**
5. Klik **Choose File** → pilih file `database.sql` dari project
6. Klik **Go**

##### Cara B: Via Command (Alternatif)

Buka **MySQL Databases** di panel → klik **Run SQL Query** → paste isi `database.sql` → Execute.

#### Step 6: Install Composer Dependencies

InfinityFree **tidak punya akses command line (SSH)** untuk menjalankan `composer install`.

Solusi: **Install composer di laptop Anda, lalu upload folder `vendor/` hasil install-an.**

```bash
# Di laptop/komputer Anda:
cd /path/to/project

# Hapus vendor lama (jika ada)
rm -rf vendor/

# Install ulang (production mode — lebih ringan)
composer install --no-dev --optimize-autoloader

# Upload folder vendor/ yang baru ke server via FTP/File Manager
```

Upload folder `vendor/` ke folder `htdocs/` di server.

#### Step 7: Seed Data Awal

Karena tidak bisa akses SSH, seed data bisa dilakukan melalui **phpMyAdmin**:

1. Buka **phpMyAdmin** dari panel InfinityFree
2. Login dengan kredensial database
3. Klik database Anda
4. Buka tab **SQL**
5. Buka file `seeder.php` di laptop Anda
6. **Jalankan query SQL berikut** (sudah ada di `database.sql` — sudah terimport):

   Pastikan tabel sudah terisi dengan menjalankan query:

   ```sql
   -- Cek isi tabel users
   SELECT * FROM users;
   ```

   Jika masih kosong, jalankan query INSERT dari file `database.sql` bagian SEED DATA:

   ```sql
   INSERT INTO pekerja (nama_pekerja, tarif_per_sak, status) VALUES
   ('Ahmad Fauzi', 65000, 'aktif'),
   ('Budi Santoso', 65000, 'aktif'),
   ('Herman', 70000, 'aktif'),
   ('Rudi Hartono', 65000, 'aktif'),
   ('Slamet Riyadi', 65000, 'nonaktif');

   INSERT INTO stok (ukuran_batako, total_produksi, total_penjualan, stok_tersedia) VALUES
   ('standar', 0, 0, 0),
   ('besar', 0, 0, 0);
   ```

   **Untuk user (password terhash):**
   ```sql
   INSERT INTO users (name, username, email, password, role) VALUES
   ('Pemilik Batako', 'pemilik', 'pemilik@batakomaros.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pemilik'),
   ('Operator 1', 'operator', 'operator@batakomaros.com', '$2y$10$OQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi92IXUNpkj', 'operator');
   ```

   > ⚠️ Hash bcrypt di atas adalah **placeholder** — untuk production,
   > jalankan `php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"`
   > di laptop, lalu ganti hash-nya.

#### Step 8: Akses Website

1. Buka browser → `http://epiz_XXXXX.infinityfreeapp.com`
2. Login dengan:
   - **Pemilik**: username `pemilik` / password `admin123`
   - **Operator**: username `operator` / password `operator123`

#### Step 9: Troubleshooting

| Masalah | Solusi |
|---------|--------|
| **404 Not Found** | Pastikan `.htaccess` terupload. Cek apakah `mod_rewrite` aktif (InfinityFree sudah aktif default) |
| **Koneksi database gagal** | Cek `DB_HOST`, `DB_USER`, `DB_PASS` di `.env`. Pastikan server host benar (bukan `localhost`) |
| **Blank page** | Cek error: tambahkan `ini_set('display_errors', 1); error_reporting(E_ALL);` di `index.php` |
| **CSS/JS tidak muncul** | Pastikan folder `assets/` terupload. Cek `APP_URL` di `.env` |
| **Login gagal** | Password hash mungkin berbeda. Gunakan PHP seed via phpMyAdmin dengan hash yg benar |
| **Composer autoload error** | Upload ulang folder `vendor/` |
| **Database connection timeout** | InfinityFree terkadang lambat. Coba refresh. Cek server host di panel |

---

### Alternatif Hosting Berbayar (Jika Ingin Lebih Stabil)

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
