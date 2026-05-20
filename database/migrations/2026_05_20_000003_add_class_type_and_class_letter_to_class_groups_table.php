<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_groups', function (Blueprint $table): void {
            $table->string('class_type')->nullable()->after('slug')->index();
            $table->string('class_letter', 1)->nullable()->after('class_type')->index();
        });
    }

    public function down(): void
    {
        Schema::table('class_groups', function (Blueprint $table): void {
            $table->dropIndex(['class_type']);
            $table->dropIndex(['class_letter']);
            $table->dropColumn(['class_type', 'class_letter']);
        });
    }
};
