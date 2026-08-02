<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FondasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_login(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@simanda.go.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_admin_can_access_master_data(): void
    {
        $admin = User::where('email', 'admin@simanda.go.id')->first();
        $this->actingAs($admin);

        $this->get('/master/budget-years')->assertStatus(200);
        $this->get('/master/units')->assertStatus(200);
        $this->get('/master/users')->assertStatus(200);
        $this->get('/master/funding-sources')->assertStatus(200);
        $this->get('/master/expense-types')->assertStatus(200);
        $this->get('/master/document-types')->assertStatus(200);
    }

    public function test_non_admin_cannot_access_master_data(): void
    {
        $pptk = User::where('email', 'pptk.bappeda@simanda.go.id')->first();
        $this->actingAs($pptk);

        $this->get('/master/users')->assertStatus(403);
    }
}
