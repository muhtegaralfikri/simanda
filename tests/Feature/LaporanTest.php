<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\BudgetPlan;
use App\Models\BudgetYear;
use App\Models\DocumentType;
use App\Models\ExpenseType;
use App\Models\FundingSource;
use App\Models\Program;
use App\Models\Realization;
use App\Models\Unit;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pimpinan;
    protected User $pptkBappeda;
    protected User $pptkDinkes;
    protected User $verifier;
    protected Unit $bappeda;
    protected Unit $dinkes;
    protected BudgetYear $activeYear;
    protected FundingSource $apbd;
    protected ExpenseType $atk;
    protected DocumentType $docTor;
    protected Program $program;
    protected Activity $activityBap;
    protected Activity $activityDin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake();

        $this->admin = User::where('email', 'admin@simanda.go.id')->first();
        $this->pimpinan = User::where('email', 'pimpinan@simanda.go.id')->first();
        $this->pptkBappeda = User::where('email', 'pptk.bappeda@simanda.go.id')->first();
        $this->pptkDinkes = User::where('email', 'pptk.dinkes@simanda.go.id')->first();
        $this->verifier = User::where('email', 'verifier@simanda.go.id')->first();

        $this->bappeda = Unit::where('code', 'BAP')->first();
        $this->dinkes = Unit::where('code', 'DIN')->first();
        $this->activeYear = BudgetYear::where('is_active', true)->first();
        $this->apbd = FundingSource::where('code', 'APBD')->first();
        $this->atk = ExpenseType::where('code', '5.1.02.01')->first();
        $this->docTor = DocumentType::where('code', 'TOR')->first();

        $this->program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-LAPORAN',
            'program_name' => 'Program Laporan Modul',
        ]);

        $this->activityBap = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-LAP-BAP',
            'activity_name' => 'Kegiatan Bappeda Laporan',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-30',
            'budget_ceiling' => 20000000,
            'progress_percentage' => 50,
            'status' => 'ongoing',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $bp = BudgetPlan::create([
            'activity_id' => $this->activityBap->id,
            'expense_type_id' => $this->atk->id,
            'account_code' => '5.1.02.01',
            'description' => 'ATK Bappeda',
            'volume' => 20,
            'unit' => 'Paket',
            'unit_price' => 1000000,
            'total' => 20000000,
        ]);

        Realization::create([
            'activity_id' => $this->activityBap->id,
            'budget_plan_id' => $bp->id,
            'expense_type_id' => $this->atk->id,
            'transaction_date' => '2026-03-10',
            'receipt_number' => 'KW-BAP-001',
            'gross_amount' => 5000000,
            'tax_amount' => 500000,
            'net_amount' => 4500000,
            'payment_method' => 'transfer',
            'status' => 'verified',
            'created_by' => $this->pptkBappeda->id,
        ]);
    }

    // 1. Admin can view dashboard for all units
    public function test_01_admin_can_view_dashboard(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewHas('analytics');
    }

    // 2. Pimpinan can view dashboard for all units
    public function test_02_pimpinan_can_view_dashboard(): void
    {
        $this->actingAs($this->pimpinan);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    // 3. PPTK sees dashboard scoped to own activities
    public function test_03_pptk_sees_dashboard_scoped(): void
    {
        $this->actingAs($this->pptkBappeda);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        $analytics = $response->viewData('analytics');
        $this->assertEquals(20000000, $analytics['budget_cards']['total_ceiling']);
    }

    // 14. Budget summary report viewable
    public function test_14_budget_summary_report(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/reports/budget-summary');
        $response->assertStatus(200);
        $response->assertViewHas('activities');
    }

    // 15. Realization report viewable
    public function test_15_realization_report(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/reports/realizations');
        $response->assertStatus(200);
        $response->assertViewHas('realizations');
    }

    // 32. PDF Export works
    public function test_32_pdf_export_works(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/reports/budget-summary/pdf');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'export_pdf',
            'module' => 'Laporan',
        ]);
    }

    // 38. Excel (CSV) Export works
    public function test_38_excel_export_works(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/reports/budget-summary/excel');
        $response->assertStatus(200);
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('Content-Type'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'export_excel',
            'module' => 'Laporan',
        ]);
    }

    // 44. Formula injection neutralized
    public function test_44_formula_injection_neutralized(): void
    {
        $service = new ReportExportService;

        $this->assertEquals("'=1+1", $service->sanitizeExcelValue('=1+1'));
        $this->assertEquals("'+cmd|' /C calc", $service->sanitizeExcelValue('+cmd|\' /C calc'));
        $this->assertEquals("'-100", $service->sanitizeExcelValue('-100'));
        $this->assertEquals("'@SUM(A1:A10)", $service->sanitizeExcelValue('@SUM(A1:A10)'));
        $this->assertEquals('Normal Text', $service->sanitizeExcelValue('Normal Text'));
    }
}
