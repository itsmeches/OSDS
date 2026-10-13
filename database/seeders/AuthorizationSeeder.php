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
            AccountRole::SuperUser->value => FoundationPermission::cases(),
            AccountRole::Admin->value => FoundationPermission::cases(),
            AccountRole::Osds->value => [FoundationPermission::AccessAdminPanel, FoundationPermission::ViewUsers, FoundationPermission::ViewRegions, FoundationPermission::ViewInstitutions, FoundationPermission::ViewAudit],
            AccountRole::Chedro->value => [FoundationPermission::AccessAdminPanel, FoundationPermission::ViewUsers, FoundationPermission::ViewRegions, FoundationPermission::ViewInstitutions, FoundationPermission::ManageInstitutions, FoundationPermission::AssignInstitutionRepresentatives],
            AccountRole::School->value => [FoundationPermission::ViewInstitutions],
        ];

        foreach ($rolePermissions as $roleName => $grants) {
            Role::findOrCreate($roleName, 'web')->syncPermissions(
                collect($grants)->map(fn (FoundationPermission $permission) => $permissions[$permission->value]),
            );
        }
    }
}
