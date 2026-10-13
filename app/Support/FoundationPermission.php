<?php

namespace App\Support;

enum FoundationPermission: string
{
    case AccessAdminPanel = 'platform.admin.access';
    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';
    case ViewRegions = 'regions.view';
    case ManageRegions = 'regions.manage';
    case AssignRegionScope = 'region_scopes.assign';
    case ViewInstitutions = 'institutions.view';
    case ManageInstitutions = 'institutions.manage';
    case AssignInstitutionRepresentatives = 'institution_representatives.assign';
    case ViewAudit = 'audit.view';
}
