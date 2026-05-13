<?php

namespace Tests\Feature\SiteSettings;

use App\Features\SiteSettings\Models\SiteSetting;
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
}
