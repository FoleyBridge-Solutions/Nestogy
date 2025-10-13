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
        Schema::table('contacts', function (Blueprint $table) {
            // Communication Preferences
            $table->string('preferred_contact_method', 50)->nullable()->default('email');
            $table->string('best_time_to_contact', 50)->nullable()->default('anytime');
            $table->string('timezone', 100)->nullable();
            $table->string('language', 50)->nullable()->default('en');
            $table->boolean('do_not_disturb')->default(false);
            $table->boolean('marketing_opt_in')->default(false);

            // Professional Details
            $table->string('linkedin_url')->nullable();
            $table->string('assistant_name')->nullable();
            $table->string('assistant_email')->nullable();
            $table->string('assistant_phone', 50)->nullable();
            $table->unsignedBigInteger('reports_to_id')->nullable();
            $table->text('work_schedule')->nullable();
            $table->text('professional_bio')->nullable();

            // Location & Availability
            $table->unsignedBigInteger('office_location_id')->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('is_after_hours_contact')->default(false);
            $table->date('out_of_office_start')->nullable();
            $table->date('out_of_office_end')->nullable();

            // Social & Web Presence
            $table->string('website')->nullable();
            $table->string('twitter_handle', 100)->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_handle', 100)->nullable();
            $table->string('company_blog')->nullable();

            // Foreign key constraints
            $table->foreign('reports_to_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('office_location_id')->references('id')->on('locations')->onDelete('set null');

            // Indexes for searchable fields
            $table->index('preferred_contact_method');
            $table->index('timezone');
            $table->index('language');
            $table->index('is_emergency_contact');
            $table->index('is_after_hours_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['reports_to_id']);
            $table->dropForeign(['office_location_id']);

            // Drop indexes
            $table->dropIndex(['preferred_contact_method']);
            $table->dropIndex(['timezone']);
            $table->dropIndex(['language']);
            $table->dropIndex(['is_emergency_contact']);
            $table->dropIndex(['is_after_hours_contact']);

            // Drop columns
            $table->dropColumn([
                'preferred_contact_method',
                'best_time_to_contact',
                'timezone',
                'language',
                'do_not_disturb',
                'marketing_opt_in',
                'linkedin_url',
                'assistant_name',
                'assistant_email',
                'assistant_phone',
                'reports_to_id',
                'work_schedule',
                'professional_bio',
                'office_location_id',
                'is_emergency_contact',
                'is_after_hours_contact',
                'out_of_office_start',
                'out_of_office_end',
                'website',
                'twitter_handle',
                'facebook_url',
                'instagram_handle',
                'company_blog',
            ]);
        });
    }
};
