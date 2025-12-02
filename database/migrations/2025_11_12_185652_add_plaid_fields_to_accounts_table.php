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
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('plaid_item_id')->nullable()->after('plaid_id')->constrained('plaid_items')->nullOnDelete();
            $table->string('plaid_account_id')->nullable()->after('plaid_item_id')->index();
            $table->string('plaid_mask')->nullable()->after('plaid_account_id'); // Last 4 digits
            $table->string('plaid_name')->nullable()->after('plaid_mask');
            $table->string('plaid_official_name')->nullable()->after('plaid_name');
            $table->string('plaid_subtype')->nullable()->after('plaid_official_name');
            $table->decimal('available_balance', 15, 2)->nullable()->after('opening_balance');
            $table->decimal('current_balance', 15, 2)->nullable()->after('available_balance');
            $table->decimal('limit_balance', 15, 2)->nullable()->after('current_balance');
            $table->timestamp('last_synced_at')->nullable()->after('updated_at');
            $table->boolean('auto_sync_enabled')->default(true)->after('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['plaid_item_id']);
            $table->dropColumn([
                'plaid_item_id',
                'plaid_account_id',
                'plaid_mask',
                'plaid_name',
                'plaid_official_name',
                'plaid_subtype',
                'available_balance',
                'current_balance',
                'limit_balance',
                'last_synced_at',
                'auto_sync_enabled',
            ]);
        });
    }
};
