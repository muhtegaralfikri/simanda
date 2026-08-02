<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\ActivityLog;
use App\Models\BackupHistory;
use App\Models\BudgetPlan;
use App\Models\BudgetYear;
use App\Models\DocumentType;
use App\Models\ExpenseType;
use App\Models\FundingSource;
use App\Models\Program;
use App\Models\Realization;
use App\Models\Unit;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Run MasterSeeder first
        $this->call(MasterSeeder::class);

        $activeYear = BudgetYear::where('is_active', true)->first();
        $bappeda = Unit::where('code', 'BAP')->first();
        $dinkes = Unit::where('code', 'DIN')->first();
        $disdik = Unit::where('code', 'DIS')->first();
        $sekretariat = Unit::where('code', 'SKR')->first();

        $pptkBap = User::where('email', 'pptk.bappeda@simanda.go.id')->first();
        $pptkDin = User::where('email', 'pptk.dinkes@simanda.go.id')->first();
        $admin = User::where('email', 'admin@simanda.go.id')->first();
        $verifier = User::where('email', 'verifier@simanda.go.id')->first();

        $apbd = FundingSource::where('code', 'APBD')->first();
        $apbn = FundingSource::where('code', 'APBN')->first();
        $blud = FundingSource::where('code', 'BLUD')->first();

        $etAtk = ExpenseType::where('code', '5.1.02.01')->first();
        $etHonor = ExpenseType::where('code', '5.1.02.04')->first();
        $etPerjadin = ExpenseType::where('code', '5.1.02.05')->first();
        $etModal = ExpenseType::where('code', '5.2.02.01')->first();

        $dtTor = DocumentType::where('code', 'TOR')->first();
        $dtRab = DocumentType::where('code', 'RAB')->first();
        $dtPresensi = DocumentType::where('code', 'PRESENSI')->first();
        $dtDokumentasi = DocumentType::where('code', 'DOKUMENTASI')->first();
        $dtKuitansi = DocumentType::where('code', 'KUITANSI')->first();

        // Ensure fake private document file exists
        $sampleDocPath = storage_path('app/private/documents/sample_document.pdf');
        if (! File::exists(dirname($sampleDocPath))) {
            File::makeDirectory(dirname($sampleDocPath), 0755, true);
        }
        File::put($sampleDocPath, '%PDF-1.4 Dummy Content for SIMANDA Demo Document%');

        // ==========================================
        // PROGRAM 1: BAPPEDA
        // ==========================================
        $prgBap1 = Program::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $bappeda->id,
            'program_code' => 'PRG-2026-BAP-01',
            'program_name' => 'Program Perencanaan Pembangunan & Tata Ruang Daerah',
            'description' => 'Program koordinasi dan penyusunan Rencana Kerja Pemerintah Daerah (RKPD)',
            'is_active' => true,
        ]);

        // KEGIATAN 1: BAPPEDA (Ongoing, 75%)
        $kgtBap1 = Activity::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $bappeda->id,
            'program_id' => $prgBap1->id,
            'person_in_charge_id' => $pptkBap->id,
            'funding_source_id' => $apbd->id,
            'activity_code' => 'KGT-2026-BAP-01',
            'activity_name' => 'Pengadaan Perangkat IT & Server Ruang Data Bappeda',
            'description' => 'Modernisasi infrastruktur server data perencanaan dan penyediaan laptop workstation analis',
            'start_date' => '2026-02-01',
            'end_date' => '2026-08-31',
            'location' => 'Gedung Bappeda Lt. 3',
            'target' => 'Tersedianya 1 Rack Server & 5 Unit Laptop Analis',
            'budget_ceiling' => 150000000,
            'progress_percentage' => 75,
            'progress_note' => 'Perangkat server telah tiba dan terpasang di Data Center. Instalasi software 75%.',
            'status' => 'ongoing',
            'created_by' => $pptkBap->id,
        ]);

        BudgetPlan::create([
            'activity_id' => $kgtBap1->id,
            'expense_type_id' => $etModal->id,
            'account_code' => '5.2.02.01',
            'description' => 'Server Rack Data Center Enterprise',
            'volume' => 1,
            'unit' => 'Unit',
            'unit_price' => 90000000,
            'total' => 90000000,
        ]);

        BudgetPlan::create([
            'activity_id' => $kgtBap1->id,
            'expense_type_id' => $etModal->id,
            'account_code' => '5.2.02.01',
            'description' => 'Laptop Workstation Analis Perencanaan',
            'volume' => 5,
            'unit' => 'Unit',
            'unit_price' => 12000000,
            'total' => 60000000,
        ]);

        $bp1 = BudgetPlan::where('activity_id', $kgtBap1->id)->first();
        Realization::create([
            'activity_id' => $kgtBap1->id,
            'budget_plan_id' => $bp1->id,
            'expense_type_id' => $etModal->id,
            'transaction_date' => '2026-03-10',
            'receipt_number' => 'KW-BAP-2026-001',
            'gross_amount' => 90000000,
            'tax_amount' => 9000000,
            'net_amount' => 81000000,
            'payment_method' => 'transfer',
            'recipient_name' => 'PT Server Nusantara Teknologi',
            'description' => 'Pembayaran Uang Muka 100% Pengadaan Server Data Center Enterprise',
            'status' => 'verified',
            'created_by' => $pptkBap->id,
            'verified_at' => '2026-03-12 10:30:00',
            'verified_by' => $verifier->id,
        ]);

        // KEGIATAN 2: BAPPEDA (Waiting Verification, 100%)
        $kgtBap2 = Activity::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $bappeda->id,
            'program_id' => $prgBap1->id,
            'person_in_charge_id' => $pptkBap->id,
            'funding_source_id' => $apbd->id,
            'activity_code' => 'KGT-2026-BAP-02',
            'activity_name' => 'Sosialisasi Rencana Pembangunan Jangka Menengah Daerah (RPJMD)',
            'description' => 'Fokus Group Discussion (FGD) dan sosialisasi draft RPJMD kepada seluruh OPD',
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-30',
            'location' => 'Hotel Grand Ballrom',
            'target' => 'Terlaksananya Sosialisasi RPJMD diikuti 120 Peserta',
            'budget_ceiling' => 80000000,
            'progress_percentage' => 100,
            'progress_note' => 'FGD dan laporan akhir RPJMD telah rampung 100%. Diajukan untuk penutupan.',
            'submission_status' => 'submitted',
            'submitted_at' => '2026-06-01 09:00:00',
            'submitted_by' => $pptkBap->id,
            'verification_round' => 1,
            'status' => 'waiting_verification',
            'created_by' => $pptkBap->id,
        ]);

        $bpBap2_1 = BudgetPlan::create([
            'activity_id' => $kgtBap2->id,
            'expense_type_id' => $etHonor->id,
            'account_code' => '5.1.02.04',
            'description' => 'Honorarium Narasumber Ahli Perencanaan',
            'volume' => 4,
            'unit' => 'OJT',
            'unit_price' => 5000000,
            'total' => 20000000,
        ]);

        $bpBap2_2 = BudgetPlan::create([
            'activity_id' => $kgtBap2->id,
            'expense_type_id' => $etAtk->id,
            'account_code' => '5.1.02.01',
            'description' => 'Paket Paket Seminar Kit & Konsumsi 120 Peserta',
            'volume' => 120,
            'unit' => 'Paket',
            'unit_price' => 500000,
            'total' => 60000000,
        ]);

        Realization::create([
            'activity_id' => $kgtBap2->id,
            'budget_plan_id' => $bpBap2_1->id,
            'expense_type_id' => $etHonor->id,
            'transaction_date' => '2026-05-15',
            'receipt_number' => 'KW-BAP-2026-002',
            'gross_amount' => 20000000,
            'tax_amount' => 1000000,
            'net_amount' => 19000000,
            'payment_method' => 'transfer',
            'recipient_name' => 'Prof. Dr. Ir. H. Suryadi, M.Sc',
            'description' => 'Pembayaran Honor Narasumber Utama FGD RPJMD',
            'status' => 'verified',
            'created_by' => $pptkBap->id,
            'verified_at' => '2026-05-20 14:00:00',
            'verified_by' => $verifier->id,
        ]);

        Realization::create([
            'activity_id' => $kgtBap2->id,
            'budget_plan_id' => $bpBap2_2->id,
            'expense_type_id' => $etAtk->id,
            'transaction_date' => '2026-05-28',
            'receipt_number' => 'KW-BAP-2026-003',
            'gross_amount' => 60000000,
            'tax_amount' => 6000000,
            'net_amount' => 54000000,
            'payment_method' => 'transfer',
            'recipient_name' => 'CV Catering Utama Sejahtera',
            'description' => 'Pembayaran Konsumsi & Paket Seminar Kit 120 Peserta',
            'status' => 'verified',
            'created_by' => $pptkBap->id,
            'verified_at' => '2026-05-30 11:20:00',
            'verified_by' => $verifier->id,
        ]);

        // Upload Valid Documents for Kegiatan 2 Bappeda
        foreach ([$dtTor, $dtRab, $dtPresensi, $dtDokumentasi, $dtKuitansi] as $dt) {
            if ($dt) {
                ActivityDocument::create([
                    'activity_id' => $kgtBap2->id,
                    'document_type_id' => $dt->id,
                    'original_name' => "dokumen_{$dt->code}_rpjmd.pdf",
                    'stored_name' => "dokumen_{$dt->code}_rpjmd.pdf",
                    'file_path' => 'private/documents/sample_document.pdf',
                    'file_size' => 1024500,
                    'mime_type' => 'application/pdf',
                    'version' => 1,
                    'is_current' => true,
                    'status' => 'valid',
                    'uploaded_by' => $pptkBap->id,
                    'verified_at' => '2026-06-01 10:00:00',
                    'verified_by' => $verifier->id,
                ]);
            }
        }

        // ==========================================
        // PROGRAM 2: DINAS KESEHATAN
        // ==========================================
        $prgDin1 = Program::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $dinkes->id,
            'program_code' => 'PRG-2026-DIN-01',
            'program_name' => 'Program Pemenuhan Upaya Kesehatan Masyarakat & Perorangan',
            'description' => 'Pengadaan obat, sarana prasarana puskesmas, dan pencegahan stunting',
            'is_active' => true,
        ]);

        // KEGIATAN 3: DINAS KESEHATAN (Completed, Closed)
        $kgtDin1 = Activity::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $dinkes->id,
            'program_id' => $prgDin1->id,
            'person_in_charge_id' => $pptkDin->id,
            'funding_source_id' => $apbn->id,
            'activity_code' => 'KGT-2026-DIN-01',
            'activity_name' => 'Pengadaan Obat-obatan & Alat Kesehatan Puskesmas Keliling',
            'description' => 'Penyediaan stok obat esensial dan perlengkapan alkes untuk 15 Puskesmas Pembantu',
            'start_date' => '2026-01-15',
            'end_date' => '2026-04-30',
            'location' => 'Gudang Farmasi Dinkes',
            'target' => '100% Obat & Alkes Terdistribusi ke 15 Puskesmas',
            'budget_ceiling' => 250000000,
            'progress_percentage' => 100,
            'progress_note' => 'Pendistribusian selesai 100%. Laporan kegiatan lengkap dan diverifikasi.',
            'submission_status' => 'submitted',
            'submitted_at' => '2026-05-02 08:00:00',
            'submitted_by' => $pptkDin->id,
            'verification_round' => 1,
            'completed_at' => '2026-05-05 14:30:00',
            'completed_by' => $verifier->id,
            'remaining_budget_note' => 'Efisiensi pengadaan obat sebesar Rp 10.000.000 dikembalikan ke Kas Daerah.',
            'closing_note' => 'Kegiatan disetujui dan ditutup. Seluruh indikator kinerja tercapai sempurna.',
            'status' => 'completed',
            'created_by' => $pptkDin->id,
        ]);

        $bpDin1_1 = BudgetPlan::create([
            'activity_id' => $kgtDin1->id,
            'expense_type_id' => $etAtk->id,
            'account_code' => '5.1.02.01',
            'description' => 'Pengadaan Obat Esensial Generik Paket A',
            'volume' => 1,
            'unit' => 'Paket',
            'unit_price' => 160000000,
            'total' => 160000000,
        ]);

        $bpDin1_2 = BudgetPlan::create([
            'activity_id' => $kgtDin1->id,
            'expense_type_id' => $etModal->id,
            'account_code' => '5.2.02.01',
            'description' => 'Paket Alat Kesehatan Stetoskop & Tensimeter Digital',
            'volume' => 15,
            'unit' => 'Set',
            'unit_price' => 6000000,
            'total' => 90000000,
        ]);

        Realization::create([
            'activity_id' => $kgtDin1->id,
            'budget_plan_id' => $bpDin1_1->id,
            'expense_type_id' => $etAtk->id,
            'transaction_date' => '2026-03-01',
            'receipt_number' => 'KW-DIN-2026-001',
            'gross_amount' => 150000000,
            'tax_amount' => 15000000,
            'net_amount' => 135000000,
            'payment_method' => 'transfer',
            'recipient_name' => 'PT Kimia Farma Trading',
            'description' => 'Pembayaran Pengadaan Obat Esensial Generik Paket A (Efisiensi HPS)',
            'status' => 'verified',
            'created_by' => $pptkDin->id,
            'verified_at' => '2026-03-05 10:00:00',
            'verified_by' => $verifier->id,
        ]);

        Realization::create([
            'activity_id' => $kgtDin1->id,
            'budget_plan_id' => $bpDin1_2->id,
            'expense_type_id' => $etModal->id,
            'transaction_date' => '2026-04-10',
            'receipt_number' => 'KW-DIN-2026-002',
            'gross_amount' => 90000000,
            'tax_amount' => 9000000,
            'net_amount' => 81000000,
            'payment_method' => 'transfer',
            'recipient_name' => 'CV Medika Medis Mandiri',
            'description' => 'Pembayaran 15 Set Alkes Stetoskop & Tensimeter Digital',
            'status' => 'verified',
            'created_by' => $pptkDin->id,
            'verified_at' => '2026-04-15 11:00:00',
            'verified_by' => $verifier->id,
        ]);

        // KEGIATAN 4: DINAS KESEHATAN (Revision, 60%)
        $kgtDin2 = Activity::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $dinkes->id,
            'program_id' => $prgDin1->id,
            'person_in_charge_id' => $pptkDin->id,
            'funding_source_id' => $blud->id,
            'activity_code' => 'KGT-2026-DIN-02',
            'activity_name' => 'Pelatihan Penanganan Stunting bagi Tenaga Posyandu',
            'description' => 'Bimbingan teknis modul pengukuran gizi balita untuk 50 Kader Posyandu',
            'start_date' => '2026-03-01',
            'end_date' => '2026-07-31',
            'location' => 'Aula Dinkes',
            'target' => '50 Kader Posyandu Terlatih',
            'budget_ceiling' => 60000000,
            'progress_percentage' => 60,
            'progress_note' => 'Gelombang 1 pelatihan selesai. Menunggu revisi laporan kwitansi transportasi.',
            'status' => 'revision',
            'created_by' => $pptkDin->id,
        ]);

        BudgetPlan::create([
            'activity_id' => $kgtDin2->id,
            'expense_type_id' => $etPerjadin->id,
            'account_code' => '5.1.02.05',
            'description' => 'Uang Transportasi & Uang Saku 50 Kader Posyandu',
            'volume' => 50,
            'unit' => 'Orang',
            'unit_price' => 1200000,
            'total' => 60000000,
        ]);

        $relDin2 = Realization::create([
            'activity_id' => $kgtDin2->id,
            'budget_plan_id' => BudgetPlan::where('activity_id', $kgtDin2->id)->first()->id,
            'expense_type_id' => $etPerjadin->id,
            'transaction_date' => '2026-05-10',
            'receipt_number' => 'KW-DIN-2026-003',
            'gross_amount' => 30000000,
            'tax_amount' => 0,
            'net_amount' => 30000000,
            'payment_method' => 'cash',
            'recipient_name' => 'Kader Posyandu Kecamatan Tengah',
            'description' => 'Pembayaran Uang Transportasi Gelombang 1 (25 Kader)',
            'status' => 'revision',
            'verification_note' => 'Harap lengkapi tanda tangan absensi kehadiran asli pada lampiran kuitansi.',
            'created_by' => $pptkDin->id,
        ]);

        Verification::create([
            'verifiable_type' => 'App\Models\Realization',
            'verifiable_id' => $relDin2->id,
            'round' => 1,
            'decision' => 'revision',
            'notes' => 'Harap lengkapi tanda tangan absensi kehadiran asli pada lampiran kuitansi.',
            'verifier_id' => $verifier->id,
            'verified_at' => '2026-05-15 09:30:00',
        ]);

        // ==========================================
        // PROGRAM 3: DINAS PENDIDIKAN
        // ==========================================
        $prgDis1 = Program::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $disdik->id,
            'program_code' => 'PRG-2026-DIS-01',
            'program_name' => 'Program Peningkatan Mutu & Sarana Sekolah Dasar',
            'description' => 'Digitalisasi ruang kelas dan pelatihan kompetensi guru SD',
            'is_active' => true,
        ]);

        // KEGIATAN 5: DISDIK (Planned)
        $kgtDis1 = Activity::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $disdik->id,
            'program_id' => $prgDis1->id,
            'person_in_charge_id' => $admin->id,
            'funding_source_id' => $apbd->id,
            'activity_code' => 'KGT-2026-DIS-01',
            'activity_name' => 'Bantuan Digitalisasi Laboratorium Komputer Sekolah Dasar',
            'description' => 'Penyediaan 40 unit Chrome Book & Router Wi-Fi untuk 4 Sekolah Dasar Negeri',
            'start_date' => '2026-09-01',
            'end_date' => '2026-11-30',
            'location' => 'SDN 01, SDN 03, SDN 05, SDN 08',
            'target' => 'Tersedianya 4 Lab Komputer Digital',
            'budget_ceiling' => 200000000,
            'progress_percentage' => 0,
            'status' => 'planned',
            'created_by' => $admin->id,
        ]);

        BudgetPlan::create([
            'activity_id' => $kgtDis1->id,
            'expense_type_id' => $etModal->id,
            'account_code' => '5.2.02.01',
            'description' => 'Perangkat Chrome Book Spesifikasi Pembelajaran SD',
            'volume' => 40,
            'unit' => 'Unit',
            'unit_price' => 5000000,
            'total' => 200000000,
        ]);

        // ==========================================
        // PROGRAM 4: SEKRETARIAT UTAMA (Overdue Activity)
        // ==========================================
        $prgSkr1 = Program::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $sekretariat->id,
            'program_code' => 'PRG-2026-SKR-01',
            'program_name' => 'Program Layanan Umum & Pemeliharaan Aset Kantor',
            'description' => 'Dukungan pemeliharaan fasilitas kerja kantor pusat',
            'is_active' => true,
        ]);

        // KEGIATAN 6: SEKRETARIAT (Ongoing, Overdue / Terlambat)
        $kgtSkr1 = Activity::create([
            'budget_year_id' => $activeYear->id,
            'unit_id' => $sekretariat->id,
            'program_id' => $prgSkr1->id,
            'person_in_charge_id' => $admin->id,
            'funding_source_id' => $apbd->id,
            'activity_code' => 'KGT-2026-SKR-01',
            'activity_name' => 'Pemeliharaan Gedung & Fasilitas Operasional Kantor Pusat',
            'description' => 'Perbaikan sistem AC Central, pengecatan fasad luar, dan penggantian atap bocor',
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
            'location' => 'Gedung Utama Kantor',
            'target' => 'Perbaikan 100% Fasilitas Gedung Pusat',
            'budget_ceiling' => 120000000,
            'progress_percentage' => 65,
            'progress_note' => 'Pekerjaan perbaikan AC terlambat karena kendala suku cadang impor.',
            'status' => 'ongoing',
            'created_by' => $admin->id,
        ]);

        BudgetPlan::create([
            'activity_id' => $kgtSkr1->id,
            'expense_type_id' => $etAtk->id,
            'account_code' => '5.1.02.01',
            'description' => 'Bahan Material Bangunan & Cat Fasad Tahan Cuaca',
            'volume' => 1,
            'unit' => 'Paket',
            'unit_price' => 120000000,
            'total' => 120000000,
        ]);

        // Generate Alerts using DeadlineAlertService
        app(\App\Services\DeadlineAlertService::class)->generateAlerts();

        // Create System Backup Record
        BackupHistory::create([
            'backup_type' => 'daily',
            'status' => 'verified',
            'started_at' => '2026-08-01 01:30:00',
            'completed_at' => '2026-08-01 01:30:15',
            'database_size' => 485000,
            'document_count' => 5,
            'document_size' => 5120000,
            'backup_path_reference' => 'daily/2026-08-01-013000',
            'message' => 'Backup harian otomatis berhasil dibuat dan diverifikasi SHA-256.',
            'created_by' => null,
        ]);

        // Audit Activity Logs
        ActivityLog::log('seed_demo_data', 'Sistem', 'Data demonstrasi sistem SIMANDA berhasil dimuat', null);
    }
}
