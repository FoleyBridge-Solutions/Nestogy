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
        Schema::create('hr_settings_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('overridable_type');
            $table->unsignedBigInteger('overridable_id');
            $table->string('setting_key');
            $table->json('setting_value');
            $table->timestamps();

            $table->unique(['company_id', 'overridable_type', 'overridable_id', 'setting_key'], 'hr_settings_override_unique');
            $table->index(['company_id', 'overridable_type', 'overridable_id'], 'hr_settings_override_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_settings_overrides');
    }
};
