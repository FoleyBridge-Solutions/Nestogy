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
        if (!Schema::hasTable('quotes')) {
            Schema::create('quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->onDelete('cascade');
                $table->string('status')->default('draft');
                $table->timestamps();
                $table->softDeletes();
            });
        }
        
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('quotes', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('sent_at');
            }
            if (!Schema::hasColumn('quotes', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('viewed_at');
            }
            if (!Schema::hasColumn('quotes', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('accepted_at');
            }
            
            $table->index(['sent_at']);
            $table->index(['status', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'viewed_at', 'accepted_at', 'declined_at']);
        });
    }
};
