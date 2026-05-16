<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Features\Permissions\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_santri_cannot_access_admin_panel(): void
    {
        $santri = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->actingAs($santri);

        $response = $this->get('/admin/meetings');
        $response->assertRedirect(route('dashboard', absolute: false));
    }
    
    public function test_ustad_can_access_admin_panel(): void
    {
        $ustad = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'dashboard.view',
            'is_allowed' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'meetings.manage',
            'is_allowed' => true,
        ]);

        $this->actingAs($ustad);

        $response = $this->get('/admin/meetings');
        
        $response->assertStatus(200);
    }

    public function test_superadmin_can_access_admin_panel_without_role_permission_records(): void
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin);

        $this->get('/admin/payments')->assertStatus(200);
        $this->get('/admin/users')->assertStatus(200);
    }

    public function test_ustad_without_resource_permission_is_redirected_from_direct_admin_url(): void
    {
        $ustad = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'dashboard.view',
            'is_allowed' => true,
        ]);

        $this->actingAs($ustad);

        $response = $this->get('/admin/payments');

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
