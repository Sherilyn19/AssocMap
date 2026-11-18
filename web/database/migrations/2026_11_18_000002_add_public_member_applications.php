<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Apply the canonical SQL through Laravel's migration history.
        if (DB::getDriverName() !== 'pgsql') throw new LogicException('Public applications require PostgreSQL.');
        DB::unprepared(file_get_contents(base_path('../supabase/migrations/025_public_member_applications.sql')));
    }

    public function down(): void
    {
        throw new LogicException('Public applications require a reviewed forward correction.');
    }
};
