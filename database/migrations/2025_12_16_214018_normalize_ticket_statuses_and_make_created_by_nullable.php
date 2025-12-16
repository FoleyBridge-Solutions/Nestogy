<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Normalizes ticket statuses to lowercase with underscores and makes
     * created_by nullable to support portal-created tickets.
     */
    public function up(): void
    {
        // First, make created_by nullable to support portal-created tickets
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });

        // Normalize existing status values to lowercase with underscores
        $statusMappings = [
            // Map various cases to normalized lowercase
            'New' => 'new',
            'NEW' => 'new',
            'Open' => 'open',
            'OPEN' => 'open',
            'In Progress' => 'in_progress',
            'In-Progress' => 'in_progress',
            'in-progress' => 'in_progress',
            'IN PROGRESS' => 'in_progress',
            'IN_PROGRESS' => 'in_progress',
            'Pending' => 'pending',
            'PENDING' => 'pending',
            'On Hold' => 'on_hold',
            'On-Hold' => 'on_hold',
            'on-hold' => 'on_hold',
            'ON HOLD' => 'on_hold',
            'ON_HOLD' => 'on_hold',
            'Waiting' => 'waiting',
            'WAITING' => 'waiting',
            'Resolved' => 'resolved',
            'RESOLVED' => 'resolved',
            'Closed' => 'closed',
            'CLOSED' => 'closed',
            'Cancelled' => 'cancelled',
            'CANCELLED' => 'cancelled',
            'Canceled' => 'cancelled',
            'CANCELED' => 'cancelled',
        ];

        foreach ($statusMappings as $oldStatus => $newStatus) {
            DB::table('tickets')
                ->where('status', $oldStatus)
                ->update(['status' => $newStatus]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert created_by to NOT NULL (will fail if there are null values)
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });

        // Revert status values to title case
        $statusMappings = [
            'new' => 'New',
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'pending' => 'Pending',
            'on_hold' => 'On Hold',
            'waiting' => 'Waiting',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
        ];

        foreach ($statusMappings as $oldStatus => $newStatus) {
            DB::table('tickets')
                ->where('status', $oldStatus)
                ->update(['status' => $newStatus]);
        }
    }
};
