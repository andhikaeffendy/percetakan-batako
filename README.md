# 🧱 Sistem Informasi Operasional Percetakan Batako Maros

Website operasional untuk **UMKM Percetakan Batako Maros, Ambon, Maluku** — menggantikan pencatatan manual menjadi pencatatan digital yang rapi, terukur, dan mudah dipantau oleh pemilik.

---

## 📋 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Arsitektur Project](#-arsitektur-project)
- [Role & Akses](#-role--akses)
- [Install di Windows](#-install-di-windows--step-by-step)
  - [1. Install XAMPP](#1-install-xampp-apache--mysql--php)
  - [2. Install Composer](#2-install-composer)
  - [3. Install Git](#3-install-git-opsional-untuk-clone)
  - [4. Install VS Code](#4-install-vs-code-editor-kode)
  - [5. Buka Project di VS Code](#5-buka-project-di-vs-code)
  - [6. Terminal di VS Code](#6-terminal-di-vs-code)
- [Jalankan Project di Windows](#-jalankan-project-di-windows)
  - [Step 1: Download](#step-1-download-project)
  - [Step 2: Dependencies](#step-2-install-dependencies)
  - [Step 3: Setup .env](#step-3-setup-env)
  - [Step 4: Database](#step-4-buat-database)
  - [Step 5: Seed Data](#step-5-seed-data)
  - [Step 6: Jalankan](#step-6-jalankan)
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
| 3 | **Dashboard KPI** | Produksi, Penjualan, Stok, Pendapatan + grafik interaktif Chart.js |
| 4 | **CRUD Lengkap** | Bahan Baku, Produksi, Penjualan, Tenaga Kerja, Pengeluaran |
| 5 | **Stok Otomatis Produk** | Dihitung dari total produksi − total penjualan, disimpan di `stok_produk` |
| 6 | **Stok Otomatis Bahan** | Pembelian − penggunaan bahan, disimpan di `stok_bahan_baku` (Semen dalam Sak, Pasir dalam m³) |
| 7 | **Validasi Stok** | Penjualan tidak boleh melebihi stok tersedia |
| 8 | **Gaji Otomatis** | Total sak semen × tarif per sak − panjar per pekerja, tersimpan per periode |
| 9 | **Pengeluaran & Laba Bersih** | Catat pengeluaran operasional + kategori, hitung laba bersih |
| 10 | **Laporan** | Produksi vs Penjualan, Keuangan, Gaji, Persediaan + Chart.js |
| 11 | **PDF & Excel** | Cetak laporan PDF (Dompdf) + Export data Excel (PhpSpreadsheet) |
| 12 | **Responsive** | Nyaman di laptop/PC dan HP (Bootstrap 5) |
| 13 | **Pagination + Filter** | Tabel dapat difilter tanggal/ukuran/search & dipaginasi |
| 14 | **Flash Messages** | Notifikasi sukses/gagal setelah setiap aksi CRUD |

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
│        CONFIG (Database + .env)         │  ← Koneksi & environment
├─────────────────────────────────────────┤
│        DATABASE (MySQL)                 │  ← 11 tabel relasional
├─────────────────────────────────────────┤
│        MIGRATIONS (SQL)                 │  ← Migrasi schema additive untuk DB existing
└─────────────────────────────────────────┘
```

### Alur Data

```
Operator Input → tabel produksi/penjualan/bahan_baku/pengeluaran → updateStok() / updateStokBahan()
                                                                    ↓
                                    stok_produk (produksi − penjualan) & stok_bahan_baku (pembelian − penggunaan)
                                                                    ↓
Pemilik Dashboard ← stok + produksi + penjualan + pengeluaran (KPI + Chart.js)
Pemilik Laporan   ← Produksi vs Penjualan, Keuangan, Gaji, Persediaan
Pemilik Gaji      ← produksi.jumlah_sak_semen × pekerja.tarif_per_sak − panjar
```

### Database Relasi

```text
users ────┐
          ├──→ bahan_baku (operator_id)
          ├──→ produksi (operator_id) ──→ pekerja (pekerja_id)
          ├──→ penjualan (operator_id)
          ├──→ pengeluaran (operator_id, kategori_pengeluaran_id)
          └──→ (session login)

pekerja ──→ gaji (pekerja_id)
stok_produk      ←── produksi + penjualan (aggregate per ukuran)
stok_bahan_baku  ←── bahan_baku pembelian − penggunaan (per jenis)
stok             ←── legacy (dijaga sinkron untuk kompatibilitas laporan lama)
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
| | Data Pengeluaran | CRUD + filter kategori + total |
| | Persediaan | Monitoring stok produk & bahan + grafik |
| | Perhitungan Gaji | Hitung & simpan gaji per periode |
| | Laporan Produksi | Grafik + tabel produksi vs penjualan + PDF |
| | Laporan Keuangan | Total pendapatan + grafik bulanan + PDF |
| | Laporan Gaji | Detail per pekerja + PDF |
| **Operator** | Beranda | Ringkasan & quick action cards |
| | Input Bahan Baku | Form input harian (pembelian/penggunaan) |
| | Input Produksi | Form input harian |
| | Input Penjualan | Form input harian + auto-hitung total |
| | Input Pengeluaran | Form input harian (listrik, air, dll) |

---

## 💿 Install di Windows — Step by Step

Semua software di bawah **WAJIB** diinstall satu per satu.

---

### 1. Install XAMPP (Apache + MySQL + PHP)

XAMPP adalah paket yang berisi Apache (web server), MySQL (database), dan PHP.

**Requirement PHP:** project ini kompatibel dengan **PHP 8.0 – 8.3** (diuji pada PHP 8.2.x). Jangan pakai PHP 7.x — dompdf 3.x (dependency PDF) sudah tidak mendukungnya.

**Download & Install:**
1. Buka https://www.apachefriends.org
2. Klik **Download** (versi terbaru untuk Windows)
3. Buka file `.exe` yang terdownload -> **Next -> Next -> Finish**
4. Setelah selesai, buka **XAMPP Control Panel** (dari Start Menu / Desktop)

**Jalankan Apache dan MySQL:**
1. Di XAMPP Control Panel, klik **Start** pada:
   - Apache (tunggu jadi hijau)
   - MySQL (tunggu jadi hijau)

> Jika port 80 error: Klik **Config** di baris Apache -> **httpd.conf** -> cari `Listen 80` -> ganti `Listen 8080` -> Start ulang Apache.
> Jika MySQL tidak bisa start: Klik **Config** di baris MySQL -> **my.ini** -> ganti port `3306` ke `3307` -> Start ulang.

**Verifikasi:** Buka browser -> `http://localhost` -> halaman orange XAMPP muncul = berhasil.

---

### 2. Install Composer

Composer untuk install library PHP (Dompdf, PhpSpreadsheet, Dotenv).

**Download & Install:**
1. Buka https://getcomposer.org
2. Klik **Download** -> **Composer-Setup.exe**
3. Jalankan installer
4. Saat pilih PHP path, **pilih**: `C:\xampp\php\php.exe`
5. Next -> Install -> Finish

**Verifikasi:** Buka CMD -> ketik `php -v` dan `composer -V` -> muncul versi.

---

### 3. Install Git (opsional, untuk clone)

1. Buka https://git-scm.com
2. Download -> jalankan installer -> Next -> Finish
3. Verifikasi: `git --version`

---

### 4. Install VS Code (Editor Kode)

VS Code adalah editor kode gratis dari Microsoft, sangat cocok untuk PHP.

**Download & Install:**
1. Buka https://code.visualstudio.com
2. Klik **Download for Windows** -> jalankan `.exe`
3. Centang semua opsi saat install (terutama **Add to PATH**)
4. Next -> Install -> Finish

**Install Extension Wajib:**

Buka VS Code -> klik icon Extensions (Ctrl+Shift+X) -> cari & install:

| Extension | Fungsi |
|-----------|--------|
| **PHP Intelephense** | Autocomplete, error detection, code navigation PHP |
| **Laravel Extra Intellisense** | (auto-install bareng Intelephense) |
| **Prettier** | Format kode otomatis (HTML, CSS, JS, PHP) |
| **Material Icon Theme** | Icon folder & file biar rapi |
| **Live Server** | Preview HTML live (klik kanan -> Open with Live Server) |
| **GitLens** | Lihat history git langsung di editor |

**Verifikasi:** Buka VS Code -> ketik `Ctrl+Shift+P` -> "PHP: Open Settings" -> jika muncul = PHP extension aktif.

---

### 5. Buka Project di VS Code

**Cara 1 - Dari CMD:**
```cmd
cd C:\xampp\htdocs\percetakan-batako
code .
```

**Cara 2 - Dari VS Code:**
1. Buka VS Code
2. File -> Open Folder (`Ctrl+K Ctrl+O`)
3. Pilih `C:\xampp\htdocs\percetakan-batako`
4. Klik **Select Folder**

**Yang harus muncul:**
- Sidebar kiri: daftar folder (`config/`, `helpers/`, `layouts/`, `pemilik/`, `operator/`, `assets/`)
- Klik file `.php` -> syntax berwarna (ada highlight)
- `Ctrl+P` -> ketik nama file -> langsung loncat ke file itu

---

### 6. Terminal di VS Code

VS Code punya terminal built-in. Bisa buka CMD/PowerShell langsung dari editor:

- `Ctrl+\`` (backtick) -> buka terminal
- Ketik perintah:
```cmd
composer install
php seeder.php
php -S localhost:8000
```

> Semua perintah project bisa dijalankan dari terminal VS Code ini — tidak perlu pindah ke CMD.

---

## 🚀 Jalankan Project di Windows

### Step 1: Download Project

**Clone dari GitHub:**
```cmd
cd C:\xampp\htdocs
git clone https://github.com/andhikaeffendy/percetakan-batako.git
cd percetakan-batako
```

**Atau download ZIP:** Buka GitHub repo -> Code -> Download ZIP -> extract ke `C:\xampp\htdocs\percetakan-batako\`

### Step 2: Install Dependencies

Buka CMD:
```cmd
cd C:\xampp\htdocs\percetakan-batako
composer install
```
Tunggu 1-3 menit. Folder `vendor/` akan terisi.

> **Penting:** jalankan `composer install` **tanpa flag tambahan**. Jangan pakai `composer update --ignore-platform-*` — itu akan menaikkan versi library (PhpSpreadsheet/zipstream) yang butuh PHP 8.3+ dan memunculkan error di laptop dengan PHP 8.2. `composer.lock` di repo ini sudah dikunci agar kompatibel dengan PHP 8.2 (dompdf 3.1.x, PhpSpreadsheet 1.29.x, zipstream 2.4.x).

### Step 3: Setup .env

Copy `.env.example` ke `.env`:
```cmd
copy .env.example .env
```

### Step 4: Buat Database

`database.sql` sudah berisi `CREATE DATABASE IF NOT EXISTS db_batako_maros` + `USE db_batako_maros`, jadi satu kali import langsung membuat database, tabel, dan data awal.

**Cara A — via phpMyAdmin (paling mudah):**
1. Buka `http://localhost/phpmyadmin`
2. Klik tab **SQL** (tanpa memilih database apa pun)
3. Klik **Choose File** -> pilih file `database.sql` dari folder project
4. Klik **Go** / **Import**
5. Verifikasi: di panel kiri phpMyAdmin harus muncul database `db_batako_maros` berisi 11 tabel

**Cara B — via CMD (MySQL CLI):**
```cmd
cd C:\xampp\htdocs\percetakan-batako
mysql -u root < database.sql
```
> Jika MySQL XAMPP Anda memakai password, tambahkan `-p` lalu ketik password: `mysql -u root -p < database.sql`
> Verifikasi: `mysql -u root -e "SHOW TABLES FROM db_batako_maros;"` harus menampilkan 11 tabel.

**Penting:** nama database di `.env` (`DB_NAME=db_batako_maros`) WAJIB sama persis dengan database hasil import. Jika Anda mengubah nama di `.env`, sesuaikan juga hasil import (atau buat DB dengan nama itu).

### Step 5: Seed Data

```cmd
php seeder.php
```

Seeder membuat akun awal (pemilik & operator) dan contoh data. Jalankan **hanya pada instalasi baru** — jangan dijalankan pada database yang sudah berisi data produksi (seeder memakai `TRUNCATE` untuk beberapa tabel).

Verifikasi berhasil: muncul pesan sukses tanpa error, lalu:
```cmd
mysql -u root -e "SELECT username, role FROM db_batako_maros.users;"
```
Harus menampilkan `pemilik` dan `operator`.

> 💡 **Lewati Step 5 jika pakai `deploy_all.sql`** — file itu sudah berisi user + data awal. `deploy_all.sql` dipakai untuk deploy hosting (lihat bagian Deploy), bukan instal lokal.

### Step 6: Jalankan

Ada dua cara menjalankan:

**Cara A — Apache XAMPP (default, tanpa terminal tambahan):**
1. Pastikan **Apache** dan **MySQL** menyala di XAMPP Control Panel
2. Letakkan project di `C:\xampp\htdocs\percetakan-batako\`
3. Buka browser -> `http://localhost/percetakan-batako`

**Cara B — PHP Built-in Server (port 8000):**
```cmd
cd C:\xampp\htdocs\percetakan-batako
php -S localhost:8000
```
Buka browser -> `http://localhost:8000`

Kedua cara menghasilkan aplikasi yang sama. Pilih salah satu.

Login: `pemilik` / `admin123` atau `operator` / `operator123`

> 💡 **Alternatif Linux/macOS (tanpa XAMPP):** jalankan `./run-local.sh` — script otomatis membuat DB, menjalankan migrasi, mengisi seed, dan menyalakan server `http://localhost:8000`.

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

> ℹ️ **Struktur folder di atas = kondisi final repo.** Tidak ada file PHP/JS/CSS duplikat di root repo — semua halaman berada di `pemilik/`, `operator/`, dan `assets/`. Saat upload ke hosting, pastikan memakai file dari folder-folder ini, bukan file root lama.

---

## 📁 Struktur Folder Lengkap

```
umkm-percetakan-batako/
├── .env.example              # Template konfigurasi environment
├── .gitignore                # File yang di-exclude dari Git
├── .htaccess                 # URL rewrite + security rules
├── composer.json             # PHP dependencies
├── database.sql              # Struktur database lengkap (11 tabel, untuk instal lokal)
├── deploy_all.sql            # Schema + data awal (untuk deploy hosting, 1 file)
├── seeder.php                # Seed data awal (users, contoh produksi, dll)
├── run-local.sh              # Bootstrap lokal: buat DB + migrasi + jalankan server
├── index.php                 # Root redirect (cek session → arahkan)
├── login.php                 # Halaman login (username/email + password)
├── logout.php                # Destroy session → redirect login
│
├── config/
│   └── database.php          # Koneksi PDO + .env loader
│
├── helpers/
│   ├── auth.php              # Auth (login, session, role, flash, format, XSS)
│   └── functions.php         # Bisnis logic (updateStok, updateStokBahan, validasiSatuanBahan, CSRF)
│
├── migrations/
│   └── revisi_persediaan.sql # Migrasi additive untuk DB existing (jenis_transaksi, stok_bahan_baku, stok_produk)
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
│   ├── pengeluaran.php       # CRUD Pengeluaran + kategori
│   ├── gaji.php              # Hitung gaji per periode + simpan ke DB
│   ├── persediaan.php        # Monitoring stok produk & bahan + grafik
│   ├── laporan_produksi.php  # Grafik + tabel produksi vs penjualan + PDF
│   ├── laporan_keuangan.php  # Total pendapatan + grafik bulanan + PDF
│   └── laporan_gaji.php      # Detail gaji per pekerja + PDF
│
├── operator/                 # === HALAMAN OPERATOR ===
│   ├── index.php             # Beranda + quick action cards + ringkasan
│   ├── input_bahan_baku.php  # Form input bahan baku harian (pembelian/penggunaan)
│   ├── input_produksi.php    # Form input produksi + update stok otomatis
│   ├── input_penjualan.php   # Form input penjualan + validasi stok
│   └── input_pengeluaran.php # Form input pengeluaran harian
│
├── assets/
│   ├── css/
│   │   └── style.css         # Full styling (login, sidebar, cards, tables, responsive)
│   └── js/
│       └── app.js            # Sidebar toggle, auto-hitung, delete confirm
│
├── tests/
│   └── TestRunner.php         # Automated test suite (assert-based, ~87 checks)
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

## 🎨 Desain UI — Industrial Soft UI (2026-08)

| Komponen | Warna / Spesifikasi |
|----------|---------------------|
| Background / Concrete | `#E8E3DA` |
| Surface (card) | `#FFFFFF` |
| Shadow lembut | `#B8B2A8` |
| Teks / Ink | `#27313A` |
| Primary (CTA, aksi utama) | Terracotta `#C65A32` |
| Secondary / Info | Cobalt `#2F67C7` |
| Success / Aman | `#287A5A` |
| Warning / Menipis | `#B67A12` |
| Danger / Habis / Delete | `#B84444` |
| Font | **Inter** via Google Fonts |
| Icons | **Bootstrap Icons** 1.11 |

Prinsip:
- Soft-neomorphism dipakai untuk hierarchy visual; CTA, input, focus state, tabel, dan status tetap high-contrast.
- Tidak ada glass effect, gradient dekoratif, atau animasi berat.
- Status tidak dibedakan hanya dengan warna (ada ikon/teks pendamping).
- Semua modal create/edit memakai `modal-dialog-centered modal-dialog-scrollable`.
- Merah hanya untuk aksi destruktif/error; CTA simpan/tambah memakai `btn-primary` terracotta.
- Emoji tidak dipakai di UI — semua ikon memakai Bootstrap Icons.

---

## 🧪 Testing Checklist

Semua skenario di `TESTING_CHECKLIST.md` sudah diuji dan PASS ✅. Lihat file tersebut untuk detail lengkap (40+ skenario: login & auth, CRUD, stok otomatis, validasi, gaji, laporan, pengeluaran & laba bersih, export, UI desktop/mobile).

Suite otomatis `tests/TestRunner.php` berisi ~87 assertion checks untuk koneksi DB, struktur tabel, validasi seed, logika bisnis (stok, gaji, satuan bahan), foreign key, helper format, dan auth.

---

## 🔧 Troubleshooting

| Masalah | Solusi |
|---------|--------|
| **"Koneksi database gagal"** | Pastikan MySQL running di XAMPP. Cek `DB_USER` dan `DB_PASS` di `.env` |
| **Blank page / Error 500** | Cek versi PHP (harus 8.0 – 8.3). Jalankan `composer install` ulang |
| **"Composer: php >= 8.3 required / php-64bit"** | Versi library ter-lock butuh PHP 8.3. Solusi: aktifkan ekstensi `gd` + `zip` di `php.ini` XAMPP (cari `;extension=gd` dan `;extension=zip`, hilangkan titik koma), lalu jalankan `composer install` lagi |
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

Proyek memiliki **automated test suite** (`tests/TestRunner.php`, ~87 checks) yang mencakup:
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

#### Step 5: Import Database

1. Di panel InfinityFree, klik **"phpMyAdmin"**
2. Login dengan **DB_USER** dan **DB_PASS** dari Step 1
   ```
   Username: epiz_XXXXX_batako_user
   Password: [password]
   ```
3. Setelah masuk, klik nama database di sidebar kiri (`epiz_XXXXX_batako_maros`)
4. Klik tab **"SQL"** di toolbar atas
5. Klik **"Choose File"** → pilih file **`deploy_all.sql`** dari folder project
6. Klik **"Go"** — tunggu query selesai
7. ✅ Selesai! Seluruh tabel (users, pekerja, kategori_pengeluaran, bahan_baku, produksi, penjualan, pengeluaran, gaji, stok, stok_bahan_baku, stok_produk) sudah terbuat + data awal terisi

> ⚠️ **Hanya 1 file: `deploy_all.sql`.** Jangan pakai `database.sql` di hosting — file itu berisi `CREATE DATABASE` yang tidak diizinkan InfinityFree.

> **Database sudah ada (upgrade dari versi lama)?** Jangan import ulang (akan menimpa data). Cukup jalankan file `migrations/revisi_persediaan.sql` sekali di phpMyAdmin untuk menambah kolom `jenis_transaksi` pada `bahan_baku` serta tabel `stok_bahan_baku` dan `stok_produk`.

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
| **❌ Login gagal** | Password hash salah | Import ulang `deploy_all.sql` via phpMyAdmin |
| **❌ "Call to undefined function validasiSatuanBahan()"** | Helper `helpers/functions.php` versi lama ter-upload | Upload ulang `helpers/functions.php` terbaru (fitur stok bahan membutuhkannya) |
| **❌ "Unknown column 'jenis_transaksi'" / tabel `stok_*` tidak ada** | DB masih versi lama | Jalankan `migrations/revisi_persediaan.sql` di phpMyAdmin |
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
