<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Add SEC only if the catalog does not already contain it.
    public function up(): void
    {
        $name = 'Securities and Exchange Commission';
        $exists = DB::table('registration_agencies')->where('code', 'SEC')->orWhereRaw('LOWER(name) = ?', [strtolower($name)])->exists();
        if (! $exists) {
            DB::table('registration_agencies')->insert(['code' => 'SEC', 'name' => $name, 'registration_kind' => 'registration', 'is_active' => true]);
        }
    }

    // Keep registration references intact if a migration is rolled back.
    public function down(): void {}
};
