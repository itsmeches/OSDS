<?php

namespace App\Filament\Resources\PublicationManifests\Pages;

use App\Filament\Resources\PublicationManifests\PublicationManifestResource;
use Filament\Resources\Pages\ListRecords;

class ListPublicationManifests extends ListRecords
{
    protected static string $resource = PublicationManifestResource::class;

    public function getSubheading(): ?string
    {
        return 'Staff previews are read only. Applicant availability also requires current dates, eligibility and representation. Dates are shown in UTC.';
    }
}
