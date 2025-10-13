<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('campaign_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');

            $table->string('name');
            $table->integer('step_number');
            $table->integer('delay_days')->default(0); // Days after previous step
            $table->integer('delay_hours')->default(0); // Hours after previous step

            // Email details
            $table->string('subject_line');
            $table->text('email_template'); // HTML content
            $table->text('email_text')->nullable(); // Plain text version

            // Conditions
            $table->json('send_conditions')->nullable(); // Conditions to send this step
            $table->json('skip_conditions')->nullable(); // Conditions to skip this step

            // Settings
            $table->boolean('is_active')->default(true);
            $table->time('send_time')->default('09:00:00'); // Preferred send time
            $table->json('send_days')->nullable(); // Days of week to send [1,2,3,4,5]

            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');

            $table->index(['campaign_id', 'step_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_sequences');
    }
};
