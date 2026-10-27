<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return 'Your workspace';
    }

    public function getSubheading(): string
    {
        return 'Choose an authorized task. Application actions depend on your current assignments and the case stage.';
    }
}
