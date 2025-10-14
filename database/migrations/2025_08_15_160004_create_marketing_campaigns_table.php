<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by_user_id');

            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['email', 'nurture', 'drip', 'event', 'webinar', 'content'])->default('email');
            $table->enum('status', ['draft', 'scheduled', 'active', 'paused', 'completed', 'archived'])->default('draft');

            // Campaign Settings
            $table->json('settings')->nullable(); // Store campaign-specific settings
            $table->json('target_criteria')->nullable(); // Lead qualification criteria
            $table->boolean('auto_enroll')->default(false);

            // Scheduling
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // Metrics
            $table->integer('total_recipients')->default(0);
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_opened')->default(0);
            $table->integer('total_clicked')->default(0);
            $table->integer('total_replied')->default(0);
            $table->integer('total_unsubscribed')->default(0);
            $table->integer('total_converted')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['company_id', 'status']);
            $table->index(['status', 'start_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
