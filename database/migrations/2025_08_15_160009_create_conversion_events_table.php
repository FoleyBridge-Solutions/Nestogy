<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversion_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();

            $table->string('event_type'); // lead_qualified, opportunity_created, deal_closed, invoice_paid
            $table->decimal('value', 12, 2)->default(0); // Revenue value
            $table->string('currency', 3)->default('USD');

            // Attribution
            $table->unsignedBigInteger('attributed_campaign_id')->nullable();
            $table->string('attribution_model')->default('last_touch'); // first_touch, last_touch, multi_touch
            $table->json('attribution_data')->nullable(); // Detailed attribution breakdown

            $table->json('metadata')->nullable();
            $table->timestamp('converted_at');

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('attributed_campaign_id')->references('id')->on('marketing_campaigns')->onDelete('set null');

            $table->index(['event_type', 'converted_at']);
            $table->index(['attributed_campaign_id', 'converted_at']);
            $table->index(['company_id', 'converted_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversion_events');
    }
};
