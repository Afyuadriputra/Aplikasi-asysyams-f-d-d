<?php

namespace Tests\Unit\Grades;

use App\Features\Grades\Services\GradeCalculationService;
use PHPUnit\Framework\TestCase;

class GradeServiceTest extends TestCase
{
    protected GradeCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GradeCalculationService();
    }

    public function test_calculate_assessment_average_perfect_score()
    {
        $assessments = [
            ['data' => [['nilai' => 'L'], ['nilai' => 'L']]], // 100, 100
            ['data' => json_encode([['nilai' => 100]])] // 100
        ];

        $avg = $this->service->calculateAssessmentAverage($assessments);
        $this->assertEquals(100.0, $avg);
    }

    public function test_calculate_assessment_average_numeric_values()
    {
        $assessments = [
            ['data' => [['nilai' => 85.5], ['nilai' => '90']]], // 85.5, 90
        ];

        $avg = $this->service->calculateAssessmentAverage($assessments);
        // (85.5 + 90) / 2 = 87.75
        $this->assertEquals(87.75, $avg);
    }

    public function test_calculate_assessment_average_mixed_scores_and_lowercase()
    {
        $assessments = [
            ['data' => [['nilai' => 'l'], ['nilai' => 'c']]], // lowercase l=100, c=75
            ['data' => [['nilai' => 'tl'], ['nilai' => 'TL']]] // lowercase tl=50, uppercase TL=50
        ];

        $avg = $this->service->calculateAssessmentAverage($assessments);
        // (100 + 75 + 50 + 50) / 4 = 275 / 4 = 68.75
        $this->assertEquals(68.75, $avg);
    }

    public function test_calculate_assessment_average_mixed_scores()
    {
        $assessments = [
            ['data' => [['nilai' => 'L'], ['nilai' => 'C']]], // 100, 75
            ['data' => [['nilai' => 'TL']]] // 50
        ];

        $avg = $this->service->calculateAssessmentAverage($assessments);
        // (100 + 75 + 50) / 3 = 225 / 3 = 75
        $this->assertEquals(75.0, $avg);
    }

    public function test_calculate_assessment_average_trims_letter_scores()
    {
        $assessments = [
            ['data' => [['nilai' => ' c '], ['nilai' => ' tl '], ['nilai' => ' l ']]],
        ];

        $avg = $this->service->calculateAssessmentAverage($assessments);

        $this->assertEquals(75.0, $avg);
    }

    public function test_calculate_assessment_average_division_by_zero()
    {
        $assessments = [
            ['data' => []], 
            ['data' => null]
        ];

        $avg = $this->service->calculateAssessmentAverage($assessments);
        $this->assertEquals(0.0, $avg);
    }

    public function test_calculate_evaluation_average_decimal_scores()
    {
        $evaluations = [
            ['items' => [['score' => 85.5], ['score' => 90.0]]],
            ['items' => json_encode([['score' => '80.25']])]
        ];

        $avg = $this->service->calculateEvaluationAverage($evaluations);
        // (85.5 + 90.0 + 80.25) / 3 = 255.75 / 3 = 85.25
        $this->assertEquals(85.25, $avg);
    }

    public function test_calculate_final_grade()
    {
        // Assessment avg: 75
        $assessments = [
            ['data' => [['nilai' => 'L'], ['nilai' => 'C'], ['nilai' => 'TL']]] 
        ];

        // Evaluation avg: 80
        $evaluations = [
            ['items' => [['score' => 70], ['score' => 90]]]
        ];

        $finalGrade = $this->service->calculateFinalGrade($assessments, $evaluations);
        // 40% of 75 = 30
        // 60% of 80 = 48
        // 30 + 48 = 78
        $this->assertEquals(78.0, $finalGrade);
    }

    public function test_calculate_final_grade_with_empty_assessment()
    {
        $assessments = []; // 0

        // Evaluation avg: 100
        $evaluations = [
            ['items' => [['score' => 100]]]
        ];

        $finalGrade = $this->service->calculateFinalGrade($assessments, $evaluations);
        // 40% of 0 = 0
        // 60% of 100 = 60
        $this->assertEquals(60.0, $finalGrade);
    }

    public function test_calculate_final_grade_with_empty_evaluation()
    {
        // Assessment avg: 100
        $assessments = [
            ['data' => [['nilai' => 'L']]]
        ];

        $evaluations = []; // 0

        $finalGrade = $this->service->calculateFinalGrade($assessments, $evaluations);
        // 40% of 100 = 40
        // 60% of 0 = 0
        $this->assertEquals(40.0, $finalGrade);
    }

    public function test_calculate_assessment_average_with_invalid_format()
    {
        $assessments = [
            ['data' => 'not-a-json'], // Invalid JSON string
            ['data' => 123], // Not array or string
        ];

        $avg = $this->service->calculateAssessmentAverage($assessments);
        $this->assertEquals(0.0, $avg);
    }
}
