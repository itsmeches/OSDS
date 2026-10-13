<?php

namespace App\Filament\Resources\AuditEvents\Pages;

use App\Filament\Resources\AuditEvents\AuditEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAuditEvent extends CreateRecord
{
    protected static string $resource = AuditEventResource::class;
}
