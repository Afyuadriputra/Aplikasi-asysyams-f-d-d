<?php

namespace Tests\Feature\Auth;

use App\Features\SiteSettings\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpmbDeadlineRegistrationTest extends TestCase
{
    use RefreshDatabase;

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

        // BUG REPRODUCED: Application does not check spmb_deadline in RegisteredUserController
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

    public function test_register_page_is_accessible(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }
}