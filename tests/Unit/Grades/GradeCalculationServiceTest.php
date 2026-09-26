<?php

namespace Tests\Unit\Grades;

use App\Features\Grades\Services\GradeCalculationService;
use PHPUnit\Framework\TestCase;

class GradeCalculationServiceTest extends TestCase
{
    private GradeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GradeCalculationService();
    }

    public function test_calculate_assessment_average_with_letter_grades(): void
    {
        $assessments = [
            ['data' => [['nilai' => 'L'], ['nilai' => 'C'], ['nilai' => 'TL']]],
        ];

        $this->assertEquals(75.0, $this->service->calculateAssessmentAverage($assessments));
    }

    public function test_calculate_assessment_average_handles_trimming_and_case(): void
    {
        $assessments = [
            ['data' => [['nilai' => ' l '], ['nilai' => ' c ']]],
        ];

        $this->assertEquals(87.5, $this->service->calculateAssessmentAverage($assessments));
    }

    public function test_calculate_assessment_average_handles_numeric_values(): void
    {
        $assessments = [
            ['data' => [['nilai' => 80], ['nilai' => 90]]],
        ];

        $this->assertEquals(85.0, $this->service->calculateAssessmentAverage($assessments));
    }

    public function test_calculate_assessment_average_empty_returns_zero(): void
    {
        $this->assertEquals(0.0, $this->service->calculateAssessmentAverage([]));
    }

    public function test_calculate_evaluation_average_with_items(): void
    {
        $evaluations = [
            ['items' => [['score' => 80], ['score' => 90]]],
        ];

        $this->assertEquals(85.0, $this->service->calculateEvaluationAverage($evaluations));
    }

    public function test_calculate_final_grade_weighted_average(): void
    {
        $assessments = [
            ['data' => [['nilai' => 'L']]],
        ];
        $evaluations = [
            ['items' => [['score' => 90]]],
        ];

        $this->assertEquals(94.0, $this->service->calculateFinalGrade($assessments, $evaluations));
    }
}