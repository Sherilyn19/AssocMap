<?php

namespace App\Services\Areas;

use App\Models\Barangay;
use App\Models\UserProfile;
use App\Services\Programs\ProgramActorService;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BarangayService
{
    public function __construct(private ProgramActorService $actors, private AuditLogService $audit) {}

    public function update(Barangay $barangay, array $data, UserProfile $currentUser): void
    {
        DB::transaction(function () use ($barangay, $data, $currentUser): void {
            $actor = $this->actors->lock($currentUser);
            abort_unless($actor->role?->code === 'system_admin', 403);
            // Use the recorded name even if a different name was submitted.
            $name = $barangay->name;
            if (Barangay::query()
                ->where('id', '!=', $barangay->id)
                ->where('municipality_id', $data['municipality_id'])
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->exists()) {
                throw ValidationException::withMessages(['name' => 'This barangay already exists under the selected municipality or city.']);
            }

            $barangay->update([
                'name' => $name,
                'municipality_id' => $data['municipality_id'],
                'psgc_code' => $data['psgc_code'] ?? null,
                'zip_code' => $data['zip_code'],
            ]);
            // Keep the activity history with the saved change.
            if ($barangay->wasChanged()) $this->audit->recordActor($actor, 'UPDATE BARANGAY', 'Area Management', 'barangay', (string) $barangay->id);
        });
    }

    public function deactivate(Barangay $barangay, UserProfile $currentUser): void
    {
        DB::transaction(function () use ($barangay, $currentUser): void {
            $actor = $this->actors->lock($currentUser);
            abort_unless($actor->role?->code === 'system_admin', 403);
            $barangay->update(['is_active' => false]);
            if ($barangay->wasChanged()) $this->audit->recordActor($actor, 'DEACTIVATE BARANGAY', 'Area Management', 'barangay', (string) $barangay->id);
        });
    }

    public function create(
        array $data,
        UserProfile $currentUser
    ): Barangay {
        return DB::transaction(function () use ($data, $currentUser): Barangay {
            $currentUser = $this->actors->lock($currentUser);
            abort_unless($currentUser->role?->code === 'system_admin', 403);
            $name = trim($data['name']);

            $duplicateExists = Barangay::query()
                ->where('municipality_id', $data['municipality_id'])
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'name' => 'This barangay already exists under the selected municipality or city.',
                ]);
            }

            $barangay = new Barangay([
                'municipality_id' => $data['municipality_id'],
                'name' => $name,
                'psgc_code' => $data['psgc_code'] ?? null,
                'zip_code' => $data['zip_code'],
                'source' => 'manual',
                'is_active' => true,
            ]);

            $barangay->created_by = $currentUser->id;
            $barangay->id = (string) \Illuminate\Support\Str::uuid();

            $barangay->save();
            $this->audit->recordActor($currentUser, 'CREATE BARANGAY', 'Area Management', 'barangay', (string) $barangay->id);

            return $barangay;
        });
    }
}
