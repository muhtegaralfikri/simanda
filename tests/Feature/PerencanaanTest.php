<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\BudgetPlan;
use App\Models\BudgetYear;
use App\Models\ExpenseType;
use App\Models\FundingSource;
use App\Models\Program;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerencanaanTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

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
    }

    // 1. Admin can create program
    public function test_01_admin_can_create_program(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/programs', [
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-BAP-01',
            'program_name' => 'Program Perencanaan Pembangunan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('programs', ['program_code' => 'PRG-BAP-01']);
    }

    // 2. PPTK can create program for own unit
    public function test_02_pptk_can_create_program_for_own_unit(): void
    {
        $this->actingAs($this->pptkBappeda);

        $response = $this->post('/programs', [
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-BAP-02',
            'program_name' => 'Program Infrastruktur Bappeda',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('programs', ['program_code' => 'PRG-BAP-02']);
    }

    // 3. PPTK cannot edit program for another unit
    public function test_03_pptk_cannot_edit_program_for_another_unit(): void
    {
        $programDinkes = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->dinkes->id,
            'program_code' => 'PRG-DIN-01',
            'program_name' => 'Program Kesehatan',
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->put("/programs/{$programDinkes->id}", [
            'unit_id' => $this->dinkes->id,
            'program_code' => 'PRG-DIN-01-EDIT',
            'program_name' => 'Hack Name',
        ]);

        $response->assertStatus(403);
    }

    // 4. Pimpinan cannot create program
    public function test_04_pimpinan_cannot_create_program(): void
    {
        $this->actingAs($this->pimpinan);

        $response = $this->post('/programs', [
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-PIMP-01',
            'program_name' => 'Program Pimpinan',
        ]);

        $response->assertStatus(403);
    }

    // 5. Program with duplicate code in same year & unit is rejected
    public function test_05_duplicate_program_code_rejected(): void
    {
        Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-DUP',
            'program_name' => 'Program First',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/programs', [
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-DUP',
            'program_name' => 'Program Second',
        ]);

        $response->assertSessionHasErrors('program_code');
    }

    // 6. Program in closed budget year cannot be edited
    public function test_06_program_in_closed_year_cannot_be_edited(): void
    {
        $closedYear = BudgetYear::create([
            'year' => 2025,
            'name' => 'TA 2025 Closed',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => false,
            'is_closed' => true,
        ]);

        $progClosed = Program::create([
            'budget_year_id' => $closedYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-CLOSED',
            'program_name' => 'Program Old',
        ]);

        $this->actingAs($this->admin);

        $response = $this->put("/programs/{$progClosed->id}", [
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-CLOSED-MOD',
            'program_name' => 'Program New Name',
        ]);

        $response->assertStatus(403);
    }

    // 7. Admin can create activity
    public function test_07_admin_can_create_activity(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-01',
            'program_name' => 'Program Bappeda',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/activities', [
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-001',
            'activity_name' => 'Kegiatan Bimtek Bappeda',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-05',
            'budget_ceiling' => 50000000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', ['activity_code' => 'KGT-001']);
    }

    // 8. PPTK can create activity for self & unit
    public function test_08_pptk_can_create_activity(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-02',
            'program_name' => 'Program Riset',
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post('/activities', [
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-002',
            'activity_name' => 'Kegiatan Penyusunan RKPD',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-10',
            'budget_ceiling' => 100000000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', ['activity_code' => 'KGT-002']);
    }

    // 9. PPTK cannot create activity for another unit
    public function test_09_pptk_cannot_create_activity_for_another_unit(): void
    {
        $programDinkes = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->dinkes->id,
            'program_code' => 'PRG-DIN-02',
            'program_name' => 'Program Dinkes',
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post('/activities', [
            'unit_id' => $this->dinkes->id,
            'program_id' => $programDinkes->id,
            'person_in_charge_id' => $this->pptkDinkes->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-DIN-001',
            'activity_name' => 'Kegiatan Dinkes Illegal',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-05',
            'budget_ceiling' => 20000000,
        ]);

        $response->assertSessionHasErrors('program_id');
    }

    // 10. PPTK cannot assign another PPTK as person in charge
    public function test_10_pptk_forced_to_self_pic(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-03',
            'program_name' => 'Program 3',
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post('/activities', [
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkDinkes->id, // Attempting another user
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-003',
            'activity_name' => 'Kegiatan 3',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'budget_ceiling' => 30000000,
        ]);

        $response->assertRedirect();
        // Database should record person_in_charge_id as pptkBappeda->id
        $this->assertDatabaseHas('activities', [
            'activity_code' => 'KGT-003',
            'person_in_charge_id' => $this->pptkBappeda->id,
        ]);
    }

    // 11. End date before start date is rejected
    public function test_11_invalid_dates_rejected(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-DATE',
            'program_name' => 'Program Date',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/activities', [
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-DATE-ERR',
            'activity_name' => 'Kegiatan Date Err',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-01', // Before start
            'budget_ceiling' => 10000000,
        ]);

        $response->assertSessionHasErrors('end_date');
    }

    // 12. Program not matching unit is rejected
    public function test_12_mismatched_program_unit_rejected(): void
    {
        $programDinkes = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->dinkes->id,
            'program_code' => 'PRG-DIN-MIS',
            'program_name' => 'Program Dinkes',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/activities', [
            'unit_id' => $this->bappeda->id, // Bappeda unit with Dinkes program
            'program_id' => $programDinkes->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-MISMATCH',
            'activity_name' => 'Kegiatan Mismatch',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'budget_ceiling' => 15000000,
        ]);

        $response->assertSessionHasErrors('program_id');
    }

    // 13. Negative budget ceiling is rejected
    public function test_13_negative_budget_ceiling_rejected(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-NEG',
            'program_name' => 'Program Neg',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/activities', [
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-NEG',
            'activity_name' => 'Kegiatan Negative Ceiling',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'budget_ceiling' => -50000,
        ]);

        $response->assertSessionHasErrors('budget_ceiling');
    }

    // 14. Pimpinan can only view activity
    public function test_14_pimpinan_read_only(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-VIEW',
            'program_name' => 'Program View',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-VIEW',
            'activity_name' => 'Kegiatan View Only',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'budget_ceiling' => 10000000,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->pimpinan);

        $this->get("/activities/{$activity->id}")->assertStatus(200);

        // Edit attempt
        $this->get("/activities/{$activity->id}/edit")->assertStatus(403);
    }

    // 15. Verifier cannot edit activity
    public function test_15_verifier_cannot_edit_activity(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-VERIF',
            'program_name' => 'Program Verif',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-VERIF',
            'activity_name' => 'Kegiatan Verif',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'budget_ceiling' => 10000000,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->verifier);

        $this->put("/activities/{$activity->id}", [
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-VERIF-MOD',
            'activity_name' => 'Modified Name',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-05',
            'budget_ceiling' => 10000000,
        ])->assertStatus(403);
    }

    // 16. RAB calculates total = volume * unit_price
    public function test_16_rab_calculates_total(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-RAB-1',
            'program_name' => 'Program RAB',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-RAB-1',
            'activity_name' => 'Kegiatan RAB 1',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 5000000,
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $this->post("/activities/{$activity->id}/budget-plans", [
            'expense_type_id' => $this->atk->id,
            'description' => 'Kertas A4 80gr',
            'volume' => 10,
            'unit' => 'Rim',
            'unit_price' => 60000,
        ])->assertRedirect();

        $this->assertDatabaseHas('budget_plans', [
            'activity_id' => $activity->id,
            'description' => 'Kertas A4 80gr',
            'volume' => 10,
            'unit_price' => 60000,
            'total' => 600000, // 10 * 60,000
        ]);
    }

    // 17. RAB exceeding budget ceiling is rejected
    public function test_17_rab_exceeding_ceiling_rejected(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-EXCEED',
            'program_name' => 'Program Exceed',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-EXCEED',
            'activity_name' => 'Kegiatan Exceed',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 1000000, // Pagu 1 juta
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$activity->id}/budget-plans", [
            'expense_type_id' => $this->atk->id,
            'description' => 'Laptop Mahal',
            'volume' => 1,
            'unit' => 'Unit',
            'unit_price' => 1500000, // 1.5 juta exceeds 1.0 juta
        ]);

        $response->assertSessionHasErrors('unit_price');
    }

    // 18. RAB on cancelled activity cannot be added
    public function test_18_rab_on_cancelled_activity_cannot_be_added(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-CANCEL',
            'program_name' => 'Program Cancel',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-CANCEL',
            'activity_name' => 'Kegiatan Cancelled',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 5000000,
            'status' => 'cancelled',
            'cancellation_reason' => 'Dibatalkan oleh pimpinan',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$activity->id}/budget-plans", [
            'expense_type_id' => $this->atk->id,
            'description' => 'Item Cancelled',
            'volume' => 1,
            'unit' => 'Pkt',
            'unit_price' => 100000,
        ]);

        $response->assertStatus(403);
    }

    // 19. RAB in closed budget year cannot be edited
    public function test_19_rab_in_closed_year_cannot_be_edited(): void
    {
        $closedYear = BudgetYear::create([
            'year' => 2024,
            'name' => 'TA 2024 Closed',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'is_active' => false,
            'is_closed' => true,
        ]);

        $program = Program::create([
            'budget_year_id' => $closedYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-CLOSED-RAB',
            'program_name' => 'Program Closed',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $closedYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-CLOSED-RAB',
            'activity_name' => 'Kegiatan Closed',
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-05',
            'budget_ceiling' => 5000000,
            'created_by' => $this->pptkBappeda->id,
        ]);

        $bp = BudgetPlan::create([
            'activity_id' => $activity->id,
            'expense_type_id' => $this->atk->id,
            'account_code' => '5.1.02.01',
            'description' => 'Item Old',
            'volume' => 1,
            'unit' => 'Pkt',
            'unit_price' => 100000,
            'total' => 100000,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put("/budget-plans/{$bp->id}", [
            'expense_type_id' => $this->atk->id,
            'description' => 'Item Edited',
            'volume' => 2,
            'unit' => 'Pkt',
            'unit_price' => 100000,
        ]);

        $response->assertStatus(403);
    }

    // 20. Inactive expense type cannot be selected
    public function test_20_inactive_expense_type_rejected(): void
    {
        $inactiveEt = ExpenseType::create([
            'code' => '9.9.99.99',
            'name' => 'Expense Type Non-Active',
            'is_active' => false,
        ]);

        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-ET-INACTIVE',
            'program_name' => 'Program ET',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-ET-INACTIVE',
            'activity_name' => 'Kegiatan ET',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 5000000,
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$activity->id}/budget-plans", [
            'expense_type_id' => $inactiveEt->id,
            'description' => 'Item Inactive ET',
            'volume' => 1,
            'unit' => 'Pkt',
            'unit_price' => 100000,
        ]);

        $response->assertSessionHasErrors('expense_type_id');
    }

    // 21. PPTK cannot edit RAB of another user's activity
    public function test_21_pptk_cannot_edit_rab_of_other_user(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-ADMIN-ACT',
            'program_name' => 'Program Admin',
        ]);

        $activityAdmin = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->admin->id, // Admin is PIC
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-ADMIN-PIC',
            'activity_name' => 'Kegiatan Admin PIC',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 5000000,
            'created_by' => $this->admin->id,
        ]);

        $bp = BudgetPlan::create([
            'activity_id' => $activityAdmin->id,
            'expense_type_id' => $this->atk->id,
            'account_code' => '5.1.02.01',
            'description' => 'Item Admin',
            'volume' => 1,
            'unit' => 'Pkt',
            'unit_price' => 100000,
            'total' => 100000,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->put("/budget-plans/{$bp->id}", [
            'expense_type_id' => $this->atk->id,
            'description' => 'Item Hijacked',
            'volume' => 2,
            'unit' => 'Pkt',
            'unit_price' => 100000,
        ]);

        $response->assertStatus(403);
    }

    // 22. Activity without RAB cannot be set as planned
    public function test_22_activity_without_rab_cannot_be_planned(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-NO-RAB',
            'program_name' => 'Program No RAB',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-NO-RAB',
            'activity_name' => 'Kegiatan Without RAB',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 5000000,
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$activity->id}/plan");
        $response->assertSessionHasErrors('status');
    }

    // 23. Activity with total RAB less than budget ceiling cannot be set as planned
    public function test_23_activity_under_allocated_cannot_be_planned(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-UNDER',
            'program_name' => 'Program Under',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-UNDER',
            'activity_name' => 'Kegiatan Under Allocated',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 10000000, // Pagu 10 juta
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        BudgetPlan::create([
            'activity_id' => $activity->id,
            'expense_type_id' => $this->atk->id,
            'account_code' => '5.1.02.01',
            'description' => 'Item Partial',
            'volume' => 1,
            'unit' => 'Pkt',
            'unit_price' => 5000000, // Only 5 juta of 10 juta
            'total' => 5000000,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$activity->id}/plan");
        $response->assertSessionHasErrors('status');
    }

    // 24. Activity with total RAB equal to budget ceiling can be set as planned
    public function test_24_activity_fully_allocated_can_be_planned(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-FULL',
            'program_name' => 'Program Full',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-FULL',
            'activity_name' => 'Kegiatan Fully Allocated',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 10000000, // Pagu 10 juta
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        BudgetPlan::create([
            'activity_id' => $activity->id,
            'expense_type_id' => $this->atk->id,
            'account_code' => '5.1.02.01',
            'description' => 'Item Full',
            'volume' => 1,
            'unit' => 'Pkt',
            'unit_price' => 10000000, // Exactly 10 juta
            'total' => 10000000,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$activity->id}/plan");
        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'planned',
        ]);
    }

    // 25. Cancelling activity requires a reason
    public function test_25_cancelling_activity_requires_reason(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-CNC-REASON',
            'program_name' => 'Program Cnc Reason',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-CNC-REASON',
            'activity_name' => 'Kegiatan Cancel Reason',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 5000000,
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $response = $this->post("/activities/{$activity->id}/cancel", [
            'cancellation_reason' => '', // Empty reason
        ]);

        $response->assertSessionHasErrors('cancellation_reason');

        // Valid cancellation
        $responseValid = $this->post("/activities/{$activity->id}/cancel", [
            'cancellation_reason' => 'Perubahan prioritas anggaran Pemda 2026',
        ]);

        $responseValid->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Perubahan prioritas anggaran Pemda 2026',
        ]);
    }

    // 26. Status change is recorded in activity log
    public function test_26_status_change_recorded_in_activity_log(): void
    {
        $program = Program::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_code' => 'PRG-LOG',
            'program_name' => 'Program Log',
        ]);

        $activity = Activity::create([
            'budget_year_id' => $this->activeYear->id,
            'unit_id' => $this->bappeda->id,
            'program_id' => $program->id,
            'person_in_charge_id' => $this->pptkBappeda->id,
            'funding_source_id' => $this->apbd->id,
            'activity_code' => 'KGT-LOG',
            'activity_name' => 'Kegiatan Logged',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'budget_ceiling' => 5000000,
            'status' => 'draft',
            'created_by' => $this->pptkBappeda->id,
        ]);

        $this->actingAs($this->pptkBappeda);

        $this->post("/activities/{$activity->id}/cancel", [
            'cancellation_reason' => 'Testing Activity Log Audit Trail',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Kegiatan',
            'action' => 'cancel',
            'subject_id' => $activity->id,
        ]);
    }
}
