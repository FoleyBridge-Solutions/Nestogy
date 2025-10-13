<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_source_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable(); // If converted to client

            // Contact Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('title')->nullable();
            $table->string('website')->nullable();

            // Address Information
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();

            // Lead Details
            $table->enum('status', ['new', 'contacted', 'qualified', 'unqualified', 'nurturing', 'converted', 'lost'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('industry')->nullable();
            $table->integer('company_size')->nullable(); // Number of employees
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();

            // Scoring
            $table->integer('total_score')->default(0);
            $table->integer('demographic_score')->default(0);
            $table->integer('behavioral_score')->default(0);
            $table->integer('fit_score')->default(0);
            $table->integer('urgency_score')->default(0);
            $table->timestamp('last_scored_at')->nullable();

            // Tracking
            $table->timestamp('first_contact_date')->nullable();
            $table->timestamp('last_contact_date')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->onDelete('set null');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'total_score']);
            $table->index(['assigned_user_id', 'status']);
            $table->index(['email']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('leads');
    }
};
