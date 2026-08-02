<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\BackupHistory;
use App\Models\BudgetYear;
use App\Models\FundingSource;
use App\Models\Program;
use App\Models\SystemAlert;
use App\Models\Unit;
use App\Models\User;
use App\Services\BackupService;
use App\Services\BackupVerificationService;
use App\Services\DeadlineAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StabilisasiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $pimpinan;
    protected User $pptk;
    protected User $verifier;
    protected Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake();

        $this->admin = User::where('email', 'admin@simanda.go.id')->first();
        $this->pimpinan = User::where('email', 'pimpinan@simanda.go.id')->first();
        $this->pptk = User::where('email', 'pptk.bappeda@simanda.go.id')->first();
        $this->verifier = User::where('email', 'verifier@simanda.go.id')->first();

        $unit = Unit::where('code', 'BAP')->first();
        $by = BudgetYear::where('is_active', true)->first();
        $fs = FundingSource::where('code', 'APBD')->first();

        $prg = Program::create([
            'budget_year_id' => $by->id,
            'unit_id' => $unit->id,
            'program_code' => 'PRG-STAB',
            'program_name' => 'Program Stabilisasi',
        ]);

        $this->activity = Activity::create([
            'budget_year_id' => $by->id,
            'unit_id' => $unit->id,
            'program_id' => $prg->id,
            'person_in_charge_id' => $this->pptk->id,
            'funding_source_id' => $fs->id,
            'activity_code' => 'KGT-STAB-01',
            'activity_name' => 'Kegiatan Test Alerting',
            'start_date' => now()->addDays(3)->format('Y-m-d'),
            'end_date' => now()->addDays(10)->format('Y-m-d'),
            'budget_ceiling' => 10000000,
            'progress_percentage' => 0,
            'status' => 'planned',
            'created_by' => $this->pptk->id,
        ]);
    }

    // 1. Planned activity starting soon generates alert
    public function test_01_planned_activity_starting_soon_generates_alert(): void
    {
        $service = new DeadlineAlertService;
        $res = $service->generateAlerts();

        $this->assertGreaterThan(0, $res['created']);
        $this->assertDatabaseHas('system_alerts', [
            'alert_type' => 'activity_starting_soon',
            'subject_id' => $this->activity->id,
            'user_id' => $this->pptk->id,
        ]);
    }

    // 2. Ongoing activity approaching deadline generates alert
    public function test_02_ongoing_activity_approaching_deadline(): void
    {
        $this->activity->update([
            'status' => 'ongoing',
            'start_date' => now()->subDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'progress_percentage' => 30,
        ]);

        $service = new DeadlineAlertService;
        $service->generateAlerts();

        $this->assertDatabaseHas('system_alerts', [
            'alert_type' => 'activity_deadline_approaching',
            'subject_id' => $this->activity->id,
            'severity' => 'warning',
        ]);
    }

    // 3. Overdue activity generates danger alert
    public function test_03_overdue_activity_generates_danger_alert(): void
    {
        $this->activity->update([
            'status' => 'ongoing',
            'end_date' => now()->subDays(2)->format('Y-m-d'),
        ]);

        $service = new DeadlineAlertService;
        $service->generateAlerts();

        $this->assertDatabaseHas('system_alerts', [
            'alert_type' => 'activity_overdue',
            'subject_id' => $this->activity->id,
            'severity' => 'danger',
        ]);
    }

    // 4. Completed activity resolves alert
    public function test_04_completed_activity_resolves_alert(): void
    {
        $service = new DeadlineAlertService;
        $service->generateAlerts();

        $this->activity->update(['status' => 'completed']);

        $res = $service->generateAlerts();

        $this->assertGreaterThan(0, $res['resolved']);
    }

    // 5. Repeated alert generation does not duplicate alert
    public function test_05_repeated_alert_generation_no_duplicate(): void
    {
        $service = new DeadlineAlertService;
        $service->generateAlerts();
        $count1 = SystemAlert::count();

        $service->generateAlerts();
        $count2 = SystemAlert::count();

        $this->assertEquals($count1, $count2);
    }

    // 6. User scoping on alerts
    public function test_06_user_scoping_on_alerts(): void
    {
        $service = new DeadlineAlertService;
        $service->generateAlerts();

        $this->actingAs($this->pptk);
        $resPPTK = $this->get('/admin/alerts');
        $resPPTK->assertStatus(200);

        $this->actingAs($this->admin);
        $resAdmin = $this->get('/admin/alerts');
        $resAdmin->assertStatus(200);
    }

    // 7. Mark alert as read
    public function test_07_mark_alert_as_read(): void
    {
        $service = new DeadlineAlertService;
        $service->generateAlerts();

        $alert = SystemAlert::where('user_id', $this->pptk->id)->first();

        $this->actingAs($this->pptk);
        $response = $this->post("/admin/alerts/{$alert->id}/read");
        $response->assertStatus(302);

        $this->assertNotNull($alert->fresh()->read_at);
    }

    // 8. Admin can run manual backup
    public function test_08_admin_can_run_manual_backup(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/system/backups/run', ['backup_type' => 'daily']);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('backup_histories', [
            'backup_type' => 'daily',
            'status' => 'success',
        ]);
    }

    // 9. Non-admin forbidden from backup
    public function test_09_non_admin_forbidden_from_backup(): void
    {
        $this->actingAs($this->pptk);

        $response = $this->post('/admin/system/backups/run', ['backup_type' => 'daily']);
        $response->assertStatus(403);
    }

    // 10. Backup verification service works
    public function test_10_backup_verification_service_works(): void
    {
        $service = new BackupService;
        $history = $service->runBackup('daily', $this->admin);

        $verifService = new BackupVerificationService;
        $res = $verifService->verifyBackup($history->backup_path_reference);

        $this->assertEquals('success', $res['status']);
        $this->assertDatabaseHas('backup_histories', [
            'id' => $history->id,
            'status' => 'verified',
        ]);
    }

    // 11. Admin can access system health page
    public function test_11_admin_can_access_health_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin/system/health');
        $response->assertStatus(200);
        $response->assertViewHas('health');
    }

    // 12. Non-admin forbidden from health page
    public function test_12_non_admin_forbidden_from_health(): void
    {
        $this->actingAs($this->pptk);

        $response = $this->get('/admin/system/health');
        $response->assertStatus(403);
    }

    // 13. Artisan commands registered and executable
    public function test_13_artisan_commands_registered(): void
    {
        $exitAlerts = Artisan::call('simanda:alerts:generate');
        $this->assertEquals(0, $exitAlerts);

        $exitBackup = Artisan::call('simanda:backup', ['--type' => 'daily']);
        $this->assertEquals(0, $exitBackup);

        $exitVerify = Artisan::call('simanda:backup:verify');
        $this->assertEquals(0, $exitVerify);

        $exitHeartbeat = Artisan::call('simanda:scheduler:heartbeat');
        $this->assertEquals(0, $exitHeartbeat);
    }
}
