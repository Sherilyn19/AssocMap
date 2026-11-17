<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Store only the verified total and latest renewal date.
    public function up(): void
    {
        Schema::table('association_registrations', function (Blueprint $table): void {
            $table->unsignedInteger('renewal_count')->nullable();
            $table->date('latest_renewal_date')->nullable();
        });
    }

    // Keep renewal details if the migration is rolled back.
    public function down(): void {}
};
