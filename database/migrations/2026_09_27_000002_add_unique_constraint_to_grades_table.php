<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToGradesTable extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->unique(['user_id', 'subject_id', 'semester_id'], 'grades_user_subject_semester_unique');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique('grades_user_subject_semester_unique');
        });
    }
}

return new AddUniqueConstraintToGradesTable;
