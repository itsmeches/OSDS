<?php

namespace Database\Seeders;

use App\Support\AccountRole;
use App\Support\FoundationPermission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(FoundationPermission::cases())->mapWithKeys(function (FoundationPermission $permission) {
            $model = Permission::findOrCreate($permission->value, 'web');

            return [$permission->value => $model];
        });

        $rolePermissions = [
            AccountRole::SuperUser->value => [FoundationPermission::AccessAdminPanel, FoundationPermission::ViewUsers, FoundationPermission::ManageUsers, FoundationPermission::CreateAdminAccounts, FoundationPermission::ActivateAccounts, FoundationPermission::SuspendAccounts, FoundationPermission::DeactivateAccounts, FoundationPermission::ReactivateAccounts, FoundationPermission::AssignRoles, FoundationPermission::ViewRegions, FoundationPermission::ManageRegions, FoundationPermission::AssignRegionScope, FoundationPermission::RevokeRegionScope, FoundationPermission::AssignNationwideScope, FoundationPermission::RevokeNationwideScope, FoundationPermission::ViewInstitutions, FoundationPermission::ManageInstitutions, FoundationPermission::AssignInstitutionRepresentatives, FoundationPermission::RevokeInstitutionRepresentatives, FoundationPermission::ViewAudit, FoundationPermission::ViewNationalAudit],
            AccountRole::Admin->value => [FoundationPermission::AccessAdminPanel, FoundationPermission::ViewUsers, FoundationPermission::ManageUsers, FoundationPermission::CreateOsdsAccounts, FoundationPermission::ActivateAccounts, FoundationPermission::SuspendAccounts, FoundationPermission::DeactivateAccounts, FoundationPermission::ReactivateAccounts, FoundationPermission::AssignRoles, FoundationPermission::ViewRegions, FoundationPermission::ManageRegions, FoundationPermission::AssignRegionScope, FoundationPermission::RevokeRegionScope, FoundationPermission::ViewInstitutions, FoundationPermission::ManageInstitutions, FoundationPermission::AssignInstitutionRepresentatives, FoundationPermission::RevokeInstitutionRepresentatives, FoundationPermission::ViewAudit, FoundationPermission::ViewQuestionnaires, FoundationPermission::ReviewQuestionnaires, FoundationPermission::PublishQuestionnaireSandbox, FoundationPermission::RetireQuestionnaires, FoundationPermission::PreviewQuestionnaireSandbox, FoundationPermission::ViewQuestionnairePublications],
            AccountRole::Osds->value => [FoundationPermission::AccessAdminPanel, FoundationPermission::ViewUsers, FoundationPermission::ManageUsers, FoundationPermission::CreateChedroAccounts, FoundationPermission::ActivateAccounts, FoundationPermission::SuspendAccounts, FoundationPermission::DeactivateAccounts, FoundationPermission::ReactivateAccounts, FoundationPermission::AssignRoles, FoundationPermission::ViewRegions, FoundationPermission::AssignRegionScope, FoundationPermission::RevokeRegionScope, FoundationPermission::ViewInstitutions, FoundationPermission::ViewAudit, FoundationPermission::ViewQuestionnaires, FoundationPermission::ManageQuestionnaireDrafts, FoundationPermission::SubmitQuestionnaireReview, FoundationPermission::PreviewQuestionnaireSandbox],
            AccountRole::Chedro->value => [FoundationPermission::AccessAdminPanel, FoundationPermission::ViewUsers, FoundationPermission::ManageUsers, FoundationPermission::CreateSchoolAccounts, FoundationPermission::VerifySchoolAccounts, FoundationPermission::ActivateAccounts, FoundationPermission::SuspendAccounts, FoundationPermission::DeactivateAccounts, FoundationPermission::ReactivateAccounts, FoundationPermission::AssignRoles, FoundationPermission::ViewRegions, FoundationPermission::ViewInstitutions, FoundationPermission::ManageInstitutions, FoundationPermission::AssignInstitutionRepresentatives, FoundationPermission::RevokeInstitutionRepresentatives],
            AccountRole::School->value => [FoundationPermission::ViewInstitutions],
        ];

        foreach ($rolePermissions as $roleName => $grants) {
            Role::findOrCreate($roleName, 'web')->syncPermissions(
                collect($grants)->map(fn (FoundationPermission $permission) => $permissions[$permission->value]),
            );
        }
    }
}
