<?php

namespace App\Http\Controllers;

use App\Models\BudgetYear;
use App\Models\ExpenseType;
use App\Models\FundingSource;
use App\Models\Program;
use App\Models\Unit;
use App\Models\User;
use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected ReportExportService $exportService;

    public function __construct(ReportService $reportService, ReportExportService $exportService)
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    public function index()
    {
        return view('admin.reports.index');
    }

    // 1. Laporan Ringkasan Anggaran
    public function budget(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getBudgetSummaryReport($filters, $user, true);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.budget', array_merge(compact('activities', 'filters'), $masterData));
    }

    public function budgetPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getBudgetSummaryReport($filters, $user, false);

        return $this->exportService->exportPdfHtml(
            'Laporan Ringkasan Anggaran',
            'admin.reports.pdf.budget',
            compact('activities', 'filters'),
            $user,
            'laporan-ringkasan-anggaran'
        );
    }

    public function budgetExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getBudgetSummaryReport($filters, $user, false);

        $headers = ['Kode Kegiatan', 'Nama Kegiatan', 'Unit Kerja', 'Program', 'Sumber Dana', 'Pagu (Rp)', 'Total RAB (Rp)', 'Realisasi Active (Rp)', 'Realisasi Verified (Rp)', 'Sisa Anggaran (Rp)', 'Serapan (%)', 'Status'];

        $rows = [];
        foreach ($activities as $act) {
            $rows[] = [
                $act->activity_code,
                $act->activity_name,
                $act->unit ? $act->unit->code : '-',
                $act->program ? $act->program->program_name : '-',
                $act->fundingSource ? $act->fundingSource->code : '-',
                $act->budget_ceiling,
                $act->total_budget_plan,
                $act->active_realization_total,
                $act->verified_realization_total,
                $act->final_remaining_budget,
                $act->realization_percentage,
                $act->status,
            ];
        }

        return $this->exportService->exportExcel('Laporan Ringkasan Anggaran', $headers, $rows, $user, 'ringkasan-anggaran');
    }

    // 2. Laporan Realisasi Anggaran
    public function realization(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $realizations = $this->reportService->getRealizationReport($filters, $user, true);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.realizations', array_merge(compact('realizations', 'filters'), $masterData));
    }

    public function realizationPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $realizations = $this->reportService->getRealizationReport($filters, $user, false);

        return $this->exportService->exportPdfHtml(
            'Laporan Realisasi Anggaran',
            'admin.reports.pdf.realizations',
            compact('realizations', 'filters'),
            $user,
            'laporan-realisasi-anggaran'
        );
    }

    public function realizationExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $realizations = $this->reportService->getRealizationReport($filters, $user, false);

        $headers = ['Tgl Transaksi', 'No Bukti', 'Kode Kegiatan', 'Nama Kegiatan', 'Unit', 'Uraian RAB', 'Jenis Belanja', 'Penerima/Vendor', 'Bruto (Rp)', 'Pajak (Rp)', 'Bersih (Rp)', 'Status'];

        $rows = [];
        foreach ($realizations as $rel) {
            $rows[] = [
                $rel->transaction_date ? $rel->transaction_date->format('Y-m-d') : '-',
                $rel->receipt_number,
                $rel->activity ? $rel->activity->activity_code : '-',
                $rel->activity ? $rel->activity->activity_name : '-',
                $rel->activity && $rel->activity->unit ? $rel->activity->unit->code : '-',
                $rel->budgetPlan ? $rel->budgetPlan->description : '-',
                $rel->expenseType ? $rel->expenseType->name : '-',
                $rel->recipient_name ?? $rel->vendor_name ?? '-',
                $rel->gross_amount,
                $rel->tax_amount,
                $rel->net_amount,
                $rel->status,
            ];
        }

        return $this->exportService->exportExcel('Laporan Realisasi Anggaran', $headers, $rows, $user, 'realisasi-anggaran');
    }

    // 3. Laporan Kegiatan
    public function activity(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getActivityReport($filters, $user, true);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.activity', array_merge(compact('activities', 'filters'), $masterData));
    }

    public function activityPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getActivityReport($filters, $user, false);

        return $this->exportService->exportPdfHtml(
            'Laporan Pelaksanaan Kegiatan',
            'admin.reports.pdf.activity',
            compact('activities', 'filters'),
            $user,
            'laporan-kegiatan'
        );
    }

    public function activityExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getActivityReport($filters, $user, false);

        $headers = ['Kode', 'Nama Kegiatan', 'Program', 'Unit', 'PPTK', 'Tgl Mulai', 'Tgl Selesai', 'Pagu (Rp)', 'Realisasi Verified (Rp)', 'Sisa Anggaran (Rp)', 'Progres (%)', 'Status'];

        $rows = [];
        foreach ($activities as $act) {
            $rows[] = [
                $act->activity_code,
                $act->activity_name,
                $act->program ? $act->program->program_name : '-',
                $act->unit ? $act->unit->code : '-',
                $act->personInCharge ? $act->personInCharge->name : '-',
                $act->start_date ? $act->start_date->format('Y-m-d') : '-',
                $act->end_date ? $act->end_date->format('Y-m-d') : '-',
                $act->budget_ceiling,
                $act->verified_realization_total,
                $act->final_remaining_budget,
                $act->progress_percentage,
                $act->status,
            ];
        }

        return $this->exportService->exportExcel('Laporan Pelaksanaan Kegiatan', $headers, $rows, $user, 'pelaksanaan-kegiatan');
    }

    // 4. Laporan Progres Kegiatan
    public function progress(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getProgressReport($filters, $user, true);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.progress', array_merge(compact('activities', 'filters'), $masterData));
    }

    public function progressPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getProgressReport($filters, $user, false);

        return $this->exportService->exportPdfHtml(
            'Laporan Progres Kegiatan',
            'admin.reports.pdf.progress',
            compact('activities', 'filters'),
            $user,
            'laporan-progres-kegiatan'
        );
    }

    public function progressExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getProgressReport($filters, $user, false);

        $headers = ['Kode', 'Nama Kegiatan', 'Unit', 'PPTK', 'Progres (%)', 'Catatan Progres Terakhir', 'Status'];

        $rows = [];
        foreach ($activities as $act) {
            $rows[] = [
                $act->activity_code,
                $act->activity_name,
                $act->unit ? $act->unit->code : '-',
                $act->personInCharge ? $act->personInCharge->name : '-',
                $act->progress_percentage,
                $act->progress_note ?? '-',
                $act->status,
            ];
        }

        return $this->exportService->exportExcel('Laporan Progres Kegiatan', $headers, $rows, $user, 'progres-kegiatan');
    }

    // 5. Laporan Kelengkapan Dokumen
    public function documents(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getDocumentReport($filters, $user, true);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.documents', array_merge(compact('activities', 'filters'), $masterData));
    }

    public function documentsPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getDocumentReport($filters, $user, false);

        return $this->exportService->exportPdfHtml(
            'Laporan Kelengkapan Dokumen',
            'admin.reports.pdf.documents',
            compact('activities', 'filters'),
            $user,
            'laporan-kelengkapan-dokumen'
        );
    }

    public function documentsExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getDocumentReport($filters, $user, false);

        $headers = ['Kode', 'Nama Kegiatan', 'Unit', 'PPTK', 'Total Wajib', 'Terunggah', 'Valid', 'Persentase Valid (%)'];

        $rows = [];
        foreach ($activities as $act) {
            $comp = $act->document_completeness;
            $rows[] = [
                $act->activity_code,
                $act->activity_name,
                $act->unit ? $act->unit->code : '-',
                $act->personInCharge ? $act->personInCharge->name : '-',
                $comp['total_required'],
                $comp['fulfilled_required'],
                $comp['valid_required'],
                $comp['valid_percentage'],
            ];
        }

        return $this->exportService->exportExcel('Laporan Kelengkapan Dokumen', $headers, $rows, $user, 'kelengkapan-dokumen');
    }

    // 6. Laporan Verifikasi
    public function verifications(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $verifications = $this->reportService->getVerificationReport($filters, $user, true);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.verifications', array_merge(compact('verifications', 'filters'), $masterData));
    }

    public function verificationsPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $verifications = $this->reportService->getVerificationReport($filters, $user, false);

        return $this->exportService->exportPdfHtml(
            'Laporan Riwayat Verifikasi',
            'admin.reports.pdf.verifications',
            compact('verifications', 'filters'),
            $user,
            'laporan-riwayat-verifikasi'
        );
    }

    public function verificationsExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $verifications = $this->reportService->getVerificationReport($filters, $user, false);

        $headers = ['Tgl Verifikasi', 'Putaran', 'Tipe Objek', 'Keputusan', 'Verifier', 'Catatan'];

        $rows = [];
        foreach ($verifications as $v) {
            $rows[] = [
                $v->verified_at ? $v->verified_at->format('Y-m-d H:i:s') : '-',
                $v->round,
                $v->verifiable_type,
                $v->decision,
                $v->verifier ? $v->verifier->name : '-',
                $v->notes ?? '-',
            ];
        }

        return $this->exportService->exportExcel('Laporan Riwayat Verifikasi', $headers, $rows, $user, 'riwayat-verifikasi');
    }

    // 7. Laporan Serapan Bulanan
    public function absorption(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $data = $this->reportService->getMonthlyAbsorptionReport($filters, $user);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.absorption', array_merge(compact('data', 'filters'), $masterData));
    }

    public function absorptionPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $data = $this->reportService->getMonthlyAbsorptionReport($filters, $user);

        return $this->exportService->exportPdfHtml(
            'Laporan Serapan Bulanan',
            'admin.reports.pdf.absorption',
            compact('data', 'filters'),
            $user,
            'laporan-serapan-bulanan'
        );
    }

    public function absorptionExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $data = $this->reportService->getMonthlyAbsorptionReport($filters, $user);

        $headers = ['Bulan', 'Realisasi Verified Bulan Ini (Rp)', 'Realisasi Verified Kumulatif (Rp)', 'Total Pagu (Rp)', 'Serapan Kumulatif (%)', 'Sisa Anggaran (Rp)'];

        $rows = [];
        foreach ($data['rows'] as $r) {
            $rows[] = [
                $r['month_name'],
                $r['monthly_verified'],
                $r['cumulative_verified'],
                $r['total_pagu'],
                $r['cumulative_absorption_percentage'],
                $r['remaining_budget'],
            ];
        }

        return $this->exportService->exportExcel('Laporan Serapan Bulanan', $headers, $rows, $user, 'serapan-bulanan');
    }

    // 8. Laporan Sisa Anggaran
    public function remainingBudget(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getRemainingBudgetReport($filters, $user, true);
        $masterData = $this->getMasterFilterData();

        return view('admin.reports.remaining_budget', array_merge(compact('activities', 'filters'), $masterData));
    }

    public function remainingBudgetPdf(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getRemainingBudgetReport($filters, $user, false);

        return $this->exportService->exportPdfHtml(
            'Laporan Sisa Anggaran Kegiatan',
            'admin.reports.pdf.remaining_budget',
            compact('activities', 'filters'),
            $user,
            'laporan-sisa-anggaran'
        );
    }

    public function remainingBudgetExcel(Request $request)
    {
        $user = auth()->user();
        $filters = $request->all();
        $activities = $this->reportService->getRemainingBudgetReport($filters, $user, false);

        $headers = ['Kode', 'Nama Kegiatan', 'Unit', 'Pagu (Rp)', 'Realisasi Verified (Rp)', 'Sisa Final (Rp)', 'Catatan Sisa Anggaran', 'Catatan Penutupan', 'Status'];

        $rows = [];
        foreach ($activities as $act) {
            $rows[] = [
                $act->activity_code,
                $act->activity_name,
                $act->unit ? $act->unit->code : '-',
                $act->budget_ceiling,
                $act->verified_realization_total,
                $act->final_remaining_budget,
                $act->remaining_budget_note ?? '-',
                $act->closing_note ?? '-',
                $act->status,
            ];
        }

        return $this->exportService->exportExcel('Laporan Sisa Anggaran', $headers, $rows, $user, 'sisa-anggaran');
    }

    protected function getMasterFilterData(): array
    {
        return [
            'budgetYears' => BudgetYear::orderBy('year', 'desc')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'programs' => Program::where('is_active', true)->orderBy('program_name')->get(),
            'fundingSources' => FundingSource::where('is_active', true)->orderBy('name')->get(),
            'expenseTypes' => ExpenseType::where('is_active', true)->orderBy('code')->get(),
            'pptks' => User::where('role', 'pptk')->orderBy('name')->get(),
        ];
    }
}
