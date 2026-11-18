<?php

namespace Tests\Feature;

use App\Exceptions\MediaLifecycleException;
use App\Models\MediaPhoto;
use App\Models\ProgramActivityRecord;
use App\Services\Media\PhotoLifecycleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\CreatesLifecycleAuditTable;
use Tests\Support\CreatesMediaFixture;
use Tests\TestCase;

final class ProgramActivityPhotoLifecycleTest extends TestCase
{
    use CreatesLifecycleAuditTable;
    use CreatesMediaFixture;

    private Request $mediaRequest;
    private PhotoLifecycleService $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mediaFixture();
        Schema::create('program_activity_records', fn (Blueprint $table) => $table->uuid('id')->primary());
        Schema::table('media_photos', fn (Blueprint $table) => $table->uuid('program_activity_id')->nullable());
        // The SQLite fixture uses the old parent check; PostgreSQL migration coverage is separate.
        DB::statement('PRAGMA ignore_check_constraints = ON');
    }

    public function test_activity_photos_use_the_private_activity_prefix_and_twenty_slot_limit(): void
    {
        $parent = $this->mediaParent(ProgramActivityRecord::class);
        for ($i = 0; $i < 20; $i++) {
            $file = UploadedFile::fake()->image('activity.png', 32, 16);
            $reservation = $this->lifecycle->reserve($parent, $file, (string) Str::uuid(), $this->mediaRequest, $this->mediaAuthorization());
            $photo = $this->lifecycle->upload($reservation['id'], $file, $this->mediaRequest, $this->mediaAuthorization());
            $this->assertSame('ready', $photo['status']);
            $this->assertStringStartsWith('program-activities/', MediaPhoto::findOrFail($photo['id'])->object_prefix);
        }
        $this->assertCount(40, Storage::disk('media_private')->allFiles('program-activities'));
        $this->expectException(MediaLifecycleException::class);
        $this->expectExceptionMessage('capacity_reached');
        $this->lifecycle->reserve($parent, UploadedFile::fake()->image('extra.png'), (string) Str::uuid(), $this->mediaRequest, $this->mediaAuthorization());
    }
}
