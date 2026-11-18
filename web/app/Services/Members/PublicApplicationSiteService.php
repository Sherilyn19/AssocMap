<?php

namespace App\Services\Members;

use App\Models\GisLocation;
use App\Services\GIS\GisAccessService;

class PublicApplicationSiteService
{
    public function __construct(private GisAccessService $access) {}

    public function lock(string $id): GisLocation
    {
        // Reuse the GIS lock order when checking the selected public location.
        return $this->access->location($id, null);
    }
}
