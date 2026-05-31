# Deploy ke InfinityFree — Tutorial Lengkap

Panduan ini berdasarkan pengalaman deploy nyata ke **InfinityFree** (gratis).

---

## Ringkasan

| Item | Nilai |
|------|-------|
| URL | `http://percetakan-batako.page.gd` |
| Hosting | InfinityFree (gratis) |
| PHP | 8.0+ |
| MySQL | via phpMyAdmin |
| File manager | htdocs/ |

---

## 1. Buat Akun & Database

### 1.1 Daftar InfinityFree

1. Buka https://www.infinityfree.com → **Get Free Hosting**
2. Isi email + password → verifikasi email
3. Login → buat akun baru (klik **Accounts** → **Create Account**)
4. Catat:
   - **Account ID**: `if0_XXXXX` (pojok kanan atas)
   - **Domain**: `epiz_XXXXX.infinityfreeapp.com` (atau domain custom)

### 1.2 Buat Database MySQL

1. Panel → **MySQL Databases** (sidebar kiri)
2. Klik **Create Database**, isi:

   | Field | Isi |
   |-------|-----|
   | Database Name | `batako_maros` |
   | Username | `batako_user` atau biarkan default |
   | Password | Buat password (catat!) |

3. **Create Database**

4. Info yang muncul (catat):

   ```
   Database: if0_XXXXX_batako_maros
   Username: if0_XXXXX             ← USERNAME INI
   Password: [yang dibuat]         ← PASSWORD INI
   ```

   > ⚠️ InfinityFree otomatis tambah prefix `if0_XXXXX_` di nama database.
   > Tapi **username MySQL** adalah `if0_XXXXX` (TANPA `_batako_user` di belakang).

---

## 2. Import Database

### 2.1 Buka phpMyAdmin

1. Panel → **MySQL Databases** → klik **phpMyAdmin**
2. Login:
   - **Username**: `if0_XXXXX` (username MySQL)
   - **Password**: (password MySQL)

### 2.2 Import deploy_all.sql

1. Klik database `if0_XXXXX_batako_maros` di sidebar kiri
2. Tab **SQL** → **Choose File** → pilih `deploy_all.sql`
3. Klik **Go**

> **Hanya 1 file.** `deploy_all.sql` berisi 7 tabel + data user + pekerja + stok.

---

## 3. Upload File Project

### 3.1 Siapkan di laptop

```bash
cd project
composer install --no-dev --optimize-autoloader
```

### 3.2 Upload ke htdocs

1. Panel → **File Manager** → masuk folder `htdocs`
2. Upload **semua file & folder** project:

```
htdocs/
├── index.php
├── login.php
├── logout.php
├── .htaccess
├── composer.json
├── composer.lock
├── config/database.php
├── helpers/auth.php
├── helpers/functions.php
├── layouts/header.php
├── layouts/footer.php
├── layouts/sidebar_pemilik.php
├── layouts/sidebar_operator.php
├── pemilik/dashboard.php
├── pemilik/bahan_baku.php
├── pemilik/produksi.php
├── pemilik/penjualan.php
├── pemilik/tenaga_kerja.php
├── pemilik/gaji.php
├── pemilik/laporan_produksi.php
├── pemilik/laporan_keuangan.php
├── pemilik/laporan_gaji.php
├── operator/index.php
├── operator/input_bahan_baku.php
├── operator/input_produksi.php
├── operator/input_penjualan.php
├── assets/css/style.css
├── assets/js/app.js
└── vendor/
```

> ⚠️ **Pastikan file di `htdocs/` langsung** (bukan di subfolder seperti `htdocs/project/`).

---

## 4. Buat File .env

1. File Manager → folder `htdocs` → **New File** → nama `.env`
2. Edit → paste:

```env
DB_HOST=sqlXXX.infinityfree.com
DB_NAME=if0_XXXXX_batako_maros
DB_USER=if0_XXXXX
DB_PASS=password_kamu

APP_URL=http://domain_kamu
APP_NAME="Percetakan Batako Maros"
APP_ENV=production
```

3. Ganti:
   - `sqlXXX` → server host dari panel MySQL (contoh: `sql105`)
   - `if0_XXXXX` → Account ID
   - `password_kamu` → password MySQL
   - `domain_kamu` → URL website

4. **Save**

> ⚠️ `APP_NAME` **WAJIB pakai tanda kutip** karena ada spasi.

---

## 5. Akses Website

Buka browser → `http://domain-kamu`

Login:

| Role | Username | Password |
|------|----------|----------|
| Pemilik | `pemilik` | `admin123` |
| Operator | `operator` | `operator123` |

---

## 6. Troubleshooting

| Error | Penyebab | Solusi |
|-------|----------|--------|
| **HTTP 500** | .htaccess terlalu kompleks | Pakai .htaccess minimal (cuma rewrite + -Indexes) |
| **"No such file or directory"** | Koneksi via socket, bukan TCP | `config/database.php` harus pakai `;port=3306` |
| **"Access denied for user"** | Username/password salah | Username MySQL = `if0_XXXXX` (tanpa `_batako_user`) |
| **Login gagal** | Hash bcrypt tidak cocok | Import ulang `deploy_all.sql` |
| **.env tidak terbaca** | Nilai dengan spasi tidak dikutip | `APP_NAME="Nama Aplikasi"` (dengan kutip) |
| **404 Not Found** | File di subfolder | Pindahkan semua file ke `htdocs/` langsung |
| **CSS/JS rusak** | APP_URL salah | Cek `APP_URL` di `.env` |
| **phpMyAdmin blank** | Cache browser | Refresh halaman atau buka private window |

---

## 7. Catatan Penting

- **Jangan pakai `database.sql`** (punya `CREATE DATABASE` — tidak diizinkan di InfinityFree)
- **Pakai `deploy_all.sql`** untuk import database (1 file untuk semua)
- **Config/database.php** sudah punya fallback hardcode — jika `.env` gagal, tetap jalan
- **Vendor wajib diupload** — InfinityFree tidak bisa `composer install`
