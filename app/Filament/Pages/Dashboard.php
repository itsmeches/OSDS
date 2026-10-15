<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getSubheading(): string
    {
        return 'Review the work available to your account and continue from the relevant task.';
    }
}
