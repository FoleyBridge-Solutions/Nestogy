<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recurring_tickets', function (Blueprint $table) {
            // Add new columns that the model expects
            $table->boolean('is_active')->default(true)->after('status');
            $table->date('next_run_date')->nullable()->after('next_run');
            $table->date('last_run_date')->nullable()->after('last_run');
            
            // Add missing columns from the model
            $table->string('name')->nullable()->after('title');
            $table->json('frequency_config')->nullable()->after('interval_value');
            $table->date('end_date')->nullable()->after('last_run_date');
            $table->integer('max_occurrences')->nullable()->after('end_date');
            $table->integer('occurrences_count')->default(0)->after('max_occurrences');
            $table->json('template_overrides')->nullable()->after('occurrences_count');
        });

        // Migrate existing data
        DB::table('recurring_tickets')->update([
            'is_active' => DB::raw("CASE WHEN status = 'active' THEN true ELSE false END"),
            'next_run_date' => DB::raw('DATE(next_run)'),
            'last_run_date' => DB::raw('DATE(last_run)'),
            'name' => DB::raw('title')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recurring_tickets', function (Blueprint $table) {
            // Remove the new columns
            $table->dropColumn([
                'is_active',
                'next_run_date',
                'last_run_date',
                'name',
                'frequency_config',
                'end_date',
                'max_occurrences',
                'occurrences_count',
                'template_overrides'
            ]);
        });
    }
};
