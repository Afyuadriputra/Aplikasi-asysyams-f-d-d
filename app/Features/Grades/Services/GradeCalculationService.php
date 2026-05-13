<?php

namespace App\Features\Grades\Services;

class GradeCalculationService
{
    /**
     * Hitung nilai akhir dari Assessment dan Evaluation
     * Pembobotan: 40% rata-rata Assessment + 60% rata-rata Evaluation
     *
     * @param iterable $assessments
     * @param iterable $evaluations
     * @return float
     */
    public function calculateFinalGrade(iterable $assessments, iterable $evaluations): float
    {
        $assessmentAvg = $this->calculateAssessmentAverage($assessments);
        $evaluationAvg = $this->calculateEvaluationAverage($evaluations);

        return ($assessmentAvg * 0.4) + ($evaluationAvg * 0.6);
    }

    /**
     * Hitung rata-rata Assessment
     */
    public function calculateAssessmentAverage(iterable $assessments): float
    {
        $totalScore = 0;
        $count = 0;

        foreach ($assessments as $assessment) {
            $data = is_string($assessment['data'] ?? null) 
                ? json_decode($assessment['data'], true) 
                : ($assessment['data'] ?? []);
                
            if (!is_array($data)) continue;

            foreach ($data as $item) {
                if (isset($item['nilai'])) {
                    $nilai = is_string($item['nilai']) ? strtoupper(trim($item['nilai'])) : $item['nilai'];
                    if ($nilai === 'L') {
                        $totalScore += 100;
                    } elseif ($nilai === 'C') {
                        $totalScore += 75;
                    } elseif ($nilai === 'TL') {
                        $totalScore += 50;
                    } elseif (is_numeric($nilai)) {
                        $totalScore += (float) $nilai;
                    }
                    $count++;
                }
            }
        }

        // Hindari Division by Zero
        if ($count === 0) {
            return 0.0;
        }

        return $totalScore / $count;
    }

    /**
     * Hitung rata-rata Evaluation
     */
    public function calculateEvaluationAverage(iterable $evaluations): float
    {
        $totalScore = 0;
        $count = 0;

        foreach ($evaluations as $evaluation) {
            $items = is_string($evaluation['items'] ?? null) 
                ? json_decode($evaluation['items'], true) 
                : ($evaluation['items'] ?? []);
                
            if (!is_array($items)) continue;

            foreach ($items as $item) {
                if (isset($item['score']) && is_numeric($item['score'])) {
                    $totalScore += (float) $item['score'];
                    $count++;
                }
            }
        }

        // Hindari Division by Zero
        if ($count === 0) {
            return 0.0;
        }

        return $totalScore / $count;
    }
}
