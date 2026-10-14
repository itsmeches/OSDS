<?php

namespace App\Filament\Resources\ConfigurationReviewRequests\Pages;

use App\Filament\Resources\ConfigurationReviewRequests\ConfigurationReviewRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListConfigurationReviewRequests extends ListRecords
{
    protected static string $resource = ConfigurationReviewRequestResource::class;
}
