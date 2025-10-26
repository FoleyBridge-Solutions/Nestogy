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
        Schema::table('client_documents', function (Blueprint $table) {
            $table->string('storage_path')->nullable()->after('file_path');
            $table->string('storage_disk')->default('local')->after('storage_path');
            $table->integer('version')->default(1)->after('storage_disk');
            $table->boolean('is_current_version')->default(true)->after('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn(['storage_path', 'storage_disk', 'version', 'is_current_version']);
        });
    }
};
