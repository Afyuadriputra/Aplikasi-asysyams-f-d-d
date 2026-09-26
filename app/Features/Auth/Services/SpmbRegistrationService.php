<?php

namespace App\Features\Auth\Services;

use App\Features\SiteSettings\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SpmbRegistrationService
{
    /**
     * Get the parsed Carbon deadline for SPMB registration.
     *
     * Date-only strings (e.g., '2026-09-30') are interpreted as remaining
     * open THROUGH 23:59:59 on that date in the application timezone.
     */
    public function getDeadline(): ?Carbon
    {
        try {
            $setting = SiteSetting::where('key', 'spmb_deadline')->value('value');
            if ($setting === null) {
                return null;
            }

            $trimmed = trim((string) $setting);
            if ($trimmed === '') {
                return null;
            }

            $tz = config('app.timezone', 'Asia/Jakarta');

            // Date only semantics: YYYY-MM-DD remains open through 23:59:59
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
                return Carbon::createFromFormat('Y-m-d', $trimmed, $tz)->endOfDay();
            }

            return Carbon::parse($trimmed, $tz);
        } catch (\Throwable $e) {
            Log::warning('Invalid spmb_deadline configured in site_settings', [
                'error' => $e->getMessage(),
            ]);

            // Fail-safe: if invalid format, allow registration rather than locking out students
            return null;
        }
    }

    /**
     * Check if SPMB registration is currently open.
     */
    public function isOpen(): bool
    {
        $deadline = $this->getDeadline();

        if ($deadline === null) {
            return true;
        }

        return now()->lte($deadline);
    }
}
