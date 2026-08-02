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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PelaksanaanTest extends TestCase
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
    protected Activity $plannedActivity;

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
            'program_code' => 'PRG-PELAKSANAAN',
            'program_name' => 'Program Pelaksanaan Utama',
        ]);

        // Create Planned Activity with RAB equal to Pagu
        $this->plannedActivity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-PLN-01',
            'activity_name' => 'Kegiatan Planned Berjalan',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'budget_ceiling' => 10000000,
            'status' => 'planned',
            'created_by' => $this->pptkBappeda->id,
        ]);

        BudgetPlan::create([
            'activity_id' => $this->plannedActivity->id,
            'expense_type_id' => $this->atk->id,
            'account_code' => '5.1.02.01',
            'description' => 'ATK Pelatihan',
            'volume' => 10,
            'unit' => 'Paket',
            'unit_price' => 1000000,
            'total' => 10000000,
        ]);
    }

    // 1. Admin can start planned activity
    public function test_01_admin_can_start_planned_activity(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post("/activities/{$this->plannedActivity->id}/start");
        $response->assertRedirect();
        $this->assertDatabaseHas('activities', ['id' => $this->plannedActivity->id, 'status' => 'ongoing']);
    }

    // 2. PPTK can start own planned activity
    public function test_02_pptk_can_start_own_planned_activity(): void
    {
        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->plannedActivity->id}/start");
        $response->assertRedirect();
        $this->assertDatabaseHas('activities', ['id' => $this->plannedActivity->id, 'status' => 'ongoing']);
    }

    // 3. PPTK cannot start another user's planned activity
    public function test_03_pptk_cannot_start_other_user_activity(): void
    {
        $this->actingAs($this->pptkDinkes);

        $response = $this->post("/activities/{$this->plannedActivity->id}/start");
        $response->assertStatus(403);
    }

    // 4. Pimpinan cannot start activity
    public function test_04_pimpinan_cannot_start_activity(): void
    {
        $this->actingAs($this->pimpinan);

        $response = $this->post("/activities/{$this->plannedActivity->id}/start");
        $response->assertStatus(403);
    }

    // 5. Verifier cannot start activity
    public function test_05_verifier_cannot_start_activity(): void
    {
        $this->actingAs($this->verifier);

        $response = $this->post("/activities/{$this->plannedActivity->id}/start");
        $response->assertStatus(403);
    }

    // 6. Draft activity cannot be started
    public function test_06_draft_activity_cannot_be_started(): void
    {
        $draftAct = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-DFT-01',
            'activity_name' => 'Kegiatan Draft',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'budget_ceiling' => 5000000,
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$draftAct->id}/start");
        $response->assertStatus(403);
    }

    // 7. Cancelled activity cannot be started
    public function test_07_cancelled_activity_cannot_be_started(): void
    {
        $cncAct = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-CNC-01',
            'activity_name' => 'Kegiatan Cancelled',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'budget_ceiling' => 5000000,
            'status' => 'cancelled',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$cncAct->id}/start");
        $response->assertStatus(403);
    }

    // 8. Activity in closed year cannot be started
    public function test_08_closed_year_activity_cannot_be_started(): void
    {
        $closedYear = BudgetYear::create([
            'year' => 2023,
            'name' => 'TA 2023 Closed',
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'is_active' => false,
            'is_closed' => true,
        ]);

        $progClosed = Program::create([
            'budget_year_id' => $closedYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-2023',
            'program_name' => 'Program 2023',
        ]);

        $actClosed = Activity::create([
            'budget_year_id' => $closedYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $progClosed->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-2023',
            'activity_name' => 'Kegiatan 2023',
            'start_date' => '2023-03-01',
            'end_date' => '2023-03-10',
            'budget_ceiling' => 5000000,
            'status' => 'planned',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post("/activities/{$actClosed->id}/start");
        $response->assertStatus(403);
    }

    // 9. PPTK can update progress of own ongoing activity
    public function test_09_pptk_can_update_progress_ongoing(): void
    {
        $this->plannedActivity->update(['status' => 'ongoing']);
        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->plannedActivity->id}/progress", [
            'progress_percentage' => 45,
            'note' => 'Tahap 1 Pembelian ATK selesai',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'id' => $this->plannedActivity->id,
            'progress_percentage' => 45,
        ]);
    }

    // 10 & 11. Progress below 0 or above 100 is rejected
    public function test_10_11_invalid_progress_percentage_rejected(): void
    {
        $this->plannedActivity->update(['status' => 'ongoing']);
        $this->actingAs($this->pptkBappeda);

        $this->post("/activities/{$this->plannedActivity->id}/progress", [
            'progress_percentage' => -10,
        ])->assertSessionHasErrors('progress_percentage');

        $this->post("/activities/{$this->plannedActivity->id}/progress", [
            'progress_percentage' => 150,
        ])->assertSessionHasErrors('progress_percentage');
    }

    // 12. Planned activity cannot be given progress before starting
    public function test_12_planned_activity_cannot_have_progress(): void
    {
        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->plannedActivity->id}/progress", [
            'progress_percentage' => 20,
        ]);

        $response->assertStatus(403);
    }

    // 14. Progress update creates history in activity_progress_logs
    public function test_14_progress_update_creates_history(): void
    {
        $this->plannedActivity->update(['status' => 'ongoing']);
        $this->actingAs($this->pptkBappeda);

        $this->post("/activities/{$this->plannedActivity->id}/progress", [
            'progress_percentage' => 60,
            'note' => 'Catatan progres 60%',
        ]);

        $this->assertDatabaseHas('activity_progress_logs', [
            'activity_id' => $this->plannedActivity->id,
            'progress_percentage' => 60,
            'note' => 'Catatan progres 60%',
        ]);
    }

    // 16. PPTK can create realization on own ongoing activity
    public function test_16_pptk_can_create_realization(): void
    {
        $this->plannedActivity->update(['status' => 'ongoing']);
        $bp = $this->plannedActivity->budgetPlans->first();

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->plannedActivity->id}/realizations", [
            'budget_plan_id' => $bp->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-001',
            'recipient_name' => 'Toko ATK',
            'gross_amount' => 2500000,
            'tax_amount' => 100000,
            'payment_method' => 'transfer',
            'description' => 'Pembelian Kertas A4',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('realizations', [
            'activity_id' => $this->plannedActivity->id,
            'receipt_number' => 'KW-001',
            'gross_amount' => 2500000,
            'tax_amount' => 100000,
            'net_amount' => 2400000, // 2.5m - 100k
            'status' => 'draft',
        ]);
    }

    // 20. Net amount is calculated by backend
    public function test_20_net_amount_calculated_by_backend(): void
    {
        $this->plannedActivity->update(['status' => 'ongoing']);
        $bp = $this->plannedActivity->budgetPlans->first();

        $this->actingAs($this->pptkBappeda);

        $this->post("/activities/{$this->plannedActivity->id}/realizations", [
            'budget_plan_id' => $bp->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-002',
            'gross_amount' => 1000000,
            'tax_amount' => 50000,
            'payment_method' => 'transfer',
        ]);

        $this->assertDatabaseHas('realizations', [
            'receipt_number' => 'KW-002',
            'net_amount' => 950000, // 1000000 - 50000
        ]);
    }

    // 23. Realization exceeding remaining RAB is rejected
    public function test_23_realization_exceeding_rab_rejected(): void
    {
        $this->plannedActivity->update(['status' => 'ongoing']);
        $bp = $this->plannedActivity->budgetPlans->first(); // Total 10m

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->plannedActivity->id}/realizations", [
            'budget_plan_id' => $bp->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-EXCEED',
            'gross_amount' => 15000000, // 15m > 10m ceiling
            'tax_amount' => 0,
            'payment_method' => 'transfer',
        ]);

        $response->assertSessionHasErrors('gross_amount');
    }

    // 29. Realization can be submitted (draft -> submitted)
    public function test_29_realization_can_be_submitted(): void
    {
        $this->plannedActivity->update(['status' => 'ongoing']);
        $bp = $this->plannedActivity->budgetPlans->first();

        $realization = Realization::create([
            'activity_id' => $this->plannedActivity->id,
            'budget_plan_id' => $bp->id,
            'expense_type_id' => $this->atk->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-SUBMIT',
            'gross_amount' => 2000000,
            'tax_amount' => 0,
            'net_amount' => 2000000,
            'payment_method' => 'transfer',
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/realizations/{$realization->id}/submit");
        $response->assertRedirect();

        $this->assertDatabaseHas('realizations', [
            'id' => $realization->id,
            'status' => 'submitted',
        ]);
    }

    // 33. PPTK can upload document to own activity
    public function test_33_pptk_can_upload_document(): void
    {
        $file = UploadedFile::fake()->create('tor_kegiatan.pdf', 500, 'application/pdf');

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->plannedActivity->id}/documents", [
            'document_type_id' => $this->docTor->id,
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activity_documents', [
            'activity_id' => $this->plannedActivity->id,
            'document_type_id' => $this->docTor->id,
            'original_name' => 'tor_kegiatan.pdf',
            'version' => 1,
            'is_current' => true,
        ]);
    }

    // 35. Executable file is rejected
    public function test_35_executable_file_rejected(): void
    {
        $fakeScript = UploadedFile::fake()->create('malicious.php', 10, 'text/x-php');

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->plannedActivity->id}/documents", [
            'document_type_id' => $this->docTor->id,
            'file' => $fakeScript,
        ]);

        $response->assertSessionHasErrors('file');
    }

    // 39. Unauthorized user cannot download document
    public function test_39_unauthorized_user_cannot_download_document(): void
    {
        $doc = ActivityDocument::create([
            'activity_id' => $this->plannedActivity->id,
            'document_type_id' => $this->docTor->id,
            'original_name' => 'secret_tor.pdf',
            'stored_name' => 'secret_tor.pdf',
            'file_path' => 'private/documents/2026/BAP/1/secret_tor.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => 'uploaded',
            'version' => 1,
            'is_current' => true,
            'uploaded_by' => $this->pptkBappeda->id,
        ]);

        Storage::put('private/documents/2026/BAP/1/secret_tor.pdf', 'dummy content');

        $this->actingAs($this->pptkDinkes); // Other unit PPTK

        $response = $this->get("/documents/{$doc->id}/download");
        $response->assertStatus(403);
    }

    // 40. Pimpinan can download document
    public function test_40_pimpinan_can_download_document(): void
    {
        $doc = ActivityDocument::create([
            'activity_id' => $this->plannedActivity->id,
            'document_type_id' => $this->docTor->id,
            'original_name' => 'tor_pimpinan.pdf',
            'stored_name' => 'tor_pimpinan.pdf',
            'file_path' => 'private/documents/2026/BAP/1/tor_pimpinan.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => 'uploaded',
            'version' => 1,
            'is_current' => true,
            'uploaded_by' => $this->pptkBappeda->id,
        ]);

        Storage::put('private/documents/2026/BAP/1/tor_pimpinan.pdf', 'dummy content');

        $this->actingAs($this->pimpinan);

        $response = $this->get("/documents/{$doc->id}/download");
        $response->assertStatus(200);
    }

    // 43. Replacing document creates new version
    public function test_43_replacing_document_creates_new_version(): void
    {
        $v1 = ActivityDocument::create([
            'activity_id' => $this->plannedActivity->id,
            'document_type_id' => $this->docTor->id,
            'original_name' => 'tor_v1.pdf',
            'stored_name' => 'tor_v1.pdf',
            'file_path' => 'private/documents/2026/BAP/1/tor_v1.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => 'uploaded',
            'version' => 1,
            'is_current' => true,
            'uploaded_by' => $this->pptkBappeda->id,
        ]);

        $fileV2 = UploadedFile::fake()->create('tor_v2.pdf', 600, 'application/pdf');

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/documents/{$v1->id}/replace", [
            'file' => $fileV2,
        ]);

        $response->assertRedirect();

        // Old version set is_current = false
        $this->assertDatabaseHas('activity_documents', [
            'id' => $v1->id,
            'version' => 1,
            'is_current' => false,
        ]);

        // New version set version = 2, is_current = true
        $this->assertDatabaseHas('activity_documents', [
            'activity_id' => $this->plannedActivity->id,
            'document_type_id' => $this->docTor->id,
            'version' => 2,
            'is_current' => true,
        ]);
    }
}
