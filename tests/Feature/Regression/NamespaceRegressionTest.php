<?php

namespace Tests\Feature\Regression;

use Tests\TestCase;

class NamespaceRegressionTest extends TestCase
{
    /**
     * Test resolving all feature models.
     */
    public function test_all_feature_models_can_be_resolved()
    {
        $models = [
            \App\Features\Payments\Models\Payment::class,
            \App\Features\Posts\Models\Post::class,
            \App\Features\Grades\Models\Assessment::class,
            \App\Features\Grades\Models\Evaluation::class,
            \App\Features\Grades\Models\Grade::class,
            \App\Features\Academic\Models\ClassGroup::class,
            \App\Features\Academic\Models\Subject::class,
            \App\Features\Academic\Models\Semester::class,
            \App\Features\Meetings\Models\Meeting::class,
            \App\Features\Meetings\Models\Attendance::class,
            \App\Features\Permissions\Models\RolePermission::class,
            \App\Features\SiteSettings\Models\SiteSetting::class,
        ];

        foreach ($models as $model) {
            $this->assertTrue(class_exists($model), "Model $model not found.");
        }
    }

    /**
     * Test resolving all feature services.
     */
    public function test_all_feature_services_can_be_resolved()
    {
        $services = [
            \App\Features\Grades\Services\GradeCalculationService::class,
            \App\Features\Payments\Services\MidtransService::class,
            \App\Features\Posts\Services\PostService::class,
            \App\Features\Contacts\Services\ContactService::class,
            \App\Features\Academic\Services\AcademicService::class,
            \App\Features\Meetings\Services\MeetingService::class,
            \App\Features\Permissions\Services\PermissionService::class,
            \App\Features\Reports\Services\ReportService::class,
            \App\Features\SiteSettings\Services\SiteSettingService::class,
            \App\Features\Students\Services\StudentService::class,
        ];

        foreach ($services as $service) {
            $this->assertTrue(class_exists($service), "Service $service not found.");
            // Test if it can be resolved by the container
            try {
                $instance = app($service);
                $this->assertInstanceOf($service, $instance);
            } catch (\Throwable $e) {
                $this->fail("Service $service could not be resolved: " . $e->getMessage());
            }
        }
    }

    /**
     * Test resolving all feature controllers.
     */
    public function test_all_feature_controllers_can_be_resolved()
    {
        $controllers = [
            \App\Features\Payments\Controllers\PaymentController::class,
            \App\Features\Posts\Controllers\PostController::class,
            \App\Features\Students\Controllers\StudentController::class,
            \App\Features\Contacts\Controllers\ContactController::class,
        ];

        foreach ($controllers as $controller) {
            $this->assertTrue(class_exists($controller), "Controller $controller not found.");
        }
    }

    public function test_legacy_model_and_service_imports_are_not_used_outside_user_model(): void
    {
        $forbiddenNamespaces = [
            'App\\Models\\Payment',
            'App\\Models\\Post',
            'App\\Models\\Grade',
            'App\\Models\\Assessment',
            'App\\Models\\Evaluation',
            'App\\Models\\ClassGroup',
            'App\\Models\\Subject',
            'App\\Models\\Semester',
            'App\\Models\\Meeting',
            'App\\Models\\Attendance',
            'App\\Models\\RolePermission',
            'App\\Models\\SiteSetting',
            'App\\Services\\MidtransService',
            'App\\Services\\GradeCalculationService',
        ];

        $paths = [
            app_path(),
            base_path('routes'),
            base_path('database'),
            resource_path('views'),
        ];

        foreach ($paths as $path) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                foreach ($forbiddenNamespaces as $namespace) {
                    $this->assertStringNotContainsString(
                        $namespace,
                        $contents,
                        "Legacy namespace {$namespace} found in {$file->getPathname()}."
                    );
                }
            }
        }
    }
}
