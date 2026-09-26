<?php

namespace Tests\Feature\Validation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_all_mandatory_fields(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'nisn',
            'grade_level',
            'birth_date',
            'mother_name',
            'school_origin',
            'address',
            'phone',
            'gender',
        ]);
    }

    public function test_registration_validates_email_format_and_uniqueness(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nisn' => '1234567890',
            'grade_level' => '1',
            'birth_date' => '2015-01-01',
            'mother_name' => 'Jane',
            'school_origin' => 'SD 1',
            'address' => 'Jl. Merdeka',
            'phone' => '08123456789',
            'gender' => 'L',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_validates_gender_must_be_l_or_p(): void
    {
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nisn' => '1234567890',
            'grade_level' => '1',
            'birth_date' => '2015-01-01',
            'mother_name' => 'Jane',
            'school_origin' => 'SD 1',
            'address' => 'Jl. Merdeka',
            'phone' => '08123456789',
            'gender' => 'X',
        ]);

        $response->assertSessionHasErrors('gender');
    }
}