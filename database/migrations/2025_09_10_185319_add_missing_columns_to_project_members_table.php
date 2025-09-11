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
    Schema::table('project_members', function (Blueprint $table) {
    $table->boolean('can_edit')->default(false);
    $table->boolean('can_manage_tasks')->default(false);
    $table->boolean('can_manage_time')->default(false);
    $table->boolean('can_view_reports')->default(false);
    $table->boolean('is_active')->default(true);
    $table->text('notes')->nullable();
    $table->softDeletes();
});
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('project_members', function (Blueprint $table) {
        $table->dropSoftDeletes();
        $table->dropColumn([
            'hourly_rate',
            'currency',
            'can_edit',
            'can_manage_tasks',
            'can_manage_time',
            'can_view_reports',
            'is_active',
            'left_at',
            'notes'
        ]);
    });
}
};
