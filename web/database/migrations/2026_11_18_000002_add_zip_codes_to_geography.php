<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Preserve existing records until their ZIP codes are verified.
    public function up(): void
    {
        foreach (['municipalities', 'barangays', 'areas'] as $name) {
            Schema::table($name, function (Blueprint $table): void { $table->string('zip_code', 4)->nullable(); });
        }
    }

    // Keep saved ZIP codes if this migration is rolled back.
    public function down(): void {}
};
