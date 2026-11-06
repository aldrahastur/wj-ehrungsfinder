<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property bool $is_admin
 * @property-read OrganisationMembership $pivot  nur beim Laden über Organisation::users()
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public const ROLE_EDITOR = 'editor';

    public const ROLE_APPROVER = 'approver';

    /**
     * Fachliche Rollen je Regelpaket (Organisation).
     *
     * @return BelongsToMany<Organisation, $this, OrganisationMembership, 'pivot'>
     */
    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(Organisation::class)->using(OrganisationMembership::class)->withPivot('role')->withTimestamps();
    }

    public function roleFor(Organisation|int $organisation): ?string
    {
        $id = $organisation instanceof Organisation ? $organisation->id : $organisation;

        return $this->organisations->firstWhere('id', $id)?->pivot->role;
    }

    public function canAccessAdmin(): bool
    {
        return $this->is_admin || $this->organisations->isNotEmpty();
    }

    public function canEditHonor(Honor $honor): bool
    {
        return $this->is_admin || in_array($this->roleFor($honor->organisation_id), [self::ROLE_EDITOR, self::ROLE_APPROVER], true);
    }

    public function canApproveHonor(Honor $honor): bool
    {
        return $this->is_admin || $this->roleFor($honor->organisation_id) === self::ROLE_APPROVER;
    }
}
