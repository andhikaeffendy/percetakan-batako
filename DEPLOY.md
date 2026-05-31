# 🚀 DEPLOY KE INFINITYFREE

## Checklist Deploy

### □ 1. Buat Akun
- [ ] Buka https://www.infinityfree.com
- [ ] Daftar (email + password)
- [ ] Verifikasi email
- [ ] Login → **Accounts** → **Create Account**
- [ ] Catat: `Username: epiz_XXXXX`, `Domain: epiz_XXXXX.infinityfreeapp.com`

### □ 2. Buat Database MySQL
- [ ] Klik **MySQL Databases** di sidebar
- [ ] Isi:
  ```
  Database Name: batako_maros
  Username: batako_user
  Password: [buat password kuat]
  ```
- [ ] Klik **Create**
- [ ] Catat info:
  ```
  DB_NAME: epiz_XXXXX_batako_maros
  DB_USER: epiz_XXXXX_batako_user
  DB_PASS: [password]
  DB_HOST: sqlXXX.infinityfree.com  (cek di panel)
  ```

### □ 3. Siapkan File untuk Upload

Di laptop/komputer, jalankan:

```bash
# 1. Masuk ke folder project
cd /path/to/umkm-percetakan-batako

# 2. Install composer (production mode — lebih ringan)
rm -rf vendor/
composer install --no-dev --optimize-autoloader

# 3. Hapus file yang tidak perlu diupload
rm -f .env              # JANGAN DIUPLOAD (buat baru di server)
rm -rf assets/design_ref/  # Hapus file desain
rm -f analisa.jpeg
```

File yang **WAJIB** diupload:
```
├── .htaccess
├── .env.example         # (sebagai referensi)
├── index.php
├── login.php
├── logout.php
├── composer.json
├── composer.lock
├── database.sql
├── seed_production.sql
├── config/
│   └── database.php
├── helpers/
│   ├── auth.php
│   └── functions.php
├── layouts/
│   ├── header.php
│   ├── footer.php
│   ├── sidebar_operator.php
│   └── sidebar_pemilik.php
├── operator/
│   ├── index.php
│   ├── input_bahan_baku.php
│   ├── input_produksi.php
│   └── input_penjualan.php
├── pemilik/
│   ├── dashboard.php
│   ├── bahan_baku.php
│   ├── produksi.php
│   ├── penjualan.php
│   ├── tenaga_kerja.php
│   ├── gaji.php
│   ├── laporan_produksi.php
│   ├── laporan_keuangan.php
│   └── laporan_gaji.php
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── vendor/              # Hasil composer install
```

### □ 4. Upload File ke Server
- [ ] Buka **File Manager** di panel InfinityFree
- [ ] Masuk ke folder `htdocs/`
- [ ] **Upload semua file** dari daftar di atas
- [ ] Cara:
  - Zip semua file → upload zip → **Extract**
  - ATAU upload 1 per 1 via **New File** + copy-paste

### □ 5. Buat File .env di Server
- [ ] Di **File Manager**, klik kanan → **New File**
- [ ] Nama file: `.env`
- [ ] Isi:
```env
DB_HOST=sqlXXX.infinityfree.com
DB_NAME=epiz_XXXXX_batako_maros
DB_USER=epiz_XXXXX_batako_user
DB_PASS=password_yang_dibuat

APP_URL=http://epiz_XXXXX.infinityfreeapp.com
APP_NAME=Percetakan Batako Maros
APP_ENV=production
```

### □ 6. Import Database
- [ ] Di panel, klik **phpMyAdmin**
- [ ] Login dengan credential database
- [ ] Klik database di sidebar kiri
- [ ] Tab **SQL** → **Choose File** → pilih `database.sql`
- [ ] Klik **Go**
- [ ] Ulangi untuk `seed_production.sql`

### □ 7. Verifikasi
- [ ] Buka: `http://epiz_XXXXX.infinityfreeapp.com`
- [ ] Login Pemilik: `pemilik` / `admin123`
- [ ] Login Operator: `operator` / `operator123`
- [ ] Cek semua halaman berfungsi
- [ ] Cek export PDF/Excel

### □ 8. Troubleshooting Cepat

| Masalah | Solusi |
|---------|--------|
| **500 Internal Server Error** | Tambah di `index.php` baris 2: `ini_set('display_errors', 1);` |
| **Blank page** | Cek error log di panel: **Error Log** |
| **CSS/JS broken** | Cek `APP_URL` di `.env`. Coba akses langsung file CSS |
| **Database connection** | Cek `DB_HOST` — InfinityFree kadang pakai `sqlXXX.infinityfree.com` bukan `localhost` |
| **.htaccess not working** | InfinityFree sudah enable mod_rewrite. Cek nama file (titik di depan) |
| **Login gagal** | Password hash mungkin beda. Ulangi import `seed_production.sql` |
| **Composer class not found** | Hapus `vendor/` → jalankan `composer install --no-dev` di laptop → upload ulang |
| **Session not starting** | Pastikan folder `htdocs/` jadi root. Session path harus writable (otomatis di InfinityFree) |
