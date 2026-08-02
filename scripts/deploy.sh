#!/usr/bin/env bash
set -euo pipefail

echo "==> Memulai Script Deployment Automated SIMANDA..."

# 1. Aktifkan Maintenance Mode
php artisan down || true

# 2. Backup Sebelum Deploy
php artisan simanda:backup --type=daily || echo "Warning: Backup otomatis pra-deploy mengalami catatan."

# 3. Pull & Install Dependencies
composer install --no-dev --prefer-dist --optimize-autoloader

# 4. Run Migrations & Cache Optimization
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Matikan Maintenance Mode
php artisan up

echo "==> Deployment SIMANDA Berhasil Selesai!"
