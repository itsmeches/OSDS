<?php

namespace App\Support;

enum FoundationPermission: string
{
    case AccessAdminPanel = 'platform.admin.access';
    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';
    case CreateAdminAccounts = 'accounts.create.admin';
    case CreateOsdsAccounts = 'accounts.create.osds';
    case CreateChedroAccounts = 'accounts.create.chedro';
    case CreateSchoolAccounts = 'accounts.create.school';
    case VerifySchoolAccounts = 'accounts.verify.school';
    case ActivateAccounts = 'accounts.activate';
    case SuspendAccounts = 'accounts.suspend';
    case DeactivateAccounts = 'accounts.deactivate';
    case ReactivateAccounts = 'accounts.reactivate';
    case AssignRoles = 'roles.assign';
    case ViewRegions = 'regions.view';
    case ManageRegions = 'regions.manage';
    case AssignRegionScope = 'region_scopes.assign';
    case RevokeRegionScope = 'region_scopes.revoke';
    case AssignNationwideScope = 'nationwide_scopes.assign';
    case RevokeNationwideScope = 'nationwide_scopes.revoke';
    case ViewInstitutions = 'institutions.view';
    case ManageInstitutions = 'institutions.manage';
    case AssignInstitutionRepresentatives = 'institution_representatives.assign';
    case RevokeInstitutionRepresentatives = 'institution_representatives.revoke';
    case ViewAudit = 'audit.view';
    case ViewNationalAudit = 'audit.view.national';
}
