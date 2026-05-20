<?php

namespace App\Features\Grades\Services;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Grade;
use App\Features\SiteSettings\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Collection;

class GradeReportService
{
    public function buildStudentControlReport(User $student, ?Grade $grade = null): array
    {
        $student->loadMissing('classGroups.subject', 'classGroups.semester');

        $classGroup = $this->resolveClassGroup($student, $grade);
        $assessments = $this->assessmentQuery($student, $classGroup)->get();
        $rows = $this->buildRows($assessments);

        return [
            'student' => $student,
            'classGroup' => $classGroup,
            'institution' => [
                'name' => 'RUMAH QUR\'AN ASY-SYAMS',
                'subtitle' => 'TILAWAH CENTER PEKANBARU',
                'address' => $this->setting('contact_address'),
                'phone' => $this->setting('contact_phone'),
                'email' => $this->setting('contact_email'),
            ],
            'rows' => $rows,
            'generatedAt' => now(),
        ];
    }

    public function canAccessStudentReport(User $actor, User $student, ?Grade $grade = null): bool
    {
        if ($actor->role === 'superadmin') {
            return true;
        }

        if ($actor->role !== 'guru' || ! $actor->hasAnyAccess(['grades.view', 'grades.manage', 'reports.download'])) {
            return false;
        }

        if ($grade && (int) $grade->user_id !== (int) $student->id) {
            return false;
        }

        return $student->classGroups()
            ->where('teacher_id', $actor->id)
            ->exists();
    }

    private function resolveClassGroup(User $student, ?Grade $grade = null): ?ClassGroup
    {
        if ($grade) {
            $matchedClass = $student->classGroups
                ->first(fn (ClassGroup $classGroup): bool =>
                    (int) $classGroup->subject_id === (int) $grade->subject_id
                    && (int) $classGroup->semester_id === (int) $grade->semester_id
                );

            if ($matchedClass) {
                return $matchedClass;
            }
        }

        return $student->classGroups
            ->sortByDesc(fn (ClassGroup $classGroup) => $classGroup->pivot?->joined_at ?? $classGroup->created_at)
            ->first();
    }

    private function assessmentQuery(User $student, ?ClassGroup $classGroup = null)
    {
        return Assessment::query()
            ->where('user_id', $student->id)
            ->when($classGroup, fn ($query) => $query->where('class_group_id', $classGroup->id))
            ->orderBy('created_at')
            ->orderBy('id');
    }

    private function buildRows(Collection $assessments): Collection
    {
        $rows = [];

        foreach ($assessments as $assessment) {
            foreach ($this->assessmentItems($assessment) as $item) {
                $dateKey = $assessment->created_at?->format('Y-m-d') ?? now()->format('Y-m-d');

                if (! isset($rows[$dateKey])) {
                    $rows[$dateKey] = [
                        'date' => $assessment->created_at,
                        'ziyadah' => '',
                        'ziyadah_score' => '',
                        'murojaah' => '',
                        'murojaah_score' => '',
                        'tahsin' => '',
                        'signature' => '',
                    ];
                }

                match ($assessment->assessment_type) {
                    'ziyadah' => $this->fillZiyadah($rows[$dateKey], $item),
                    'murojaah' => $this->fillMurojaah($rows[$dateKey], $item),
                    'tahsin', 'tilawah' => $this->fillTahsin($rows[$dateKey], $item),
                    default => null,
                };
            }
        }

        return collect(array_values($rows))
            ->values()
            ->map(function (array $row, int $index): array {
                $row['number'] = $index + 1;

                return $row;
            });
    }

    private function assessmentItems(Assessment $assessment): array
    {
        $data = $assessment->data ?? [];

        if (! is_array($data)) {
            return [];
        }

        return array_values($data);
    }

    private function fillZiyadah(array &$row, array $item): void
    {
        $row['ziyadah'] = $this->mergeCell($row['ziyadah'], $this->surahAyat($item));
        $row['ziyadah_score'] = $this->normalizeScore($item['nilai'] ?? null);
    }

    private function fillMurojaah(array &$row, array $item): void
    {
        $row['murojaah'] = $this->mergeCell($row['murojaah'], $this->surahAyat($item));
        $row['murojaah_score'] = $this->scoreToBaikKurang($item['nilai'] ?? $item['nilai_penyetoran'] ?? null);
    }

    private function fillTahsin(array &$row, array $item): void
    {
        $row['tahsin'] = $this->mergeCell($row['tahsin'], $this->surahAyat($item));
    }

    private function surahAyat(array $item): string
    {
        $surah = trim((string) ($item['surah'] ?? $item['name'] ?? ''));
        $ayat = trim((string) ($item['ayat'] ?? ''));

        if ($surah === '' && $ayat === '') {
            return trim((string) ($item['catatan'] ?? $item['nilai_penyetoran'] ?? '-'));
        }

        return trim($surah . ($ayat !== '' ? ' : ' . $ayat : ''));
    }

    private function normalizeScore(mixed $value): string
    {
        $score = strtoupper(trim((string) $value));

        return in_array($score, ['L', 'C', 'TL'], true) ? $score : '';
    }

    private function scoreToBaikKurang(mixed $value): string
    {
        $score = strtoupper(trim((string) $value));

        if ($score === 'L' || $score === 'B') {
            return 'B';
        }

        if (in_array($score, ['C', 'TL', 'K'], true)) {
            return 'K';
        }

        if (is_numeric($score)) {
            return (float) $score >= 75 ? 'B' : 'K';
        }

        return '';
    }

    private function mergeCell(string $current, string $value): string
    {
        if ($value === '') {
            return $current;
        }

        if ($current === '') {
            return $value;
        }

        return $current . '; ' . $value;
    }

    private function setting(string $key): ?string
    {
        return SiteSetting::query()->where('key', $key)->value('value');
    }
}
