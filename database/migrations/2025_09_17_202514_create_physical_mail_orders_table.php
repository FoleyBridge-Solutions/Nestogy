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
        Schema::create('physical_mail_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->string('mailable_type'); // letter/postcard/cheque/self_mailer
            $table->uuid('mailable_id');
            $table->string('postgrid_id')->nullable()->unique();
            $table->enum('status', ['pending', 'ready', 'printing', 'processed_for_delivery', 'completed', 'cancelled', 'failed'])->default('pending');

            // US Intelligent Mail Barcode tracking
            $table->enum('imb_status', ['entered_mail_stream', 'out_for_delivery', 'returned_to_sender'])->nullable();
            $table->timestamp('imb_date')->nullable();
            $table->string('imb_zip_code')->nullable();

            $table->string('tracking_number')->nullable(); // For certified/registered
            $table->string('mailing_class')->default('first_class');
            $table->timestamp('send_date')->nullable();
            $table->decimal('cost', 8, 2)->nullable();
            $table->text('pdf_url')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('formatted_address')->nullable();
            $table->timestamps();

            $table->index(['mailable_type', 'mailable_id']);
            $table->index(['client_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index(['latitude', 'longitude'], 'physical_mail_orders_location_index');
            $table->index('postgrid_id');
            $table->index('status');
            $table->index('send_date');
            $table->index('tracking_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_mail_orders');
    }
};
