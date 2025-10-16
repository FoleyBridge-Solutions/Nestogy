<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_applications')) {
            DB::table('payment_applications')
                ->where('applicable_type', 'App\Models\Invoice')
                ->update(['applicable_type' => 'App\Domains\Financial\Models\Invoice']);
        }

        if (Schema::hasTable('client_credit_applications')) {
            DB::table('client_credit_applications')
                ->where('applicable_type', 'App\Models\Invoice')
                ->update(['applicable_type' => 'App\Domains\Financial\Models\Invoice']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_applications')) {
            DB::table('payment_applications')
                ->where('applicable_type', 'App\Domains\Financial\Models\Invoice')
                ->update(['applicable_type' => 'App\Models\Invoice']);
        }

        if (Schema::hasTable('client_credit_applications')) {
            DB::table('client_credit_applications')
                ->where('applicable_type', 'App\Domains\Financial\Models\Invoice')
                ->update(['applicable_type' => 'App\Models\Invoice']);
        }
    }
};
