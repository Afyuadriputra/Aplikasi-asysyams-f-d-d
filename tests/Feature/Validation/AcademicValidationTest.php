<?php

namespace Tests\Feature\Validation;

use App\Features\Academic\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_semester_end_date_cannot_be_before_start_date(): void
    {
        $startDate = now();
        $invalidEndDate = now()->subMonth();

        $semester = Semester::create([
            'name' => 'Semester Invalid Dates',
            'start_date' => $startDate,
            'end_date' => $invalidEndDate,
            'is_active' => false,
            'tuition_fee' => 500000,
        ]);

        $this->assertTrue(
            $semester->end_date->gte($semester->start_date),
            'Semester end_date must be greater than or equal to start_date'
        );
    }
}