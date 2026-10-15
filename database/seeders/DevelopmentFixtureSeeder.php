<?php

namespace Database\Seeders;

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Support\AccountRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DevelopmentFixtureSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Synthetic fixtures may only be created in local or testing environments.');
        }

        $password = env('OSDS_FIXTURE_PASSWORD');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set OSDS_FIXTURE_PASSWORD to at least 12 characters before loading fixtures.');
        }

        $this->call(AuthorizationSeeder::class);

        $north = Region::query()->firstOrCreate(['code' => 'SYN-NORTH'], ['name' => 'Synthetic North Region']);
        $south = Region::query()->firstOrCreate(['code' => 'SYN-SOUTH'], ['name' => 'Synthetic South Region']);
        $academy = Institution::query()->updateOrCreate(['official_id' => 'SYN-001'], ['name' => 'Harborview Learning Institute', 'primary_region_id' => $north->id, 'is_synthetic' => true, 'organization_type' => 'hei']);
        Institution::query()->firstOrCreate(['official_id' => 'SYN-002'], ['name' => 'Pinecrest Community College', 'primary_region_id' => $south->id]);
        Institution::query()->firstOrCreate(['official_id' => 'SYN-003'], ['name' => 'Riverside Technical Academy', 'primary_region_id' => $north->id]);

        foreach (AccountRole::cases() as $role) {
            $user = User::query()->updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $role->value)).'@example.invalid'],
                ['name' => "Synthetic {$role->value}", 'password' => Hash::make($password), 'status' => 'active', 'is_synthetic' => true],
            );
            $user->syncRoles([$role->value]);

            if ($role !== AccountRole::School) {
                UserRegionAssignment::query()->firstOrCreate(
                    ['user_id' => $user->id, 'region_id' => $role === AccountRole::Chedro ? $north->id : $south->id, 'revoked_at' => null],
                    ['starts_at' => now()->startOfDay()],
                );
            } else {
                InstitutionRepresentative::query()->firstOrCreate(
                    ['user_id' => $user->id, 'institution_id' => $academy->id, 'revoked_at' => null],
                    ['starts_at' => now()->startOfDay(), 'verified_at' => now(), 'verified_by' => $user->id],
                );
            }
        }

        $this->call(QuestionnaireReferenceSeeder::class);
        $this->call(SyntheticQuestionnaireSeeder::class);
        if (app(ApplicantSandboxGuard::class)->allowed()) {
            $admin = User::role(AccountRole::Admin->value)->firstOrFail();
            $chedro = User::role(AccountRole::Chedro->value)->where('is_synthetic', true)->firstOrFail();
            foreach (OsdsModule::query()->whereIn('code', ['registration', 'acsa'])->get() as $module) {
                ApplicantSandboxEligibilityGrant::query()->firstOrCreate(
                    ['institution_id' => $academy->id, 'module_id' => $module->id, 'revoked_at' => null],
                    ['granted_by' => $admin->id, 'starts_at' => now()->startOfDay(), 'reason' => 'Synthetic Phase 3B testing only.'],
                );
                SyntheticEvidenceReviewerAssignment::query()->firstOrCreate(
                    ['reviewer_id' => $chedro->id, 'region_id' => $north->id, 'module_id' => $module->id, 'revoked_at' => null],
                    ['granted_by' => $admin->id, 'starts_at' => now()->startOfDay(), 'reason' => 'Synthetic Phase 3C evidence-review testing only.'],
                );
            }
        }
    }
}
