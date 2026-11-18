<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') throw new LogicException('Project status migration requires PostgreSQL.');
        DB::unprepared(file_get_contents(base_path('../supabase/migrations/027_remove_planned_project_status.sql')));
    }

    public function down(): void
    {
        throw new LogicException('The former project statuses cannot be restored automatically.');
    }
};
