<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'status', 'deactivated_at', 'deactivated_by'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $attributes = ['status' => 'active'];

    public function canAccessPanel(Panel $panel): bool
    {
        // Panel admission stays closed until Phase 2 grants are approved.
        return $this->status === 'active' && $this->can('platform.admin.access');
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->public_id ??= (string) Str::uuid();
        });
        static::updating(function (User $user): void {
            if (! $user->isDirty('status')) {
                return;
            }

            $disabled = in_array($user->status, ['suspended', 'inactive'], true);
            $user->deactivated_at = $disabled ? ($user->deactivated_at ?? now()) : null;
            $user->deactivated_by = $disabled ? ($user->deactivated_by ?? auth()->id()) : null;
        });
        static::updated(function (User $user): void {
            if ($user->status !== 'active' && $user->wasChanged('status')) {
                $user->invalidateSessions();
            }
        });
    }

    public function regionAssignments(): HasMany
    {
        return $this->hasMany(UserRegionAssignment::class);
    }

    public function representedInstitutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'institution_representatives')
            ->withPivot(['starts_at', 'ends_at', 'revoked_at', 'verified_at', 'verified_by'])->withTimestamps();
    }

    public function nationwideAccessGrants(): HasMany
    {
        return $this->hasMany(NationwideAccessGrant::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(AccountStatusEvent::class);
    }

    public function invalidateSessions(): void
    {
        DB::table('sessions')->where('user_id', $this->id)->delete();
        $this->forceFill(['remember_token' => null])->saveQuietly();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deactivated_at === null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deactivated_at' => 'immutable_datetime',
        ];
    }
}
