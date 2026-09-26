<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')
            ->where('status', 'success')
            ->update(['status' => 'paid']);
    }

    public function down(): void
    {
        DB::table('payments')
            ->where('status', 'paid')
            ->update(['status' => 'success']);
    }
};
