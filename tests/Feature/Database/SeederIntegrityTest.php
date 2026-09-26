<?php

namespace Tests\Feature\Database;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SubjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs_without_errors(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_role_permission_seeder_seeds_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->assertDatabaseHas('role_permissions', ['role' => 'guru', 'permission' => 'meetings.manage']);
    }

    public function test_subject_seeder_seeds_subjects(): void
    {
        $this->seed(SubjectSeeder::class);
        $this->assertDatabaseCount('subjects', 6);
    }

    public function test_mockup_data_seeder_populates_domain_records_cleanly(): void
    {
        $this->seed(\Database\Seeders\MockupDataSeeder::class);
        $this->assertDatabaseHas('users', ['email' => 'superadmin@asy-syams.test']);
        $this->assertDatabaseHas('users', ['email' => 'guru.tahsin@asy-syams.test']);
    }
}