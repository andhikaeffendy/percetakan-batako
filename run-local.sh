#!/bin/bash
# run-local.sh — Jalankan aplikasi lokal di MacBook (tanpa XAMPP)
# Cara pakai: ./run-local.sh
# Buka http://localhost:8000 — login pemilik/admin123 atau operator/operator123
set -e
cd "$(dirname "$0")"

MYSQL=/opt/homebrew/bin/mysql
PHP=/opt/homebrew/bin/php
DB_NAME=db_batako_maros

echo "==> [1/3] Membuat database (jika belum ada)..."
$MYSQL -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"

echo "==> [2/3] Menyiapkan struktur tabel..."
if $MYSQL -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='stok_produk';" | grep -q "^1$"; then
    echo "    Struktur sudah lengkap — dilewati (data tidak disentuh)."
elif $MYSQL -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='users';" | grep -q "^1$"; then
    echo "    Database lama terdeteksi — menjalankan migrasi revisi persediaan..."
    $MYSQL -u root "$DB_NAME" < migrations/revisi_persediaan.sql
    echo "    Migrasi selesai."
else
    echo "    Database baru — import struktur lengkap..."
    $MYSQL -u root "$DB_NAME" < database.sql
    echo "    Struktur tabel berhasil diimpor."
fi

echo "==> [3/3] Menjalankan server di http://localhost:8000 ..."
echo "    Tekan Ctrl+C untuk menghentikan."
$PHP -S localhost:8000
