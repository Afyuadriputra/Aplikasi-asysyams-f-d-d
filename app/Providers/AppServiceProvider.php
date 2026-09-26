<?php

namespace App\Providers;

use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Observers\AssessmentObserver;
use App\Features\Grades\Observers\EvaluationObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Assessment::observe(AssessmentObserver::class);
        Evaluation::observe(EvaluationObserver::class);
    }
}
