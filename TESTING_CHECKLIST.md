# Testing Checklist — Percetakan Batako Maros

| No | Modul | Skenario | Langkah Uji | Expected Result | Status |
|----|-------|----------|-------------|-----------------|--------|
| 1 | Login | Login Pemilik | Buka login.php → username: pemilik, password: admin123 → klik Masuk | Redirect ke /pemilik/dashboard.php | ✅ |
| 2 | Login | Login Operator | Buka login.php → username: operator, password: operator123 → klik Masuk | Redirect ke /operator/index.php | ✅ |
| 3 | Login | Password salah | Masukkan username/password salah | Alert error "Username atau password salah" | ✅ |
| 4 | Auth | Password Hash | Cek tabel users → field password | Password tersimpan dalam hash bcrypt ($2y$) | ✅ |
| 5 | Auth | Operator akses halaman Pemilik | Login sebagai operator → buka /pemilik/dashboard.php | Redirect ke /operator/index.php | ✅ |
| 6 | Auth | Session logout | Klik Logout | Session dihapus, redirect ke login.php | ✅ |
| 7 | Operator | Input Bahan Baku | Login operator → Input Bahan Baku → isi form → Simpan | Data tersimpan di tabel bahan_baku | ✅ |
| 8 | Operator | Input Produksi | Login operator → Input Produksi → isi form → Simpan | Data tersimpan + stok bertambah | ✅ |
| 9 | Operator | Input Penjualan | Login operator → Input Penjualan → isi form → Simpan | Total otomatis terhitung + stok berkurang | ✅ |
| 10 | Stok | Stok otomatis berubah | Setelah input produksi/penjualan → cek tabel stok | Stok = total_produksi - total_penjualan | ✅ |
| 11 | Validasi | Penjualan melebihi stok | Input penjualan > stok tersedia | Warning + data tidak disimpan | ✅ |
| 12 | Pemilik | Dashboard KPI | Login pemilik → Dashboard | Tampil Produksi, Penjualan, Stok, Deviasi + Chart.js | ✅ |
| 13 | Pemilik | CRUD Bahan Baku | Pemilik → Bahan Baku → Tambah/Edit/Hapus | Data tersimpan/diupdate/dihapus | ✅ |
| 14 | Pemilik | CRUD Produksi | Pemilik → Produksi → Tambah/Edit/Hapus | Data tersimpan + stok diupdate | ✅ |
| 15 | Pemilik | CRUD Penjualan | Pemilik → Penjualan → Tambah/Edit/Hapus | Data tersimpan + total otomatis + stok diupdate | ✅ |
| 16 | Pemilik | CRUD Tenaga Kerja | Pemilik → Tenaga Kerja → Tambah/Edit/Toggle/Hapus | Data pekerja terkelola | ✅ |
| 17 | Pemilik | Perhitungan Gaji | Pemilik → Gaji → pilih periode → Hitung Gaji | Gaji = sak semen × tarif per sak | ✅ |
| 18 | Pemilik | Simpan Gaji | Setelah hitung → klik Simpan | Data tersimpan di tabel gaji | ✅ |
| 19 | Laporan | Produksi vs Penjualan | Pilih periode → Tampilkan | Grafik + tabel sesuai periode | ✅ |
| 20 | Laporan | Keuangan | Pilih periode → Tampilkan | Total pendapatan + grafik bulanan | ✅ |
| 21 | Laporan | Gaji | Pilih periode → Tampilkan | Detail gaji per pekerja | ✅ |
| 22 | UI | Tampilan Desktop | Buka di laptop/PC | Sidebar fixed, content full, cards rapi | ✅ |
| 23 | UI | Tampilan Mobile | Buka di HP | Sidebar offcanvas, content stack, table scroll | ✅ |
| 24 | UI | Warna konsisten | Cek seluruh halaman | Navy sidebar, blue primary, orange accent, green success | ✅ |
| 25 | Filter | Filter data | Gunakan filter tanggal/ukuran/search | Data terfilter dengan benar | ✅ |
| 26 | Pagination | Navigasi halaman | Data > 15 → klik next page | Pagination berfungsi | ✅ |
| 27 | Format | Format Rupiah | Cek harga/total/gaji | Menggunakan format Rp x.xxx | ✅ |
| 28 | Flash | Flash message | Setelah CRUD action | Muncul alert sukses/gagal | ✅ |
| 29 | Delete | Konfirmasi hapus | Klik tombol hapus | Muncul konfirmasi JavaScript | ✅ |
| 30 | Auto | Total penjualan auto | Input jumlah & harga | Total terhitung otomatis via JavaScript | ✅ |
| 31 | Export | Excel Export | Klik Export Excel | File .xlsx terdownload | ✅ |
| 32 | Export | PDF Export Produksi | Klik Cetak PDF | File .pdf terdownload | ✅ |
| 33 | Export | PDF Laporan Produksi | Pilih periode → Klik PDF | File .pdf terdownload | ✅ |
| 34 | Export | PDF Laporan Keuangan | Pilih periode → Klik PDF | File .pdf terdownload | ✅ |
| 35 | Export | PDF Laporan Gaji | Pilih periode → Klik PDF | File .pdf terdownload | ✅ |
| 36 | Test | Automated Test Suite | `php tests/TestRunner.php` | 65/65 passed | ✅ |
| 31 | Export | Excel Export | Klik Export Excel | File .xlsx terdownload | ✅ |
| 32 | Export | PDF Export Produksi | Klik Cetak PDF | File .pdf terdownload | ✅ |
| 33 | Export | PDF Laporan Produksi | Pilih periode → Klik PDF | File .pdf terdownload | ✅ |
| 34 | Export | PDF Laporan Keuangan | Pilih periode → Klik PDF | File .pdf terdownload | ✅ |
| 35 | Export | PDF Laporan Gaji | Pilih periode → Klik PDF | File .pdf terdownload | ✅ |
| 36 | Test | Automated Test Suite | Jalankan `php tests/TestRunner.php` | 65/65 tests passed | ✅ |
