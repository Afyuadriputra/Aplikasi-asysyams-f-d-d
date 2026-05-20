<?php

namespace Database\Seeders;

use App\Features\Academic\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Baca & Tulis', 'slug' => 'baca-tulis'],
            ['name' => 'Tahsin', 'slug' => 'tahsin'],
            ['name' => 'Murottal', 'slug' => 'murottal'],
            ['name' => 'Tilawah', 'slug' => 'tilawah'],
            ['name' => 'Tahfidz', 'slug' => 'tahfidz'],
            ['name' => 'Tajwid', 'slug' => 'tajwid'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['slug' => $subject['slug']],
                $subject
            );
        }
    }
}
