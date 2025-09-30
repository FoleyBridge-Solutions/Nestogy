<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE project_members DROP CONSTRAINT IF EXISTS project_members_role_check;');
        DB::statement("ALTER TABLE project_members ADD CONSTRAINT project_members_role_check CHECK (role IN ('manager', 'lead', 'developer', 'designer', 'tester', 'analyst', 'consultant', 'coordinator', 'client', 'observer', 'member'));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE project_members DROP CONSTRAINT IF EXISTS project_members_role_check;');
        DB::statement("ALTER TABLE project_members ADD CONSTRAINT project_members_role_check CHECK (role IN ('manager', 'lead', 'developer', 'designer', 'tester', 'analyst', 'consultant', 'coordinator', 'client', 'observer'));");
    }
};
