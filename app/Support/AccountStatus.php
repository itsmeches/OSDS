<?php

namespace App\Support;

enum AccountStatus: string
{
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';
}
