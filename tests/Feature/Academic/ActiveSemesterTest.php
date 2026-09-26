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
            'end_date' => now()->startOfYear()->addMonths(5),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $semester2 = Semester::create([
            'name' => 'Semester 2 2026/2027',
            'start_date' => now()->startOfYear()->addMonths(6),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $activeCount = Semester::where('is_active', true)->count();
        $this->assertSame(1, $activeCount, 'Only exactly one semester should remain active');
        $this->assertFalse($semester1->fresh()->is_active);
        $this->assertTrue($semester2->fresh()->is_active);
    }

    public function test_activating_new_semester_deactivates_previously_active_semester(): void
    {
        $semester1 = Semester::create([
            'name' => 'Semester 1 2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->startOfYear()->addMonths(5),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $this->assertTrue($semester1->fresh()->is_active);

        $semester2 = Semester::create([
            'name' => 'Semester 2 2026/2027',
            'start_date' => now()->startOfYear()->addMonths(6),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $this->assertFalse(
            $semester1->fresh()->is_active,
            'Previous semester should automatically be deactivated when new semester is activated'
        );
        $this->assertTrue($semester2->fresh()->is_active);
    }

    public function test_creating_first_active_semester_works(): void
    {
        $this->assertSame(0, Semester::where('is_active', true)->count());

        $semester = Semester::create([
            'name' => 'Semester Perdana',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 450000,
        ]);

        $this->assertTrue($semester->fresh()->is_active);
        $this->assertSame(1, Semester::where('is_active', true)->count());
    }

    public function test_activating_second_semester_deactivates_first(): void
    {
        $sem1 = Semester::create([
            'name' => 'Sem A',
            'start_date' => now(),
            'end_date' => now()->addMonths(5),
            'is_active' => true,
            'tuition_fee' => 300000,
        ]);

        $sem2 = Semester::create([
            'name' => 'Sem B',
            'start_date' => now()->addMonths(6),
            'end_date' => now()->addMonths(11),
            'is_active' => false,
            'tuition_fee' => 300000,
        ]);

        $this->assertTrue($sem1->fresh()->is_active);
        $this->assertFalse($sem2->fresh()->is_active);

        $sem2->update(['is_active' => true]);

        $this->assertFalse($sem1->fresh()->is_active);
        $this->assertTrue($sem2->fresh()->is_active);
        $this->assertSame(1, Semester::where('is_active', true)->count());
    }

    public function test_reactivating_old_semester_deactivates_current(): void
    {
        $semOld = Semester::create([
            'name' => 'Old Semester',
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonths(6),
            'is_active' => false,
            'tuition_fee' => 350000,
        ]);

        $semCurrent = Semester::create([
            'name' => 'Current Semester',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 400000,
        ]);

        $this->assertTrue($semCurrent->fresh()->is_active);

        // Reactivate old semester
        $semOld->update(['is_active' => true]);

        $this->assertTrue($semOld->fresh()->is_active);
        $this->assertFalse($semCurrent->fresh()->is_active);
        $this->assertSame(1, Semester::where('is_active', true)->count());
    }

    public function test_inactive_semesters_can_coexist(): void
    {
        $sem1 = Semester::create([
            'name' => 'Inactive 1',
            'start_date' => now()->subYears(2),
            'end_date' => now()->subMonths(18),
            'is_active' => false,
            'tuition_fee' => 300000,
        ]);

        $sem2 = Semester::create([
            'name' => 'Inactive 2',
            'start_date' => now()->subMonths(17),
            'end_date' => now()->subMonths(12),
            'is_active' => false,
            'tuition_fee' => 300000,
        ]);

        $this->assertSame(0, Semester::where('is_active', true)->count());
        $this->assertFalse($sem1->fresh()->is_active);
        $this->assertFalse($sem2->fresh()->is_active);
    }

    public function test_updating_non_activation_fields_does_not_change_active_semester(): void
    {
        $semActive = Semester::create([
            'name' => 'Active Sem',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $semInactive = Semester::create([
            'name' => 'Inactive Sem',
            'start_date' => now()->subMonths(12),
            'end_date' => now()->subMonths(6),
            'is_active' => false,
            'tuition_fee' => 500000,
        ]);

        // Update name of active semester
        $semActive->update(['name' => 'Active Sem Renamed']);

        $this->assertTrue($semActive->fresh()->is_active);
        $this->assertFalse($semInactive->fresh()->is_active);
        $this->assertSame(1, Semester::where('is_active', true)->count());

        // Update fee of inactive semester
        $semInactive->update(['tuition_fee' => 600000]);

        $this->assertTrue($semActive->fresh()->is_active);
        $this->assertFalse($semInactive->fresh()->is_active);
        $this->assertSame(1, Semester::where('is_active', true)->count());
    }
}