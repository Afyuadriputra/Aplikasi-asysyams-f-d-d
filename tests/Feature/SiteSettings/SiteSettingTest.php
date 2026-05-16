<?php

namespace Tests\Feature\SiteSettings;

use App\Filament\Resources\SiteSettingResource\Pages\ManageSPMBDeadline;
use App\Features\SiteSettings\Models\SiteSetting;
use App\Models\User;
use App\Features\Permissions\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_site_setting()
    {
        SiteSetting::create([
            'key' => 'site_name',
            'value' => 'Asy-Syams'
        ]);

        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => 'Asy-Syams']);
    }

    public function test_landing_page_can_read_settings()
    {
        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => '2026-12-31 23:59:59'
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('2026-12-31T23:59:59'); // In JS variable
    }

    public function test_landing_page_does_not_crash_without_settings()
    {
        // No settings created
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_spmb_deadline_setting_can_be_read()
    {
        $setting = SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => '2026-08-17 10:30:00',
        ]);

        $this->assertSame('2026-08-17 10:30:00', $setting->fresh()->value);
    }

    public function test_manage_spmb_deadline_uses_feature_model_and_permission()
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $this->actingAs($guru);
        $this->assertFalse(ManageSPMBDeadline::canAccess());

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'settings.update',
            'is_allowed' => true,
        ]);

        $this->assertTrue(ManageSPMBDeadline::canAccess());
        $this->assertStringContainsString(
            'App\\Features\\SiteSettings\\Models\\SiteSetting',
            file_get_contents(app_path('Filament/Resources/SiteSettingResource/Pages/ManageSPMBDeadline.php'))
        );
    }
}
