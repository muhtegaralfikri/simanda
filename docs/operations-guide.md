# PANDUAN OPERASIONAL & PEMELIHARAAN SISTEM — SIMANDA

Dokumen ini ditujukan bagi Administrator Sistem dan Operator Teknis untuk memelihara dan mengoperasikan aplikasi SIMANDA di lingkungan produksi.

---

## 1. PEMERIKSAAN RUTIN KESEHATAN SISTEM

Akses menu **Status Sistem** pada URL:
```text
https://simanda.example.com/admin/system/health
```
Pastikan seluruh indikator berikut berstatus **GOOD**:
- Environment: `production` & `APP_DEBUG=false`.
- Database SQLite: Mode WAL aktif (`journal_mode=wal`).
- Direktori Storage & Documents Privat: Writable oleh PHP-FPM (`www-data`).
- Heartbeat Scheduler: Aktif dipanggil tiap jam.
- Kapasitas Disk: Sisa ruang disk > 15%.

---

## 2. MANAJEMEN PENCADANGAN (BACKUP)

### A. Eksekusi Backup Manual via Web:
Buka halaman `/admin/system/backups` dan klik tombol **Jalankan Backup Manual** (Pilihan: Daily, Weekly, Monthly).

### B. Eksekusi Backup via Terminal:
```bash
cd /var/www/simanda
php artisan simanda:backup --type=daily
```

### C. Verifikasi Keutuhan File Backup:
```bash
php artisan simanda:backup:verify --path=daily/2026-08-02-013000
```

---

## 3. PROSEDUR PENANGANAN INSIDEN (TROUBLESHOOTING)

### A. Kapasitas Disk Penuh / Peringatan Disk Space:
1. Jalankan simulasi atau pembersihan backup lama:
   ```bash
   php artisan simanda:backup:cleanup
   ```
2. Bersihkan file log sistem lama:
   ```bash
   rm -f storage/logs/laravel-*.log
   ```

### B. Database SQLite Terkunci (Database Locked / Busy Timeout):
SIMANDA telah dikonfigurasi dengan `busy_timeout=5000` dan mode `WAL`. Jika terjadi penguncian sementara, periksa proses PHP yang menggantung:
```bash
sudo systemctl restart php8.3-fpm
```

### C. Reset Cache Aplikasi Pasca Pembaruan Kode:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. KONTAK DUKUNGAN TEKNIS

- **Tim Dukungan SIMANDA**: `support@simanda.example.com`
- **Helpdesk IT**: `0800-1-SIMANDA`
