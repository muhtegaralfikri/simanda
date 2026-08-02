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
use App\Services\ActivityClosingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UATFinalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pimpinan;
    protected User $pptk;
    protected User $verifier;
    protected Unit $unit;
    protected BudgetYear $by;
    protected FundingSource $fs;
    protected ExpenseType $et;
    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake();

        $this->admin = User::where('email', 'admin@simanda.go.id')->first();
        $this->pimpinan = User::where('email', 'pimpinan@simanda.go.id')->first();
        $this->pptk = User::where('email', 'pptk.bappeda@simanda.go.id')->first();
        $this->verifier = User::where('email', 'verifier@simanda.go.id')->first();

        $this->unit = Unit::where('code', 'BAP')->first();
        $this->by = BudgetYear::where('is_active', true)->first();
        $this->fs = FundingSource::where('code', 'APBD')->first();
        $this->et = ExpenseType::where('code', '5.1.02.01')->first();

        $this->program = Program::create([
            'budget_year_id' => $this->by->id,
            'unit_id' => $this->unit->id,
            'program_code' => 'PRG-UAT-FINAL',
            'program_name' => 'Program UAT Final',
        ]);
    }

    // 1. UAT Complete Lifecycle: Draft -> Planned -> Ongoing -> Realization & Doc -> Submit -> Verify -> Closed (Completed)
    public function test_01_uat_complete_lifecycle(): void
    {
        // 1. Create Draft Activity
        $activity = Activity::create([
            'budget_year_id' => $this->by->id,
            'unit_id' => $this->unit->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptk->id,
            'funding_source_id' => $this->fs->id,
            'activity_code' => 'KGT-UAT-001',
            'activity_name' => 'Kegiatan UAT End-to-End',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-30',
            'budget_ceiling' => 10000000,
            'progress_percentage' => 0,
            'status' => 'draft',
            'created_by' => $this->pptk->id,
        ]);

        // 2. Create Budget Plan (RAB = Ceiling)
        $bp = BudgetPlan::create([
            'activity_id' => $activity->id,
            'expense_type_id' => $this->et->id,
            'account_code' => '5.1.02.01',
            'description' => 'ATK UAT',
            'volume' => 10,
            'unit' => 'Paket',
            'unit_price' => 1000000,
            'total' => 10000000,
        ]);

        // 3. Set Planned
        $this->actingAs($this->pptk);
        $resPlan = $this->post("/activities/{$activity->id}/plan");
        $resPlan->assertStatus(302);
        $this->assertEquals('planned', $activity->fresh()->status);

        // 4. Start Activity
        $resStart = $this->post("/activities/{$activity->id}/start");
        $resStart->assertStatus(302);
        $this->assertEquals('ongoing', $activity->fresh()->status);

        // 5. Update Progress to 100%
        $this->post("/activities/{$activity->id}/progress", ['progress_percentage' => 100, 'progress_note' => 'Pekerjaan selesai 100%']);

        // 6. Upload Required Documents for all required document types
        $requiredTypes = DocumentType::where('is_required', true)->get();
        $uploadedDocs = [];
        foreach ($requiredTypes as $dt) {
            $ext = explode(',', $dt->allowed_extensions)[0] ?? 'pdf';
            $file = UploadedFile::fake()->create("doc_{$dt->code}.{$ext}", 500);
            $this->post("/activities/{$activity->id}/documents", [
                'document_type_id' => $dt->id,
                'file' => $file,
                'description' => "Dokumen {$dt->name}",
            ]);
            $uploadedDocs[] = ActivityDocument::where('activity_id', $activity->id)->where('document_type_id', $dt->id)->first();
        }

        // 7. Create Realization
        $this->post("/activities/{$activity->id}/realizations", [
            'budget_plan_id' => $bp->id,
            'expense_type_id' => $this->et->id,
            'transaction_date' => '2026-03-15',
            'receipt_number' => 'KW-UAT-001',
            'gross_amount' => 10000000,
            'tax_amount' => 1000000,
            'payment_method' => 'transfer',
            'description' => 'Pembayaran UAT ATK',
        ]);
        $rel = Realization::where('activity_id', $activity->id)->first();

        // 8. Submit Realization & Submit Activity for Verification
        $this->post("/realizations/{$rel->id}/submit");
        $resSubmit = $this->post("/activities/{$activity->id}/submit-verification");
        $resSubmit->assertSessionHasNoErrors();
        $this->assertEquals('waiting_verification', $activity->fresh()->status);

        // 9. Verifier Verifies Realization & Documents
        $this->actingAs($this->verifier);
        $this->post("/realizations/{$rel->id}/verify", ['decision' => 'verified', 'notes' => 'Transaksi valid']);
        foreach ($uploadedDocs as $doc) {
            if ($doc) {
                $this->post("/documents/{$doc->id}/verify", ['decision' => 'valid', 'notes' => 'Dokumen sah']);
            }
        }

        // 10. Close Activity
        $resClose = $this->post("/admin/verifications/{$activity->id}/close", [
            'closing_note' => 'Penutupan kegiatan UAT berhasil 100%',
        ]);

        $finalAct = $activity->fresh();
        $this->assertEquals('completed', $finalAct->status);
        $this->assertEquals(10000000, $finalAct->verified_realization_total);
        $this->assertEquals(0, $finalAct->final_remaining_budget);
        $this->assertTrue($finalAct->isClosedOrLocked());
    }

    // 2. Illegal transition prevention
    public function test_02_illegal_transition_prevention(): void
    {
        $activity = Activity::create([
            'budget_year_id' => $this->by->id,
            'unit_id' => $this->unit->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptk->id,
            'funding_source_id' => $this->fs->id,
            'activity_code' => 'KGT-ILLEGAL',
            'activity_name' => 'Kegiatan Illegal Transition',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-30',
            'budget_ceiling' => 5000000,
            'progress_percentage' => 0,
            'status' => 'draft',
            'created_by' => $this->pptk->id,
        ]);

        $this->actingAs($this->verifier);
        $this->expectException(ValidationException::class);

        $closingService = app(ActivityClosingService::class);
        $closingService->closeActivity($activity, null, 'Coba penutupan ilegal', $this->verifier);
    }

    // 3. Rejected realization does not deduct budget ceiling
    public function test_03_rejected_realization_does_not_deduct_ceiling(): void
    {
        $activity = Activity::create([
            'budget_year_id' => $this->by->id,
            'unit_id' => $this->unit->id,
            'program_id' => $this->program->id,
            'person_in_charge_id' => $this->pptk->id,
            'funding_source_id' => $this->fs->id,
            'activity_code' => 'KGT-REJ-01',
            'activity_name' => 'Kegiatan Realisasi Ditolak',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-30',
            'budget_ceiling' => 10000000,
            'status' => 'ongoing',
            'created_by' => $this->pptk->id,
        ]);

        $bp = BudgetPlan::create([
            'activity_id' => $activity->id,
            'expense_type_id' => $this->et->id,
            'description' => 'Uraian',
            'volume' => 1,
            'unit' => 'Paket',
            'unit_price' => 10000000,
            'total' => 10000000,
        ]);

        Realization::create([
            'activity_id' => $activity->id,
            'budget_plan_id' => $bp->id,
            'expense_type_id' => $this->et->id,
            'transaction_date' => '2026-03-15',
            'receipt_number' => 'KW-REJ',
            'gross_amount' => 5000000,
            'tax_amount' => 0,
            'net_amount' => 5000000,
            'status' => 'rejected',
            'created_by' => $this->pptk->id,
        ]);

        $this->assertEquals(0, $activity->fresh()->verified_realization_total);
        $this->assertEquals(10000000, $activity->fresh()->final_remaining_budget);
    }
}
