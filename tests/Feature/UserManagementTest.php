<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', 'admin')->first();
        $this->unit = Unit::first();
    }

    public function test_admin_can_view_users_list_and_filter(): void
    {
        $response = $this->actingAs($this->admin)->get(route('master.users.index', [
            'search' => $this->admin->name,
            'role' => 'admin',
        ]));

        $response->assertStatus(200);
        $response->assertSee($this->admin->name);
    }

    public function test_admin_can_create_new_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('master.users.store'), [
            'name' => 'User Baru Test',
            'email' => 'userbaru@simanda.go.id',
            'phone' => '081299998888',
            'role' => 'pptk',
            'unit_id' => $this->unit->id,
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'userbaru@simanda.go.id',
            'role' => 'pptk',
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_admin_can_update_existing_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Lama Name',
            'email' => 'lama@simanda.go.id',
            'role' => 'verifier',
        ]);

        $response = $this->actingAs($this->admin)->put(route('master.users.update', $user->id), [
            'name' => 'Pengguna Edit',
            'email' => 'lama@simanda.go.id',
            'phone' => '081233334444',
            'role' => 'pimpinan',
            'unit_id' => null,
            'password' => '',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Pengguna Edit',
            'role' => 'pimpinan',
        ]);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('master.users.toggle-active', $user->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_toggle_self_active_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('master.users.toggle-active', $this->admin->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('master.users.destroy', $user->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('master.users.destroy', $this->admin->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
