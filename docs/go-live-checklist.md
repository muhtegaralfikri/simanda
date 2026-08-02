# CHECKLIST GO-LIVE SIMANDA — PELUNCURAN PRODUKSI

- [x] **Aplikasi**: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` tergenerate, `config:cache`, `route:cache`, `view:cache` aktif.
- [x] **Database**: SQLite WAL mode aktif (`PRAGMA journal_mode=WAL`), migration `force` selesai.
- [x] **Keamanan**: File `.env` & `database.sqlite` terblokir dari akses HTTP publik. Dokumen tersimpan privat di `storage/app/private/documents`.
- [x] **Backup**: `simanda:backup` harian & retensi aktif, verifikasi SHA-256 berfungsi.
- [x] **Scheduler**: Cron `schedule:run` berjalan tiap menit.
- [x] **Pengujian**: All 72+ Feature Tests PASS 100%.
