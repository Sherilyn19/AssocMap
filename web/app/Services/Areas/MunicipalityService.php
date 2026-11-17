<?php

namespace App\Services\Areas;

use App\Models\Municipality;
use App\Models\UserProfile;
use App\Services\Programs\ProgramActorService;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MunicipalityService
{
    public function __construct(private ProgramActorService $actors, private AuditLogService $audit) {}

    public function update(Municipality $municipality, array $data, UserProfile $currentUser): void
    {
        DB::transaction(function () use ($municipality, $data, $currentUser): void {
            $actor = $this->actors->lock($currentUser);
            abort_unless($actor->role?->code === 'system_admin', 403);
            // Use the recorded name even if a different name was submitted.
            $name = $municipality->name;
            if (Municipality::query()
                ->where('id', '!=', $municipality->id)
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->whereRaw('LOWER(province) = LOWER(?)', [$municipality->province])
                ->exists()) {
                throw ValidationException::withMessages(['name' => 'This locality already exists in the province.']);
            }

            $municipality->update(['name' => $name, 'psgc_code' => $data['psgc_code'] ?? null]);
            // Keep the activity history with the saved change.
            if ($municipality->wasChanged()) $this->audit->recordActor($actor, 'UPDATE MUNICIPALITY', 'Area Management', 'municipality', (string) $municipality->id);
        });
    }

    public function deactivate(Municipality $municipality, UserProfile $currentUser): void
    {
        DB::transaction(function () use ($municipality, $currentUser): void {
            $actor = $this->actors->lock($currentUser);
            abort_unless($actor->role?->code === 'system_admin', 403);
            $municipality->update(['is_active' => false]);
            if ($municipality->wasChanged()) $this->audit->recordActor($actor, 'DEACTIVATE MUNICIPALITY', 'Area Management', 'municipality', (string) $municipality->id);
        });
    }

    public function create(
        array $data,
        UserProfile $currentUser
    ): Municipality {
        return DB::transaction(function () use ($data, $currentUser): Municipality {
            $currentUser = $this->actors->lock($currentUser);
            abort_unless($currentUser->role?->code === 'system_admin', 403);
            $name = trim($data['name']);

            $duplicateExists = Municipality::query()
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->whereRaw('LOWER(province) = LOWER(?)', ['Cebu'])
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'name' => 'This municipality already exists in Cebu.',
                ]);
            }

            $municipality = new Municipality([
                'name' => $name,
                'psgc_code' => $data['psgc_code'] ?? null,
                'province' => 'Cebu',
                'region' => 'Region VII',
                'source' => 'manual',
                'is_active' => true,
            ]);

            $municipality->created_by = $currentUser->id;
            $municipality->id = (string) \Illuminate\Support\Str::uuid();

            $municipality->save();
            $this->audit->recordActor($currentUser, 'CREATE MUNICIPALITY', 'Area Management', 'municipality', (string) $municipality->id);

            return $municipality;
        });
    }
}
