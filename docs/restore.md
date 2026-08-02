# PANDUAN PEMULIHAN APLIKASI (RESTORE PROCEDURES) — SIMANDA

Dokumen ini menjelaskan alur manual pemulihan data aplikasi SIMANDA dari file cadangan (*backup*) jika terjadi kerusakan server atau kegagalan data.

> [!IMPORTANT]
> Pemulihan (*restore*) **HANYA DILAKUKAN MANUAL MELALUI SERVER TERMINAL** demi keamanan dan mencegah penghapusan data secara tidak sengaja melalui browser web.

---

## ALUR LANGKAH-DEMI-LANGKAH PEMULIHAN DATA

### 1. Aktifkan Modus Perbaikan (Maintenance Mode)

```bash
cd /var/www/simanda
php artisan down --message="Sistem sedang dalam pemeliharaan darurat."
```

### 2. Verifikasi File Backup

Pilih folder backup yang akan digunakan (misal: `/var/backups/simanda/daily/2026-08-02-013000`):

```bash
php artisan simanda:backup:verify --path=daily/2026-08-02-013000
```

Pastikan status verifikasi mengembalikan output **SUCCESS (Integrity OK)**.

### 3. Buat Salinan Darurat Data Saat Ini (Pre-Restore Snapshot)

```bash
cp database/database.sqlite database/database.sqlite.bak
```

### 4. Pulihkan File Database SQLite

```bash
cp /var/backups/simanda/daily/2026-08-02-013000/database.sqlite database/database.sqlite
chmod 660 database/database.sqlite
```

### 5. Pulihkan Folder Dokumen Private

```bash
# Ekstrak archive dokumen ke lokasi private storage
unzip -o /var/backups/simanda/daily/2026-08-02-013000/documents.zip -d storage/app/private/documents/
```

### 6. Atur Ulang Permission File & Bersihkan Cache

```bash
sudo chown -R www-data:www-data /var/www/simanda
sudo chmod -R 775 storage bootstrap/cache database

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Matikan Maintenance Mode & Verifikasi Status

```bash
php artisan up
```

Buka halaman `/admin/system/health` untuk memastikan database terhubung dan seluruh status komponen berwarna hijau/good.
