<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing varchar data to JSON format
        DB::statement("
            UPDATE categories 
            SET type = CONCAT('[\"', type, '\"]')::jsonb 
            WHERE type IS NOT NULL 
            AND type::text NOT LIKE '[%'
        ");
        
        // Change column type to jsonb
        DB::statement('ALTER TABLE categories ALTER COLUMN type TYPE jsonb USING type::jsonb');
        
        // Add GIN index if it doesn't exist
        DB::statement('
            CREATE INDEX IF NOT EXISTS categories_type_gin_index 
            ON categories USING GIN (type)
        ');
    }

    public function down(): void
    {
        // Convert JSON back to varchar
        DB::statement("
            UPDATE categories 
            SET type = (type->>0)::varchar 
            WHERE jsonb_array_length(type) = 1
        ");
        
        DB::statement('ALTER TABLE categories ALTER COLUMN type TYPE varchar(255)');
        DB::statement('DROP INDEX IF EXISTS categories_type_gin_index');
    }
};
