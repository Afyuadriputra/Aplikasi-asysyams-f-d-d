<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('assessments', 'month')) {
                $table->unsignedTinyInteger('month')->nullable()->after('assessment_type');
            }
            if (! Schema::hasColumn('assessments', 'year')) {
                $table->unsignedSmallInteger('year')->nullable()->after('month');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assessments MODIFY COLUMN assessment_type ENUM('ziyadah', 'murojaah', 'tahsin', 'tilawah', 'tahfidz', 'tajwid') NOT NULL");
        } else {
            try {
                Schema::table('assessments', function (Blueprint $table) {
                    $table->string('assessment_type')->change();
                });
            } catch (\Throwable $e) {
                //
            }
        }

        Schema::table('assessments', function (Blueprint $table) {
            try {
                $table->dropUnique(['class_group_id', 'user_id', 'assessment_type']);
            } catch (\Throwable $e) {
                // In some SQLite configurations, dropping inline table constraints is skipped
            }

            try {
                $table->unique(['class_group_id', 'user_id', 'assessment_type', 'month', 'year'], 'assessments_period_unique');
            } catch (\Throwable $e) {
                //
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            try {
                $table->dropUnique('assessments_period_unique');
            } catch (\Throwable $e) {
            }

            try {
                $table->unique(['class_group_id', 'user_id', 'assessment_type']);
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('assessments', 'year')) {
                $table->dropColumn('year');
            }
            if (Schema::hasColumn('assessments', 'month')) {
                $table->dropColumn('month');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assessments MODIFY COLUMN assessment_type ENUM('ziyadah', 'murojaah', 'tahsin', 'tilawah') NOT NULL");
        }
    }
};
