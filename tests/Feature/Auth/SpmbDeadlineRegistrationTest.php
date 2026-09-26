<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Services\SpmbRegistrationService;
use App\Features\SiteSettings\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SpmbDeadlineRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_registration_is_blocked_when_spmb_deadline_has_passed(): void
    {
        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => now()->subDay()->toDateTimeString(),
        ]);

        $response = $this->post('/register', [
            'name' => 'Calon Santri',
            'email' => 'calon@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nisn' => '1234567890',
            'grade_level' => '1',
            'birth_date' => '2015-01-01',
            'mother_name' => 'Fatimah',
            'school_origin' => 'SD IT',
            'address' => 'Pekanbaru',
            'phone' => '081234567890',
            'gender' => 'L',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', [
            'email' => 'calon@example.com',
        ]);
    }

    public function test_registration_is_allowed_when_spmb_deadline_is_in_the_future(): void
    {
        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => now()->addDays(7)->toDateTimeString(),
        ]);

        $response = $this->post('/register', [
            'name' => 'Calon Santri Baru',
            'email' => 'calonbaru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nisn' => '9988776655',
            'grade_level' => '1',
            'birth_date' => '2015-05-05',
            'mother_name' => 'Aisyah',
            'school_origin' => 'SD IT Al-Hikmah',
            'address' => 'Pekanbaru',
            'phone' => '081234567891',
            'gender' => 'P',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'calonbaru@example.com',
        ]);
    }

    public function test_register_page_is_accessible_when_no_deadline_or_in_future(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => now()->addDays(5)->toDateTimeString(),
        ]);

        $responseFuture = $this->get('/register');
        $responseFuture->assertStatus(200);
    }

    public function test_register_page_returns_403_when_deadline_passed(): void
    {
        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => now()->subHour()->toDateTimeString(),
        ]);

        $response = $this->get('/register');
        $response->assertStatus(403);
        $response->assertSee('Pendaftaran SPMB Ditutup');
    }

    public function test_date_only_deadline_remains_open_through_end_of_day(): void
    {
        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => '2026-09-30',
        ]);

        $service = app(SpmbRegistrationService::class);

        // 2026-09-29 12:00 -> OPEN
        Carbon::setTestNow('2026-09-29 12:00:00');
        $this->assertTrue($service->isOpen());

        // 2026-09-30 00:00:00 -> OPEN
        Carbon::setTestNow('2026-09-30 00:00:00');
        $this->assertTrue($service->isOpen());

        // 2026-09-30 23:59:00 -> OPEN
        Carbon::setTestNow('2026-09-30 23:59:00');
        $this->assertTrue($service->isOpen());

        // 2026-10-01 00:00:01 -> CLOSED
        Carbon::setTestNow('2026-10-01 00:00:01');
        $this->assertFalse($service->isOpen());
    }

    public function test_datetime_deadline_enforces_exact_boundary(): void
    {
        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => '2026-09-30 15:00:00',
        ]);

        $service = app(SpmbRegistrationService::class);

        Carbon::setTestNow('2026-09-30 14:59:59');
        $this->assertTrue($service->isOpen());

        Carbon::setTestNow('2026-09-30 15:00:01');
        $this->assertFalse($service->isOpen());
    }

    public function test_registration_open_when_no_deadline_configured(): void
    {
        $service = app(SpmbRegistrationService::class);
        $this->assertNull($service->getDeadline());
        $this->assertTrue($service->isOpen());
    }

    public function test_registration_open_when_invalid_deadline_configured(): void
    {
        SiteSetting::create([
            'key' => 'spmb_deadline',
            'value' => 'not-a-valid-date-string',
        ]);

        $service = app(SpmbRegistrationService::class);
        $this->assertNull($service->getDeadline());
        $this->assertTrue($service->isOpen());
    }
}