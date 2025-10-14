<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type'); // email_sent, email_opened, email_clicked, call_made, meeting_scheduled, etc.
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store additional data like email ID, campaign ID, etc.
            $table->timestamp('activity_date');
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['lead_id', 'activity_date']);
            $table->index(['type', 'activity_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('lead_activities');
    }
};
