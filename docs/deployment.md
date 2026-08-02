# PANDUAN DEPLOYMENT SIMANDA — STABILISASI VPS 2 GB RAM

Dokumen ini berisi panduan teknis langkah-demi-langkah untuk mendeploy aplikasi **SIMANDA (Sistem Monitoring Anggaran dan Dokumen Kegiatan)** pada server VPS Linux (Ubuntu/Debian) dengan RAM 2 GB.

---

## 1. KEBUTUHAN SERVER & PERANGKAT LUNAK

* **Sistem Operasi**: Ubuntu 22.04 LTS / Debian 12
* **RAM**: 2 GB (Rekomendasi Tambahkan Swap 1–2 GB)
* **Web Server**: Nginx
* **PHP**: PHP 8.3 (PHP-FPM, Extensions: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `zip`)
* **Database**: SQLite 3 (CLI `sqlite3` terinstal)
* **Composer**: Latest 2.x

---

## 2. STABILISASI MEMORI (SWAP 2 GB)

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 3. INSTALASI DEPENDENCY SERVER

```bash
sudo apt update && sudo apt install -y nginx php8.3-fpm php8.3-sqlite3 php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip sqlite3 composer git
```

---

## 4. DEPLOYMENT APLIKASI

```bash
cd /var/www
git clone <URL_REPOSITORY> simanda
cd simanda

# Setup File Environment
cp .env.production.example .env
php artisan key:generate

# Buat File Database SQLite
touch database/database.sqlite
chmod 660 database/database.sqlite

# Modus Production Install
composer install --no-dev --prefer-dist --optimize-autoloader

# Migration & Optimasi Cache
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Hak Akses Direktori
sudo chown -R www-data:www-data /var/www/simanda
sudo chmod -R 775 storage bootstrap/cache database
```

---

## 5. SCHEDULER CRON

Daftarkan Cron Job untuk `www-data`:

```bash
sudo crontab -u www-data -e
```

Tambahkan baris berikut:

```cron
* * * * * cd /var/www/simanda && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. PEMERIKSAAN AKHIR

Buka browser dan akses URL sistem, pastikan halaman login dapat diakses dan buka menu `Status Sistem` (`/admin/system/health`) untuk memverifikasi kesehatan server.
