<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use App\Features\Permissions\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_selalu_bypass_permission()
    {
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->assertTrue($superadmin->hasAccess('any.permission'));
        $this->assertTrue($superadmin->hasAnyAccess(['any.permission', 'other.permission']));
    }

    public function test_guru_hanya_bisa_akses_permission_yang_is_allowed_true()
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'meetings.manage',
            'is_allowed' => true,
        ]);

        $this->assertTrue($guru->hasAccess('meetings.manage'));
    }

    public function test_guru_tidak_bisa_akses_permission_yang_is_allowed_false()
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'payments.view',
            'is_allowed' => false,
        ]);

        $this->assertFalse($guru->hasAccess('payments.view'));
    }

    public function test_guru_tidak_bisa_akses_permission_yang_tidak_ada()
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        $this->assertFalse($guru->hasAccess('non.existent.permission'));
    }

    public function test_has_any_access_berjalan_benar()
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'grades.view',
            'is_allowed' => true,
        ]);

        $this->assertTrue($guru->hasAnyAccess(['grades.view', 'payments.view']));
        $this->assertFalse($guru->hasAnyAccess(['posts.view', 'payments.view']));
    }

    public function test_has_any_access_false_jika_semua_permission_tidak_allowed()
    {
        $guru = User::factory()->create([
            'role' => 'guru',
            'is_active' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'posts.view',
            'is_allowed' => false,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'payments.view',
            'is_allowed' => false,
        ]);

        $this->assertFalse($guru->hasAnyAccess(['posts.view', 'payments.view']));
    }

    public function test_role_permission_feature_model_namespace_berjalan_normal()
    {
        $permission = RolePermission::create([
            'role' => 'guru',
            'permission' => 'classes.view',
            'is_allowed' => true,
        ]);

        $this->assertInstanceOf(RolePermission::class, $permission->fresh());
        $this->assertDatabaseHas('role_permissions', [
            'role' => 'guru',
            'permission' => 'classes.view',
            'is_allowed' => true,
        ]);
    }

    public function test_student_tidak_bisa_akses_admin_panel_karena_middleware()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->actingAs($student);
        
        $response = $this->get('/admin');
        // Route aslinya di redirect ke home jika gak diizinkan ke /admin, namun
        // di middleware 'RedirectUnauthorizedFilamentAccess' atau 'EnsureUserHasPermission'
        // kita perlu cek redirection-nya.
        // Berdasarkan RbacTest sebelumnya, dia diredirect ke route('dashboard').
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
