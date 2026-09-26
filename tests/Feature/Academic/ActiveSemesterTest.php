<?php

namespace Tests\Feature\Academic;

use App\Features\Academic\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveSemesterTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_semester_can_be_active_at_a_time(): void
    {
        $semester1 = Semester::create([
            'name' => 'Semester 1 2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $semester2 = Semester::create([
            'name' => 'Semester 2 2026/2027',
            'start_date' => now()->addMonths(6),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        // BUG REPRODUCED: System allows multiple active semesters without automatic deactivation
        $activeCount = Semester::where('is_active', true)->count();
        $this->assertSame(1, $activeCount, 'Only exactly one semester should remain active');
    }
}