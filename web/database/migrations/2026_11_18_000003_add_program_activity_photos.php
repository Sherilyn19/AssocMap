<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') throw new LogicException('Program activity photos require PostgreSQL.');
        DB::unprepared(file_get_contents(base_path('../supabase/migrations/026_program_activity_photos.sql')));
    }

    public function down(): void
    {
        throw new LogicException('Program activity photos require a reviewed forward correction.');
    }
};
