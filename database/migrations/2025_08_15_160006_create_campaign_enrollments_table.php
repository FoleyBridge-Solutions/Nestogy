<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('campaign_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();

            $table->enum('status', ['enrolled', 'active', 'completed', 'paused', 'unsubscribed', 'bounced'])->default('enrolled');
            $table->integer('current_step')->default(0);
            $table->timestamp('enrolled_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Tracking
            $table->integer('emails_sent')->default(0);
            $table->integer('emails_opened')->default(0);
            $table->integer('emails_clicked')->default(0);
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');

            $table->index(['campaign_id', 'status']);
            $table->index(['next_send_at']);
            $table->index(['lead_id', 'status']);
            $table->index(['contact_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_enrollments');
    }
};
