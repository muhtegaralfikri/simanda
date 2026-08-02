<?php

namespace Database\Seeders;

use App\Models\BudgetYear;
use App\Models\DocumentType;
use App\Models\ExpenseType;
use App\Models\FundingSource;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tahun Anggaran
        $budgetYear = BudgetYear::updateOrCreate(
            ['year' => 2026],
            [
                'name' => 'Tahun Anggaran 2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_active' => true,
                'is_closed' => false,
            ]
        );

        // 2. Unit Kerja
        $units = [
            ['code' => 'SKR', 'name' => 'Sekretariat Utama', 'description' => 'Unit Pelayanan Administrasi dan Manajemen Umum'],
            ['code' => 'BAP', 'name' => 'Bappeda', 'description' => 'Badan Perencanaan Pembangunan Daerah'],
            ['code' => 'DIN', 'name' => 'Dinas Kesehatan', 'description' => 'Dinas Kesehatan dan Pelayanan Masyarakat'],
            ['code' => 'DIS', 'name' => 'Dinas Pendidikan', 'description' => 'Dinas Pendidikan dan Kebudayaan'],
        ];

        $unitModels = [];
        foreach ($units as $u) {
            $unitModels[$u['code']] = Unit::updateOrCreate(['code' => $u['code']], $u);
        }

        // 3. Users per Role
        $users = [
            [
                'email' => 'admin@simanda.go.id',
                'name' => 'Administrator Sistem',
                'role' => 'admin',
                'unit_id' => $unitModels['SKR']->id,
                'phone' => '081234567890',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'pimpinan@simanda.go.id',
                'name' => 'Dr. H. Bambang Priyono, M.Si',
                'role' => 'pimpinan',
                'unit_id' => $unitModels['SKR']->id,
                'phone' => '081298765432',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'pptk.bappeda@simanda.go.id',
                'name' => 'Budi Santoso, S.STP',
                'role' => 'pptk',
                'unit_id' => $unitModels['BAP']->id,
                'phone' => '081311223344',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'pptk.dinkes@simanda.go.id',
                'name' => 'dr. Ratna Sari, M.Kes',
                'role' => 'pptk',
                'unit_id' => $unitModels['DIN']->id,
                'phone' => '081355667788',
                'password' => Hash::make('password'),
            ],
            [
                'email' => 'verifier@simanda.go.id',
                'name' => 'Ahmad Hidayat, S.E., Ak.',
                'role' => 'verifier',
                'unit_id' => $unitModels['SKR']->id,
                'phone' => '081399001122',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }

        // 4. Sumber Dana
        $fundingSources = [
            ['code' => 'APBD', 'name' => 'APBD Kabupaten/Kota', 'description' => 'Anggaran Pendapatan dan Belanja Daerah'],
            ['code' => 'APBN', 'name' => 'APBN / DAK Pusat', 'description' => 'Dana Alokasi Khusus APBN'],
            ['code' => 'BLUD', 'name' => 'Pendapatan BLUD / Swakelola', 'description' => 'Pendapatan Asli Daerah / Rumah Sakit / Kampus'],
            ['code' => 'HIBAH', 'name' => 'Dana Hibah & Kerjasama', 'description' => 'Bantuan pihak ketiga non-mengikat'],
        ];
        foreach ($fundingSources as $fs) {
            FundingSource::updateOrCreate(['code' => $fs['code']], $fs);
        }

        // 5. Jenis Belanja
        $expenseTypes = [
            ['code' => '5.1.02.01', 'name' => 'Belanja Bahan & Alat Tulis Kantor', 'category' => 'Operasional'],
            ['code' => '5.1.02.02', 'name' => 'Belanja Cetak & Penggandaan', 'category' => 'Operasional'],
            ['code' => '5.1.02.04', 'name' => 'Belanja Honorarium Panitia & Narasumber', 'category' => 'Personel'],
            ['code' => '5.1.02.05', 'name' => 'Belanja Perjalanan Dinas Biasa', 'category' => 'Perjalanan Dinas'],
            ['code' => '5.2.02.01', 'name' => 'Belanja Modal Peralatan Komputer', 'category' => 'Modal'],
        ];
        foreach ($expenseTypes as $et) {
            ExpenseType::updateOrCreate(['code' => $et['code']], $et);
        }

        // 6. Jenis Dokumen
        $documentTypes = [
            ['code' => 'TOR', 'name' => 'TOR / Kerangka Acuan Kerja (KAK)', 'stage' => 'planning', 'is_required' => true, 'allowed_extensions' => 'pdf,doc,docx', 'maximum_size' => 10240],
            ['code' => 'RAB', 'name' => 'Rincian Anggaran Biaya (RAB)', 'stage' => 'planning', 'is_required' => true, 'allowed_extensions' => 'pdf,xls,xlsx', 'maximum_size' => 5120],
            ['code' => 'SURAT_TUGAS', 'name' => 'Surat Tugas / Undangan', 'stage' => 'planning', 'is_required' => false, 'allowed_extensions' => 'pdf', 'maximum_size' => 5120],
            ['code' => 'PRESENSI', 'name' => 'Daftar Hadir Peserta', 'stage' => 'execution', 'is_required' => true, 'allowed_extensions' => 'pdf,jpg,png', 'maximum_size' => 5120],
            ['code' => 'NOTULEN', 'name' => 'Notulen & Laporan Risalah Rapat', 'stage' => 'execution', 'is_required' => false, 'allowed_extensions' => 'pdf,doc,docx', 'maximum_size' => 5120],
            ['code' => 'DOKUMENTASI', 'name' => 'Foto Dokumentasi Kegiatan', 'stage' => 'execution', 'is_required' => true, 'allowed_extensions' => 'jpg,jpeg,png,pdf', 'maximum_size' => 10240],
            ['code' => 'KUITANSI', 'name' => 'Kuitansi & Bukti Pembayaran', 'stage' => 'financial', 'is_required' => true, 'allowed_extensions' => 'pdf,jpg,png', 'maximum_size' => 5120],
            ['code' => 'INVOICE', 'name' => 'Invoice & Nota Penyedia', 'stage' => 'financial', 'is_required' => false, 'allowed_extensions' => 'pdf,jpg,png', 'maximum_size' => 5120],
            ['code' => 'FAKTUR_PAJAK', 'name' => 'Faktur Pajak / Bukti Potong', 'stage' => 'financial', 'is_required' => false, 'allowed_extensions' => 'pdf,jpg,png', 'maximum_size' => 5120],
        ];
        foreach ($documentTypes as $dt) {
            DocumentType::updateOrCreate(['code' => $dt['code']], $dt);
        }
    }
}
