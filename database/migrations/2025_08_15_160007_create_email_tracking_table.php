<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('tracking_id')->unique(); // UUID for tracking

            // Recipients
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('recipient_email');

            // Campaign/Email details
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('campaign_sequence_id')->nullable();
            $table->string('email_type')->default('campaign'); // campaign, transactional, manual
            $table->string('subject_line');

            // Delivery tracking
            $table->enum('status', ['sent', 'delivered', 'bounced', 'failed'])->default('sent');
            $table->timestamp('sent_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->text('bounce_reason')->nullable();

            // Engagement tracking
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->timestamp('first_clicked_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->integer('click_count')->default(0);
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            // User agent tracking
            $table->text('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('location')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');
            $table->foreign('campaign_sequence_id')->references('id')->on('campaign_sequences')->onDelete('cascade');

            $table->index(['tracking_id']);
            $table->index(['recipient_email']);
            $table->index(['campaign_id', 'sent_at']);
            $table->index(['lead_id', 'sent_at']);
            $table->index(['contact_id', 'sent_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_tracking');
    }
};
