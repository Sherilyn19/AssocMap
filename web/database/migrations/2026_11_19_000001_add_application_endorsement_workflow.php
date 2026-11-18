<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            // Null represents a public application awaiting its first association review.
            $table->string('endorsement_stage', 20)->nullable();
        });
        Schema::create('member_application_review_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_application_id')->constrained('member_applications')->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('user_profiles')->nullOnDelete();
            $table->string('actor_name');
            $table->string('action', 20);
            $table->text('note');
            $table->timestampTz('created_at');
            $table->index(['member_application_id', 'created_at']);
        });
        if (DB::getDriverName() === 'pgsql') {
            // Review notes are private; the server's authorized database role performs access checks.
            DB::statement('ALTER TABLE member_application_review_events ENABLE ROW LEVEL SECURITY');
            DB::statement("ALTER TABLE member_applications ADD CONSTRAINT member_application_endorsement_stage_check CHECK (endorsement_stage IS NULL OR (submission_source = 'public_gis' AND endorsement_stage IN ('endorsed', 'returned', 'declined')))");
        }
    }

    public function down(): void
    {
        throw new LogicException('Review history requires a reviewed forward correction.');
    }
};
