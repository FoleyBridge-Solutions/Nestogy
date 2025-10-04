<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_applications') && !Schema::hasColumn('credit_applications', 'application_date')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->date('application_date')->nullable()->after('application_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('credit_applications', 'application_date')) {
            Schema::table('credit_applications', function (Blueprint $table) {
                $table->dropColumn('application_date');
            });
        }
    }
};
