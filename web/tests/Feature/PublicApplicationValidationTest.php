<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use App\Models\Association;
use App\Models\GisLocation;
use App\Models\Project;
use App\Services\Members\PublicApplicationSiteService;
use App\Services\GIS\GisQueryService;
use App\Services\Members\MemberApplicationPhotoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

final class PublicApplicationValidationTest extends TestCase
{
    // Exercise the public workflow with isolated records and private-photo storage mocked.
    public function test_public_submission_creates_a_pending_application_for_the_selected_association(): void
    {
        $this->withoutVite();
        Schema::create('sex_categories', function (Blueprint $table) { $table->id(); $table->boolean('is_active'); });
        Schema::create('roles', function (Blueprint $table) { $table->id(); $table->string('code'); $table->boolean('is_active'); });
        Schema::create('user_profiles', function (Blueprint $table) { $table->uuid('id')->primary(); $table->integer('role_id'); $table->boolean('is_active'); });
        Schema::create('association_positions', function (Blueprint $table) { $table->id(); $table->string('code'); $table->boolean('is_active'); });
        Schema::create('members', function (Blueprint $table) { $table->uuid('id'); $table->uuid('association_id'); $table->date('birth_date'); $table->string('first_name'); $table->string('middle_name')->nullable(); $table->string('last_name'); $table->string('suffix')->nullable(); });
        Schema::create('member_applications', function (Blueprint $table) {
            $table->uuid('id')->primary(); $table->uuid('association_id'); $table->uuid('submitted_by_user_id')->nullable();
            $table->string('submission_source'); $table->uuid('public_location_id'); $table->string('public_location_title');
            $table->string('first_name'); $table->string('middle_name')->nullable(); $table->string('last_name'); $table->string('suffix')->nullable();
            $table->date('birth_date'); $table->integer('sex_id'); $table->string('contact_number'); $table->string('email')->nullable();
            $table->text('reason_for_applying'); $table->integer('position_id'); $table->string('status'); $table->string('applicant_photo_path'); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) { $table->uuid('id'); $table->uuid('actor_user_id')->nullable(); $table->string('action_type'); $table->string('module'); $table->string('entity_type'); $table->string('entity_id'); $table->timestamp('performed_at'); });
        DB::table('sex_categories')->insert(['id' => 1, 'is_active' => true]);
        DB::table('roles')->insert(['id' => 1, 'code' => 'field_officer', 'is_active' => true]);
        DB::table('user_profiles')->insert(['id' => '22222222-2222-4222-8222-222222222222', 'role_id' => 1, 'is_active' => true]);
        DB::table('association_positions')->insert(['id' => 1, 'code' => 'MEMBER', 'is_active' => true]);
        DB::connection()->getPdo()->sqliteCreateFunction('regexp_replace', fn ($value, $pattern, $replacement, $flags) => preg_replace('/\s+/u', $replacement, $value ?? ''), 4);
        DB::connection()->getPdo()->sqliteCreateFunction('btrim', fn ($value) => $value === null ? null : trim($value), 1);
        $association = new Association();
        $association->id = '33333333-3333-4333-8333-333333333333';
        $association->primary_field_officer_id = '22222222-2222-4222-8222-222222222222';
        $project = new Project(); $project->setRelation('association', $association);
        $locked = new GisLocation(); $locked->setRelation('project', $project);
        $site = (object) ['id' => '11111111-1111-4111-8111-111111111111', 'public_title' => 'Published site', 'application_association_id' => $association->id];
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturnSelf(); $builder->shouldReceive('select')->andReturnSelf(); $builder->shouldReceive('firstOrFail')->andReturn($site);
        $gis = Mockery::mock(GisQueryService::class); $gis->shouldReceive('scoped')->with(null)->andReturn($builder); $this->app->instance(GisQueryService::class, $gis);
        $sites = Mockery::mock(PublicApplicationSiteService::class); $sites->shouldReceive('lock')->andReturn($locked); $this->app->instance(PublicApplicationSiteService::class, $sites);
        $photos = Mockery::mock(MemberApplicationPhotoService::class); $photos->shouldReceive('store')->once()->andReturn('private-photo.jpg'); $this->app->instance(MemberApplicationPhotoService::class, $photos);

        $payload = [
            'first_name' => 'Maria', 'last_name' => 'Santos', 'birth_date' => '1990-01-01', 'sex_id' => 1,
            'contact_number' => '09123456789', 'email' => 'maria@example.test', 'reason_for_applying' => 'I would like to participate.',
            'acknowledgement' => '1', 'applicant_photo' => UploadedFile::fake()->image('applicant.jpg'),
        ];
        $this->post('/membership/apply/'.$site->id, $payload)->assertRedirect(route('public.application.received'));

        $this->assertDatabaseHas('member_applications', ['association_id' => $association->id, 'submission_source' => 'public_gis',
            'status' => 'pending', 'email' => 'maria@example.test', 'public_location_title' => 'Published site']);
        // The public event identifies the application without inventing an account actor.
        $applicationId = DB::table('member_applications')->value('id');
        $this->assertDatabaseHas('audit_logs', ['actor_user_id' => null, 'action_type' => 'SUBMIT PUBLIC MEMBER APPLICATION',
            'module' => 'Member Applications', 'entity_type' => 'member_application', 'entity_id' => $applicationId]);
        $payload['applicant_photo'] = UploadedFile::fake()->image('applicant.jpg');
        $this->post('/membership/apply/'.$site->id, $payload)->assertSessionHasErrors('application');
        $this->assertSame(1, DB::table('member_applications')->count());
    }

    public function test_public_application_rejects_too_many_words_missing_consent_and_honeypot_input(): void
    {
        $this->withoutVite();
        $response = $this->from('/membership/apply/11111111-1111-4111-8111-111111111111')
            ->post('/membership/apply/11111111-1111-4111-8111-111111111111', [
                'first_name' => 'Maria', 'last_name' => 'Santos', 'birth_date' => '1990-01-01',
                'contact_number' => '09123456789', 'reason_for_applying' => implode(' ', array_fill(0, 501, 'word')),
                'applicant_photo' => UploadedFile::fake()->image('applicant.jpg'),
                'website' => 'automated submission',
            ]);

        $response->assertSessionHasErrors(['reason_for_applying', 'acknowledgement', 'website']);
    }

    public function test_public_application_requires_mobile_number_and_photo(): void
    {
        $this->withoutVite();
        $this->post('/membership/apply/11111111-1111-4111-8111-111111111111', [
            'first_name' => 'Maria', 'last_name' => 'Santos', 'birth_date' => '1990-01-01',
            'reason_for_applying' => 'I would like to participate.', 'acknowledgement' => '1',
        ])->assertSessionHasErrors(['contact_number', 'applicant_photo']);
    }

    public function test_public_submission_is_rate_limited(): void
    {
        $url = '/membership/apply/11111111-1111-4111-8111-111111111111';
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post($url, [])->assertRedirect();
        }
        $this->post($url, [])->assertStatus(429);
    }

    public function test_public_application_validates_philippine_mobile_number_format(): void
    {
        $url = '/membership/apply/11111111-1111-4111-8111-111111111111';
        foreach (['0912345678', '08123456789'] as $number) {
            $this->post($url, ['contact_number' => $number])->assertSessionHasErrors('contact_number');
        }
        $this->post($url, ['contact_number' => '09123456789'])->assertSessionDoesntHaveErrors('contact_number');
    }
}
