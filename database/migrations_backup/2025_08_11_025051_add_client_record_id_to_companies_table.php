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
            // Link to the client record in Company 1 for billing
            $table->foreignId('client_record_id')->nullable()->constrained('clients')->onDelete('set null');
            
            // Company status for tenant management
            $table->boolean('is_active')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            
            // Indexes
            $table->index('client_record_id');
            $table->index(['is_active', 'suspended_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['client_record_id']);
            $table->dropIndex(['client_record_id']);
            $table->dropIndex(['is_active', 'suspended_at']);
            
            $table->dropColumn([
                'client_record_id',
                'is_active',
                'suspended_at',
                'suspension_reason',
            ]);
        });
    }
};
