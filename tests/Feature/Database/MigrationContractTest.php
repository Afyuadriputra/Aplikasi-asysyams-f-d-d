<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_core_tables_exist(): void
    {
        $tables = [
            'users',
            'semesters',
            'subjects',
            'class_groups',
            'class_group_student',
            'meetings',
            'attendances',
            'assessments',
            'evaluations',
            'grades',
            'payments',
            'site_settings',
            'role_permissions',
            'teacher_attendances',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} must exist in database");
        }
    }

    public function test_assessments_table_has_all_expected_columns(): void
    {
        $columns = [
            'id',
            'class_group_id',
            'user_id',
            'assessment_type',
            'month',
            'year',
            'data',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('assessments', $column), "Column {$column} must exist on assessments table");
        }
    }

    public function test_meetings_table_has_expected_foreign_keys_and_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('meetings', 'class_group_id'));
        $this->assertTrue(Schema::hasColumn('meetings', 'user_id'));
        $this->assertTrue(Schema::hasColumn('meetings', 'title'));
        $this->assertTrue(Schema::hasColumn('meetings', 'date'));
    }

    public function test_unique_indexes_exist_on_critical_tables(): void
    {
        $userIndexes = Schema::getIndexes('users');
        $emailUnique = collect($userIndexes)->first(fn ($idx) => in_array('email', $idx['columns'] ?? []) && ($idx['unique'] ?? false));
        $this->assertNotNull($emailUnique, 'users table must have unique index on email');

        $assessmentIndexes = Schema::getIndexes('assessments');
        $this->assertNotEmpty($assessmentIndexes, 'assessments table must have indexes');
    }
}