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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifikasiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pimpinan;
    protected User $pptkBappeda;
    protected User $pptkDinkes;
    protected User $verifier;
    protected Unit $bappeda;
    protected BudgetYear $activeYear;
    protected FundingSource $apbd;
    protected ExpenseType $atk;
    protected Program $program;
    protected Activity $activity;
    protected BudgetPlan $budgetPlan;

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
        $this->activeYear = BudgetYear::where('is_active', true)->first();
        $this->apbd = FundingSource::where('code', 'APBD')->first();
        $this->atk = ExpenseType::where('code', '5.1.02.01')->first();

        $this->program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-VERIF',
            'program_name' => 'Program Modul Verifikasi',
        ]);

        $this->activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-VRF-01',
            'activity_name' => 'Kegiatan Siap Diverifikasi',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-30',
            'budget_ceiling' => 10000000,
            'progress_percentage' => 100,
            'status' => 'ongoing',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->budgetPlan = BudgetPlan::create([
            'activity_id' => $this->activity->id,
            'expense_type_id' => $this->atk->id,
            'account_code' => '5.1.02.01',
            'description' => 'ATK Sosialisasi',
            'volume' => 10,
            'unit' => 'Paket',
            'unit_price' => 1000000,
            'total' => 10000000,
        ]);

        // Upload all required document types
        $requiredTypes = DocumentType::where('is_active', true)->where('is_required', true)->get();
        foreach ($requiredTypes as $dt) {
            ActivityDocument::create([
                'activity_id' => $this->activity->id,
                'document_type_id' => $dt->id,
                'original_name' => strtolower($dt->code).'_doc.pdf',
                'stored_name' => strtolower($dt->code).'_doc.pdf',
                'file_path' => 'private/documents/2026/BAP/1/'.strtolower($dt->code).'.pdf',
                'file_size' => 2048,
                'mime_type' => 'application/pdf',
                'status' => 'uploaded',
                'version' => 1,
                'is_current' => true,
                'uploaded_by' => $this->pptkBappeda->id,
            ]);
        }
    }

    // 1. PPTK can submit eligible activity for verification
    public function test_01_pptk_can_submit_eligible_activity(): void
    {
        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->activity->id}/submit-verification");
        $response->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'id' => $this->activity->id,
            'status' => 'waiting_verification',
            'submission_status' => 'submitted',
            'verification_round' => 1,
        ]);
    }

    // 2. PPTK cannot submit another user's activity
    public function test_02_pptk_cannot_submit_other_user_activity(): void
    {
        $this->actingAs($this->pptkDinkes);

        $response = $this->post("/activities/{$this->activity->id}/submit-verification");
        $response->assertStatus(403);
    }

    // 3. Activity with progress < 100 rejected
    public function test_03_activity_progress_less_than_100_rejected(): void
    {
        $this->activity->update(['progress_percentage' => 80]);
        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->activity->id}/submit-verification");
        $response->assertSessionHasErrors('progress_percentage');
    }

    // 4. Activity with draft realization rejected
    public function test_04_activity_with_draft_realization_rejected(): void
    {
        Realization::create([
            'activity_id' => $this->activity->id,
            'budget_plan_id' => $this->budgetPlan->id,
            'expense_type_id' => $this->atk->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-DRAFT',
            'gross_amount' => 1000000,
            'tax_amount' => 0,
            'net_amount' => 1000000,
            'payment_method' => 'transfer',
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$this->activity->id}/submit-verification");
        $response->assertSessionHasErrors('realizations');
    }

    // 13 & 14 & 15. Admin, Pimpinan, PPTK cannot verify realizations (Only Verifier allowed)
    public function test_13_14_15_only_verifier_can_verify_realization(): void
    {
        $rel = Realization::create([
            'activity_id' => $this->activity->id,
            'budget_plan_id' => $this->budgetPlan->id,
            'expense_type_id' => $this->atk->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-SUBMIT',
            'gross_amount' => 1000000,
            'tax_amount' => 0,
            'net_amount' => 1000000,
            'payment_method' => 'transfer',
            'status' => 'submitted',
            'created_by' => $this->pptkBappeda->id,
        ]);

        // Admin forbidden
        $this->actingAs($this->admin);
        $this->post("/realizations/{$rel->id}/verify", ['decision' => 'verified'])->assertStatus(403);

        // Pimpinan forbidden
        $this->actingAs($this->pimpinan);
        $this->post("/realizations/{$rel->id}/verify", ['decision' => 'verified'])->assertStatus(403);

        // PPTK forbidden
        $this->actingAs($this->pptkBappeda);
        $this->post("/realizations/{$rel->id}/verify", ['decision' => 'verified'])->assertStatus(403);
    }

    // 16 & 17. Verifier can approve submitted realization
    public function test_16_17_verifier_can_approve_submitted_realization(): void
    {
        $rel = Realization::create([
            'activity_id' => $this->activity->id,
            'budget_plan_id' => $this->budgetPlan->id,
            'expense_type_id' => $this->atk->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-VRF-01',
            'gross_amount' => 2000000,
            'tax_amount' => 0,
            'net_amount' => 2000000,
            'payment_method' => 'transfer',
            'status' => 'submitted',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->verifier);

        $response = $this->post("/realizations/{$rel->id}/verify", [
            'decision' => 'verified',
            'notes' => 'Sesuai dengan kuitansi dan ketersediaan barang',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('realizations', [
            'id' => $rel->id,
            'status' => 'verified',
            'verified_by' => $this->verifier->id,
        ]);

        $this->assertDatabaseHas('verifications', [
            'verifiable_type' => Realization::class,
            'verifiable_id' => $rel->id,
            'decision' => 'approved',
            'verifier_id' => $this->verifier->id,
        ]);
    }

    // 20. Notes mandatory for realization revision
    public function test_20_notes_mandatory_for_realization_revision(): void
    {
        $rel = Realization::create([
            'activity_id' => $this->activity->id,
            'budget_plan_id' => $this->budgetPlan->id,
            'expense_type_id' => $this->atk->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-REV-01',
            'gross_amount' => 1000000,
            'tax_amount' => 0,
            'net_amount' => 1000000,
            'payment_method' => 'transfer',
            'status' => 'submitted',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->verifier);

        $response = $this->post("/realizations/{$rel->id}/verify", [
            'decision' => 'revision',
            'notes' => '', // empty notes
        ]);

        $response->assertSessionHasErrors('notes');
    }

    // 26 & 27. Verifier can validate document & valid document cannot be deleted by PPTK
    public function test_26_27_verifier_can_validate_document(): void
    {
        $doc = $this->activity->documents->first();

        $this->actingAs($this->verifier);

        $response = $this->post("/documents/{$doc->id}/verify", [
            'decision' => 'valid',
            'notes' => 'Dokumen TOR sah dan lengkap',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_documents', [
            'id' => $doc->id,
            'status' => 'valid',
            'verified_by' => $this->verifier->id,
        ]);

        // PPTK tries to delete valid document -> 403
        $this->actingAs($this->pptkBappeda);
        $this->delete("/documents/{$doc->id}")->assertStatus(403);
    }

    // 32 & 33. Verifier can request activity revision
    public function test_32_33_verifier_can_request_activity_revision(): void
    {
        $this->activity->update(['status' => 'waiting_verification', 'submission_status' => 'submitted']);

        $this->actingAs($this->verifier);

        $response = $this->post("/admin/verifications/{$this->activity->id}/revision", [
            'notes' => 'Laporan fisik dan beberapa kuitansi tidak jelas.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'id' => $this->activity->id,
            'status' => 'revision',
            'submission_status' => 'revision',
        ]);

        $this->assertDatabaseHas('verifications', [
            'verifiable_type' => Activity::class,
            'verifiable_id' => $this->activity->id,
            'decision' => 'revision',
        ]);
    }

    // 35 & 36. Verifier can reject activity submission
    public function test_35_36_verifier_can_reject_submission(): void
    {
        $this->activity->update(['status' => 'waiting_verification', 'submission_status' => 'submitted']);

        $this->actingAs($this->verifier);

        $response = $this->post("/admin/verifications/{$this->activity->id}/reject", [
            'notes' => 'Pengajuan salah instansi/kegiatan.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'id' => $this->activity->id,
            'status' => 'ongoing',
            'submission_status' => 'rejected',
        ]);
    }

    // 39 & 40 & 41. Verifier can close activity when all conditions met
    public function test_39_40_41_verifier_can_close_activity(): void
    {
        // 1. Submit activity
        $this->activity->update(['status' => 'waiting_verification', 'submission_status' => 'submitted']);

        // 2. Add verified realization (5m of 10m ceiling)
        Realization::create([
            'activity_id' => $this->activity->id,
            'budget_plan_id' => $this->budgetPlan->id,
            'expense_type_id' => $this->atk->id,
            'transaction_date' => '2026-03-05',
            'receipt_number' => 'KW-CLOSE',
            'gross_amount' => 5000000,
            'tax_amount' => 0,
            'net_amount' => 5000000,
            'payment_method' => 'transfer',
            'status' => 'verified',
            'created_by' => $this->pptkBappeda->id,
            'verified_by' => $this->verifier->id,
        ]);

        // 3. Mark all current documents valid
        foreach ($this->activity->documents as $doc) {
            $doc->update(['status' => 'valid', 'verified_by' => $this->verifier->id]);
        }

        $this->actingAs($this->verifier);

        // Attempt closing without remaining_budget_note -> should fail (remaining is 5m)
        $this->post("/admin/verifications/{$this->activity->id}/close", [
            'remaining_budget_note' => '',
        ])->assertSessionHasErrors('remaining_budget_note');

        // With remaining_budget_note -> success
        $response = $this->post("/admin/verifications/{$this->activity->id}/close", [
            'remaining_budget_note' => 'Efisiensi pengadaan bahan sosialisasi.',
            'closing_note' => 'Kegiatan terlaksana dengan hasil sangat baik.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'id' => $this->activity->id,
            'status' => 'completed',
            'submission_status' => 'approved',
            'completed_by' => $this->verifier->id,
        ]);

        // Verify completed activity is read-only for updates
        $this->actingAs($this->pptkBappeda);
        $this->put("/activities/{$this->activity->id}", [
            'activity_name' => 'Attempt Edit Name',
        ])->assertStatus(403);
    }
}
