<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('name');
            $table->integer('progress')->default(0)->after('status');
            $table->string('priority')->default('medium')->after('progress');
            $table->date('start_date')->nullable()->after('priority');
            $table->decimal('budget', 10, 2)->nullable()->after('manager_id');
            $table->decimal('actual_cost', 10, 2)->nullable()->after('budget');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['status', 'progress', 'priority', 'start_date', 'budget', 'actual_cost']);
        });
    }
};
