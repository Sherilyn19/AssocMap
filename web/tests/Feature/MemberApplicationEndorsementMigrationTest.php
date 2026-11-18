<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\IsolatedDatabaseManager;
use Tests\TestCase;

class MemberApplicationEndorsementMigrationTest extends TestCase
{
    public function test_existing_public_applications_wait_for_association_review(): void
    {
        $this->assertInstanceOf(IsolatedDatabaseManager::class, DB::getFacadeRoot());
        Schema::create('user_profiles', fn (Blueprint $table) => $table->uuid('id')->primary());
        Schema::create('member_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('submission_source');
            $table->string('status');
        });
        DB::table('member_applications')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'submission_source' => 'public_gis', 'status' => 'pending'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'submission_source' => 'public_gis', 'status' => 'approved'],
        ]);
        $migration = require database_path('migrations/2026_11_19_000001_add_application_endorsement_workflow.php');
        $migration->up();
        $this->assertNull(DB::table('member_applications')->where('status', 'pending')->value('endorsement_stage'));
        $this->assertSame('approved', DB::table('member_applications')->where('id', '22222222-2222-4222-8222-222222222222')->value('status'));
        $this->assertTrue(Schema::hasTable('member_application_review_events'));
    }
}
