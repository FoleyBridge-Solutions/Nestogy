<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attribution_touchpoints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();

            $table->string('touchpoint_type'); // email_open, email_click, website_visit, form_submit, etc.
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('source')->nullable(); // utm_source
            $table->string('medium')->nullable(); // utm_medium
            $table->string('campaign')->nullable(); // utm_campaign
            $table->string('content')->nullable(); // utm_content
            $table->string('term')->nullable(); // utm_term

            $table->string('page_url')->nullable();
            $table->string('referrer_url')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamp('touched_at');

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');

            $table->index(['lead_id', 'touched_at']);
            $table->index(['contact_id', 'touched_at']);
            $table->index(['client_id', 'touched_at']);
            $table->index(['campaign_id', 'touched_at']);
            $table->index(['touchpoint_type', 'touched_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attribution_touchpoints');
    }
};
