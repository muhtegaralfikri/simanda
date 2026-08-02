# CHECKLIST USER ACCEPTANCE TESTING (UAT) — SIMANDA

Dokumen ini berisi pengujian penerimaan pengguna (*User Acceptance Testing*) terhadap seluruh modul aplikasi SIMANDA.

| ID UAT | Modul | Skenario Pengujian | Role Target | Langkah-Langkah | Hasil yang Diharapkan | Status | Penguji | Tanggal |
|---|---|---|---|---|---|---|---|---|
| UAT-01 | Autentikasi | Login Akun Administrator | Admin | Input email `admin@simanda.go.id` & password | Berhasil masuk ke dashboard admin | **LULUS** | QA Team | 02/08/2026 |
| UAT-02 | Autentikasi | Login Akun Pimpinan | Pimpinan | Input email `pimpinan@simanda.go.id` & password | Berhasil masuk ke dashboard pimpinan (read-only) | **LULUS** | QA Team | 02/08/2026 |
| UAT-03 | Master Data | Tambah Unit Kerja Baru | Admin | Buka `/master/units`, form tambah unit | Unit kerja baru tersimpan di database | **LULUS** | QA Team | 02/08/2026 |
| UAT-04 | Perencanaan | Buat Program & Kegiatan | PPTK | Buka `/programs` & `/activities`, simpan data | Kegiatan baru terbuat dengan status `draft` | **LULUS** | QA Team | 02/08/2026 |
| UAT-05 | Perencanaan | Input RAB & Kunci Pagu | PPTK | Input rincian RAB hingga total RAB == Pagu | Kegiatan dapat diubah menjadi `planned` | **LULUS** | QA Team | 02/08/2026 |
| UAT-06 | Pelaksanaan | Mulai Kegiatan & Progres | PPTK | Klik 'Mulai Pelaksanaan', perbarui progres % | Status menjadi `ongoing` & log progres tercatat | **LULUS** | QA Team | 02/08/2026 |
| UAT-07 | Pelaksanaan | Input Realisasi Transaksi | PPTK | Buat realisasi baru, nominal & kuitansi | Transaksi tersimpan dengan status `draft` | **LULUS** | QA Team | 02/08/2026 |
| UAT-08 | Pelaksanaan | Unggah Dokumen Privat | PPTK | Unggah berkas TOR/RAB format PDF/Docx | Berkas tersimpan privat di storage & versi 1 | **LULUS** | QA Team | 02/08/2026 |
| UAT-09 | Verifikasi | Pengajuan Verifikasi | PPTK | Ajukan realisasi, dokumen, & kegiatan | Status kegiatan menjadi `waiting_verification` | **LULUS** | QA Team | 02/08/2026 |
| UAT-10 | Verifikasi | Verifikasi Realisasi & Dokumen | Verifier | Buka `/admin/verifications`, validasi & setuju | Status realisasi `verified` & dokumen `valid` | **LULUS** | QA Team | 02/08/2026 |
| UAT-11 | Verifikasi | Permintaan Revisi Transaksi | Verifier | Klik 'Minta Revisi' dengan catatan | Status kegiatan & realisasi menjadi `revision` | **LULUS** | QA Team | 02/08/2026 |
| UAT-12 | Verifikasi | Penutupan Kegiatan (Closing) | Verifier | Klik 'Tutup Kegiatan' saat 100% valid | Kegiatan `completed` & data terkunci read-only | **LULUS** | QA Team | 02/08/2026 |
| UAT-13 | Analitik | Dashboard Analitik & Chart | All Roles | Buka `/dashboard` dengan filter TA & Unit | Indikator & grafik Chart.js tampil akurat | **LULUS** | QA Team | 02/08/2026 |
| UAT-14 | Laporan | Cetak PDF & Ekspor Excel | All Roles | Unduh PDF & CSV/Excel dari Pusat Laporan | PDF printable & CSV clean (tanpa injection) | **LULUS** | QA Team | 02/08/2026 |
| UAT-15 | Peringatan | Peringatan Tenggat Waktu | All Roles | Jalankan `simanda:alerts:generate` | Alert dibuat tanpa duplikasi & badge aktif | **LULUS** | QA Team | 02/08/2026 |
| UAT-16 | Keamanan | Akses Dokumen Privat Terkunci | Anonymous | Akses URL file fisik langsung via HTTP | HTTP 403 / 404 Forbidden | **LULUS** | QA Team | 02/08/2026 |
| UAT-17 | Backup | Backup & Verification SHA-256 | Admin | Jalankan `simanda:backup` & verify | Backup SQLite & Dokumen valid terverifikasi | **LULUS** | QA Team | 02/08/2026 |
| UAT-18 | Infrastruktur| System Health Check | Admin | Buka `/admin/system/health` | Semua komponen menunjukkan status GOOD | **LULUS** | QA Team | 02/08/2026 |
