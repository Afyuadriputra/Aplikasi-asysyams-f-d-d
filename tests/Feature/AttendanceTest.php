<?php

namespace Tests\Feature;

use App\Filament\Resources\MeetingResource\Pages\CreateMeeting;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Meetings\Models\Meeting;
use App\Features\Permissions\Models\RolePermission;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ustad_can_create_meeting_and_save_attendance()
    {
        // 1. Buat Ustad
        $ustad = User::factory()->create([
            'role' => 'guru', 
            'is_active' => true
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'dashboard.view',
            'is_allowed' => true,
        ]);

        RolePermission::create([
            'role' => 'guru',
            'permission' => 'meetings.manage',
            'is_allowed' => true,
        ]);

        // 2. Buat Class Group dan Santri
        $subject = Subject::create([
            'name' => 'Tajwid',
            'slug' => 'tajwid'
        ]);
        
        $semester = Semester::create([
            'name' => 'Ganjil',
            'year' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);
        
        $classGroup = ClassGroup::create([
            'name' => 'Kelas A', 
            'slug' => 'kelas-a',
            'teacher_id' => $ustad->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'is_active' => true
        ]);
        
        $santri = User::factory()->create([
            'role' => 'student', 
            'name' => 'Santri A',
            'is_active' => true
        ]);
        
        // Hubungkan santri ke kelas
        $classGroup->students()->attach($santri->id);

        $this->actingAs($ustad);

        // 3. Test Livewire Component CreateMeeting
        Livewire::test(CreateMeeting::class)
            ->fillForm([
                'class_group_id' => $classGroup->id,
                'user_id' => $ustad->id,
                'title' => 'Materi Tajwid',
                'date' => now()->format('Y-m-d'),
                'students_attendance' => [
                    [
                        'user_id' => $santri->id,
                        'name' => $santri->name,
                        'status' => 'present'
                    ]
                ]
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // 4. Assert Database Has Meeting
        $this->assertDatabaseHas('meetings', [
            'class_group_id' => $classGroup->id,
            'title' => 'Materi Tajwid'
        ]);

        // 5. Assert Database Has Attendance
        $meeting = Meeting::where('title', 'Materi Tajwid')->first();
        
        $this->assertDatabaseHas('attendances', [
            'meeting_id' => $meeting->id,
            'user_id' => $santri->id,
            'status' => 'present'
        ]);
    }
}
