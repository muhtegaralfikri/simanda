# SIMANDA Go-Live Report

## Ringkasan

- Tanggal deployment: 2026-08-02
- Domain production: https://simanda.muhtegaralfikri.my.id
- Server: satu server aplikasi, Nginx, PHP-FPM, SQLite, Laravel monolith
- Branch: main
- Commit: 99a1f257044e8916c034f2cf991e0bfcb4e358ac
- Administrator: operator server melalui SSH
- Status akhir: GO-LIVE BERHASIL

## Versi Runtime

- OS: Armbian OS 26.08.0 bookworm / Debian 12
- PHP: 8.3.32
- Laravel: 13.23.0
- SQLite: 3.40.1
- Nginx: 1.22.1
- Composer: 2.5.5

## Pemeriksaan Server

- RAM: 1.9 GiB
- Swap: 1.0 GiB
- Disk root setelah pembersihan aman: 79% terpakai, sekitar 21% kosong
- Extension PHP wajib tersedia: pdo_sqlite, sqlite3, mbstring, openssl, fileinfo, tokenizer, xml, ctype, json, curl
- Queue: sync
- Cache: file
- Session: file
- Timezone aplikasi: Asia/Makassar

## Deployment Kode

- Kode production diambil dari repository GitHub resmi proyek SIMANDA.
- File lokal non-production tidak dibawa ke production: .env lokal, database lokal, node_modules, log lokal, dokumen lokal, backup lokal.
- Document root Nginx hanya mengarah ke direktori public aplikasi.
- Direktori database, storage private, vendor, backup, dan environment file tidak berada dalam document root.

## Environment

- APP_ENV: production
- APP_DEBUG: false
- APP_URL: https://simanda.muhtegaralfikri.my.id
- DB_CONNECTION: sqlite
- SESSION_SECURE_COOKIE: true
- QUEUE_CONNECTION: sync
- LOG_CHANNEL: daily
- Backup path production dikonfigurasi di luar document root.

Tidak ada credential, APP_KEY, atau password yang dicatat dalam laporan ini.

## Database

- Database SQLite production dibuat untuk deployment baru.
- Migration dijalankan dengan `php artisan migrate --force`.
- Status migration: berhasil, seluruh migration berjalan.
- SQLite integrity check: ok
- SQLite journal mode: wal
- Seeder demo tidak dijalankan di production.
- Data awal production dibuat secara minimum untuk role admin, pimpinan, PPTK, verifier, master tahun anggaran, unit kerja, sumber dana, jenis belanja, dan jenis dokumen.

## Optimasi Laravel

- `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`: berhasil
- `php artisan optimize:clear`: berhasil
- `php artisan config:cache`: berhasil
- `php artisan route:cache`: berhasil
- `php artisan view:cache`: berhasil
- `php artisan about`: berhasil
- `php artisan route:list`: berhasil
- `php artisan schedule:list`: berhasil

## PHP-FPM

- Mode process manager: ondemand
- max_children: 8
- process_idle_timeout: 10s
- max_requests: 300
- memory_limit: 256M
- upload_max_filesize: 10M
- post_max_size: 12M
- max_execution_time: 120
- OPcache dikonfigurasi aktif untuk PHP-FPM.
- PHP-FPM config test: berhasil
- PHP-FPM reload: berhasil

## Nginx dan HTTPS

- Nginx site production dibuat untuk service tunnel lokal port 8084.
- Root mengarah ke public aplikasi.
- Hidden files, .env, database, storage private, backup, dan vendor diblokir.
- Static asset caching aktif.
- `nginx -t`: berhasil
- Nginx reload: berhasil
- HTTPS publik aktif melalui Cloudflare Tunnel.
- Domain login production: https://simanda.muhtegaralfikri.my.id/login

## Scheduler

Cron production terpasang untuk menjalankan Laravel scheduler setiap menit.

Jadwal terverifikasi:

- 07:00 setiap hari: `simanda:alerts:generate`
- 01:30 setiap hari: `simanda:backup --type=daily`
- 02:00 setiap Minggu: `simanda:backup --type=weekly`
- 02:30 tanggal 1: `simanda:backup --type=monthly`
- 03:00 setiap hari: `simanda:backup:verify`
- Setiap satu jam: `simanda:scheduler:heartbeat`

Heartbeat scheduler: berhasil dan health check status good.

## Backup Awal

- Backup awal daily: berhasil
- Backup verification by path: berhasil
- Backup verification latest: berhasil
- Manifest tersedia: ya
- Database backup tersedia: ya
- SQLite integrity pada backup: ok
- Status backup pada health page: good

## Smoke Test Role

Login melalui domain HTTPS:

- Admin: berhasil
- Pimpinan: berhasil
- PPTK: berhasil
- Verifier: berhasil

Logout, redirect HTTPS, dan akses menu sesuai role diuji tanpa error 500.

## Smoke Test File Private

- Upload dokumen oleh PPTK: berhasil
- File tersimpan pada private storage: berhasil
- Download tanpa login: ditolak atau diarahkan ke login
- Akses langsung ke path private: ditolak
- File uji production telah dibersihkan setelah validasi
- Tidak ditemukan dokumen uji tersisa setelah cleanup

## Smoke Test Laporan

Delapan laporan diuji dan dapat diakses:

1. Ringkasan Anggaran
2. Realisasi Anggaran
3. Kegiatan
4. Progres
5. Dokumen
6. Verifikasi
7. Serapan Bulanan
8. Sisa Anggaran

Ekspor PDF dan CSV/Excel untuk seluruh laporan: berhasil.

## Alert dan Health

- `php artisan simanda:alerts:generate`: berhasil
- Tidak ditemukan error kritis pada alert generation
- Health check akhir: overall good
- Komponen health good: environment, database, storage, documents storage, backup, disk space, PHP OPcache, scheduler

## Log

Log aplikasi, Nginx, dan PHP-FPM diperiksa setelah deployment.

Tidak ditemukan:

- error 500 pada alur utama
- database permission denied
- database is locked berulang
- route conflict
- backup error
- scheduler error
- stack trace sensitif pada tampilan publik

## Temuan dan Perbaikan

Temuan deployment:

- Timezone Laravel belum membaca `APP_TIMEZONE`.
- Redirect login berada pada skema HTTP ketika berada di belakang Cloudflare Tunnel.
- Health check belum menganggap backup `verified` sebagai backup valid.
- Heartbeat scheduler perlu disimpan dalam format waktu yang konsisten.
- Disk sempat berada di batas minimal dan perlu pembersihan cache aman.

Tindakan:

- Konfigurasi timezone diperbaiki agar membaca environment.
- Trusted proxy Cloudflare Tunnel dikonfigurasi pada bootstrap Laravel.
- Health check backup dan heartbeat diperbaiki.
- Cache Composer/APT dan backup validasi duplikat dibersihkan tanpa menghapus data production.

## Keputusan Aktivasi

Semua kriteria utama terpenuhi:

- Domain HTTPS dapat diakses.
- Login empat role berhasil.
- Database SQLite sehat dan WAL aktif.
- Migration selesai.
- Cache Laravel berhasil.
- Nginx test berhasil.
- Upload dan proteksi dokumen private berhasil.
- Laporan dan ekspor berhasil.
- Alert dan scheduler berjalan.
- Health check tidak memiliki masalah kritis.
- Backup awal berhasil dan terverifikasi.

Status akhir: GO-LIVE BERHASIL
