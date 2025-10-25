<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('employment_type', ['hourly', 'salary', 'contract'])->default('hourly')->after('department');
            $table->boolean('is_overtime_exempt')->default(false)->after('employment_type');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('is_overtime_exempt');
            $table->decimal('annual_salary', 12, 2)->nullable()->after('hourly_rate');
            
            $table->index('employment_type');
            $table->index('is_overtime_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['employment_type']);
            $table->dropIndex(['is_overtime_exempt']);
            $table->dropColumn(['employment_type', 'is_overtime_exempt', 'hourly_rate', 'annual_salary']);
        });
    }
};
