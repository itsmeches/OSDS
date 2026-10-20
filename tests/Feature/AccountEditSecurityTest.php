<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Identity\AccountGovernance;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

class AccountEditSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->seed(AuthorizationSeeder::class);
    }

    private function account(AccountRole $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function grant(User $user, Region $region): UserRegionAssignment
    {
        return UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $user->id, 'region_id' => $region->id, 'starts_at' => now()->subMinute()]));
    }

    private function pair(AccountRole $actorRole = AccountRole::Admin, AccountRole $targetRole = AccountRole::Osds): array
    {
        $region = Region::create(['code' => 'SEC007-'.Region::count(), 'name' => 'Disposable security region']);
        $actor = $this->account($actorRole);
        $target = $this->account($targetRole);
        $grant = $this->grant($actor, $region);
        $this->grant($target, $region);

        return [$actor, $target, $grant];
    }

    private function state(User $target): array
    {
        return [$target->fresh()->getRawOriginal(), $target->fresh()->getRoleNames()->all(), AuditEvent::count()];
    }

    private function save($page, array $updates)
    {
        return $page->update(calls: [['method' => 'save', 'params' => [false], 'path' => '']], updates: collect($updates)->mapWithKeys(fn ($v, $k) => ['data.'.$k => $v])->all());
    }

    public function test_forbidden_credentials_email_and_security_fields_are_atomic(): void
    {
        [$actor, $target] = $this->pair(targetRole: AccountRole::SuperUser);
        foreach ([['password' => 'Isolated-secret-marker!'], ['email' => 'replacement@example.invalid'], ['roles' => ['Admin']], ['status' => 'inactive'], ['email_verified_at' => now()->addDay()->toDateTimeString()], ['is_synthetic' => true], ['region_id' => 999]] as $attack) {
            $before = $this->state($target);
            $page = Livewire::actingAs($actor->fresh())->test(EditUser::class, ['record' => $target->id]);
            try {
                $this->save($page, ['name' => 'Must not partially save', ...$attack])->assertForbidden();
            } catch (AssertionFailedError $e) {
                throw new AssertionFailedError('Denied field: '.array_key_first($attack).' '.$e->getMessage());
            }
            $this->assertTrue($before === $this->state($target), 'Denied save must leave the whole account and audit history unchanged.');
        }
    }

    public function test_authorized_delegated_credentials_are_hashed_and_responses_redacted(): void
    {
        foreach ([[AccountRole::Admin, AccountRole::Osds], [AccountRole::SuperUser, AccountRole::Admin]] as [$role, $targetRole]) {
            [$actor, $target] = $this->pair($role, $targetRole);
            $target->forceFill(['is_synthetic' => true])->saveQuietly();
            $oldHash = $target->password;
            $roles = $target->getRoleNames()->all();
            $page = Livewire::actingAs($actor)->test(EditUser::class, ['record' => $target->id]);
            $initial = json_encode($page->snapshot);
            $this->assertFalse(str_contains($initial, $oldHash), 'Initial snapshot must omit credential hash.');
            $page->update(calls: [['method' => 'save', 'params' => [false], 'path' => '']], updates: ['data' => array_replace($page->get('data'), ['name' => 'Managed profile', 'email' => 'changed-'.$target->id.'@example.invalid', 'password' => 'Isolated-secret-marker!'])])->assertHasNoFormErrors();
            $target->refresh();
            $this->assertTrue(Hash::check('Isolated-secret-marker!', $target->password));
            $this->assertSame('changed-'.$target->id.'@example.invalid', $target->email);
            $this->assertSame($roles, $target->getRoleNames()->all());
            $this->assertSame('active', $target->status);
            $payload = json_encode([$page->snapshot, $page->effects]);
            foreach (['Isolated-secret-marker!', $target->password, $oldHash] as $secret) {
                $this->assertFalse(str_contains($payload, $secret), 'Response must not serialize credentials.');
            }
            $audit = AuditEvent::where('resource_id', (string) $target->id)->get()->toJson();
            $this->assertFalse(str_contains($audit, 'Isolated-secret-marker!'));
            $this->assertFalse(str_contains($audit, $target->password));
        }
    }

    public function test_profile_edits_and_self_edits_do_not_change_security_fields(): void
    {
        [$actor, $target] = $this->pair(targetRole: AccountRole::SuperUser);
        foreach ([$target, $actor] as $edited) {
            $before = $edited->getRawOriginal();
            $page = Livewire::actingAs($actor->fresh())->test(EditUser::class, ['record' => $edited->id]);
            $this->save($page, ['name' => 'Only permitted name'])->assertHasNoFormErrors();
            foreach (['password', 'email', 'status', 'remember_token', 'email_verified_at'] as $field) {
                $this->assertTrue($before[$field] === $edited->fresh()->getRawOriginal($field), 'Profile edit changed a security field.');
            }
        }
        foreach (['password' => 'Self-reset-marker!', 'email' => 'self-change@example.invalid'] as $field => $value) {
            $before = $this->state($actor);
            $page = Livewire::actingAs($actor->fresh())->test(EditUser::class, ['record' => $actor->id]);
            $this->save($page, [$field => $value])->assertForbidden();
            $this->assertTrue($before === $this->state($actor));
        }
    }

    public function test_initial_and_mounted_editors_enforce_current_authority(): void
    {
        foreach (['expired', 'future', 'revoked', 'cross_region', 'inactive', 'permissionless'] as $state) {
            [$actor, $target, $grant] = $this->pair();
            if ($state === 'expired') {
                $grant->update(['ends_at' => now()->subSecond()]);
            }
            if ($state === 'future') {
                $grant->update(['starts_at' => now()->addDay()]);
            }
            if ($state === 'revoked') {
                $grant->update(['revoked_at' => now()]);
            }
            if ($state === 'cross_region') {
                $grant->update(['region_id' => Region::create(['code' => 'OTHER', 'name' => 'Other'])->id]);
            }
            if ($state === 'inactive') {
                $actor->update(['status' => 'inactive']);
            }
            if ($state === 'permissionless') {
                $actor->removeRole(AccountRole::Admin->value);
            }
            $before = $this->state($target);
            $response = $this->actingAs($actor->fresh())->get('/admin/users/'.$target->id.'/edit');
            $this->assertTrue(in_array($response->status(), [302, 403, 404], true));
            $this->assertTrue($before === $this->state($target));
        }
        foreach (['permission', 'scope', 'target_role', 'inactive'] as $change) {
            [$actor, $target, $grant] = $this->pair();
            $page = Livewire::actingAs($actor->fresh())->test(EditUser::class, ['record' => $target->id]);
            if ($change === 'permission') {
                $remaining = $actor->getAllPermissions()->pluck('name')->reject(fn ($name) => $name === 'users.manage')->all();
                $actor->syncRoles([]);
                $actor->syncPermissions($remaining);
                $this->assertTrue($actor->fresh()->can('users.view'));
                $this->assertFalse($actor->fresh()->can('users.manage'));
            }
            if ($change === 'scope') {
                $grant->update(['revoked_at' => now()]);
            }
            if ($change === 'target_role') {
                $target->syncRoles([AccountRole::SuperUser->value]);
            }
            if ($change === 'inactive') {
                $actor->update(['status' => 'inactive']);
            }
            $before = $this->state($target);
            $response = $this->actingAs($actor->fresh())->postJson(app('livewire')->getUpdateUri(), ['components' => [['snapshot' => json_encode($page->snapshot), 'updates' => ['data.email' => 'forged@example.invalid', 'data.password' => 'Must-not-save!'], 'calls' => [['method' => 'save', 'params' => []]]]]], ['X-Livewire' => 'true']);
            $this->assertContains($response->status(), [403, 404], 'Revoked authority must fail closed.');
            $this->assertFalse(str_contains($response->getContent(), 'Must-not-save!'));
            $this->assertTrue($before === $this->state($target));
            if ($change !== 'target_role') {
                foreach (['getRecord', 'refreshFormData'] as $method) {
                    $response = $this->actingAs($actor->fresh())->postJson(app('livewire')->getUpdateUri(), ['components' => [['snapshot' => json_encode($page->snapshot), 'updates' => (object) [], 'calls' => [['method' => $method, 'params' => $method === 'refreshFormData' ? [['password', 'remember_token']] : []]]]]], ['X-Livewire' => 'true']);
                    $this->assertContains($response->status(), [403, 404]);
                    $this->assertFalse(str_contains($response->getContent(), $target->email));
                }
            }

        }
    }

    public function test_validation_and_transport_do_not_expose_passwords(): void
    {
        [$actor, $target] = $this->pair();
        $page = Livewire::actingAs($actor)->test(EditUser::class, ['record' => $target->id]);
        $before = $this->state($target);
        $this->save($page, ['name' => '', 'password' => 'Validation-secret-marker!'])->assertHasFormErrors(['name']);
        $this->assertTrue($before === $this->state($target));
        $this->assertFalse(str_contains(json_encode([$page->snapshot, $page->effects]), 'Validation-secret-marker!'));
        $create = Livewire::actingAs($actor)->test(CreateUser::class);
        $create->update(calls: [['method' => 'create', 'params' => [], 'path' => '']], updates: ['data.password' => 'Creation-secret-marker!'])->assertHasFormErrors();
        $this->assertFalse(str_contains(json_encode([$create->snapshot, $create->effects]), 'Creation-secret-marker!'));
        $snapshot = $page->snapshot;
        $actor->removeRole(AccountRole::Admin->value);
        $response = $this->actingAs($actor->fresh())->postJson(app('livewire')->getUpdateUri(), ['components' => [['snapshot' => json_encode($snapshot), 'updates' => ['data.password' => 'Denied-secret-marker!'], 'calls' => [['method' => 'save', 'params' => []]]]]], ['X-Livewire' => 'true'])->assertNotFound();
        $this->assertFalse(str_contains($response->getContent(), 'Denied-secret-marker!'));
        $this->assertFalse(str_contains($response->getContent(), $target->password));
    }

    public function test_public_record_helpers_and_unexpected_failures_do_not_disclose_secrets(): void
    {
        [$actor, $target] = $this->pair();
        $target->forceFill(['remember_token' => 'Isolated-recovery-secret'])->saveQuietly();
        $page = Livewire::actingAs($actor)->test(EditUser::class, ['record' => $target->id]);
        $page->call('getRecord');
        $page->call('refreshFormData', ['password', 'remember_token']);
        $payload = json_encode([$page->snapshot, $page->effects]);
        $this->assertFalse(str_contains($payload, $target->password));
        $this->assertFalse(str_contains($payload, 'Isolated-recovery-secret'));

        $before = $this->state($target);
        $service = \Mockery::mock(AccountGovernance::class)->makePartial();
        $service->shouldReceive('updateProfile')->once()->andThrow(new \RuntimeException('Private credential diagnostic marker'));
        $this->app->instance(AccountGovernance::class, $service);
        Log::shouldReceive('error')->once()->withArgs(fn ($message, $context) => $message === 'Account save failed'
            && array_keys($context) === ['support_reference', 'exception_class']
            && $context['exception_class'] === \RuntimeException::class);
        $this->save($page, ['name' => 'Not persisted', 'password' => 'Failure-secret-marker!'])->assertHasFormErrors(['name']);
        $this->assertTrue($before === $this->state($target));
        $payload = json_encode([$page->snapshot, $page->effects]);
        $this->assertFalse(str_contains($payload, 'Private credential diagnostic marker'));
        $this->assertFalse(str_contains($payload, 'Failure-secret-marker!'));
    }
}
