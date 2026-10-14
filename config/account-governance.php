<?php

use App\Support\AccountRole;
use App\Support\FoundationPermission;

return [
    // Target-role creation is permission-driven. The role names are labels, not ranks.
    'creation_permissions' => [
        AccountRole::Admin->value => FoundationPermission::CreateAdminAccounts->value,
        AccountRole::Osds->value => FoundationPermission::CreateOsdsAccounts->value,
        AccountRole::Chedro->value => FoundationPermission::CreateChedroAccounts->value,
        AccountRole::School->value => FoundationPermission::CreateSchoolAccounts->value,
    ],

    // Pending supervisor decision. Changing this does not bypass verification permission or scope checks.
    'allow_school_creator_to_verify' => env('OSDS_ALLOW_SCHOOL_CREATOR_TO_VERIFY', false),

    // Deployment gates only. Synthetic local accounts are intentionally exempt.
    'production_mfa_required_roles' => [
        AccountRole::SuperUser->value,
        AccountRole::Admin->value,
        AccountRole::Osds->value,
        AccountRole::Chedro->value,
    ],
];
