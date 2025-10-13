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
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('client_record_id')->nullable()->after('currency');
            $table->string('suspension_reason')->nullable()->after('suspended_at');

            // Add foreign key if clients table exists
            if (Schema::hasTable('clients')) {
                $table->foreign('client_record_id')->references('id')->on('clients')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasTable('clients')) {
                $table->dropForeign(['client_record_id']);
            }
            $table->dropColumn(['client_record_id', 'suspension_reason']);
        });
    }
};
