<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use JoelButcher\Socialstream\HasConnectedAccounts;
use JoelButcher\Socialstream\SetsProfilePhotoFromUrl;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Liberu\Foundation\Identity\Socialstream\Contracts\ConnectedAccountOwner;
use Liberu\Foundation\Observability\Contracts\ObservabilityActor;
use Liberu\Foundation\Organizations\Contracts\OrganizationActor;
use Liberu\Foundation\Organizations\Models\Team;
use Liberu\Foundation\RolesPermissions\Contracts\PrivilegedActor;
use Liberu\Foundation\RolesPermissions\Services\AnyTeamRoleLookup;
use Liberu\Foundation\Search\Concerns\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string|null $theme_preference
 * @property string|null $locale
 */
class User extends Authenticatable implements ConnectedAccountOwner, FilamentUser, HasDefaultTenant, HasTenants, ObservabilityActor, OrganizationActor, PrivilegedActor
{
    use HasApiTokens;
    use HasConnectedAccounts;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto {
        HasProfilePhoto::profilePhotoUrl as getPhotoUrl;
    }
    use HasRoles, HasTeams {
        // Both traits define teams(): Jetstream = team membership (used by allTeams()
        // and Filament tenancy). Spatie's teams() (roles-derived) is excluded — Spatie
        // scopes via the team_id column + DefaultTeamResolver, not this relation.
        HasTeams::teams insteadof HasRoles;
    }
    use LogsActivity;
    use Notifiable;

    // The scope `SearchService` calls. It used to be declared here, which meant
    // the package could not be installed anywhere else without reimplementing it.
    use Searchable;
    use SetsProfilePhotoFromUrl;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'theme_preference',
        'locale',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        // PII: keep email off array/JSON serialization so public search endpoints
        // (nested post.user / group.owner) can't be used to harvest addresses.
        'email',
        'email_verified_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the URL to the user's profile photo.
     *
     * @return Attribute<string, never>
     */
    protected function profilePhotoUrl(): Attribute
    {
        return filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)
            ? Attribute::get(fn () => $this->profile_photo_path)
            : $this->getPhotoUrl();
    }

    /**
     * The teams this user may act within as a Filament tenant — owned + member teams,
     * consistent with canAccessTenant()/belongsToTeam() so invited members aren't locked out.
     *
     * @return array<int, Model>|Collection<int, Model>
     */
    public function getTenants(Panel $panel): array|Collection
    {
        return $this->allTeams();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof Team && $this->belongsToTeam($tenant);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAdminAccess();
        }

        return true;
    }

    /**
     * True if the user holds an admin role in ANY team. Spatie roles are
     * team-scoped, and the active team context is not reliably set when
     * canAccessPanel() runs, so check the pivot directly across all teams.
     */
    public function hasAdminAccess(): bool
    {
        return $this->hasRoleInAnyTeam([(string) config('filament-shield.super_admin.name', 'super_admin'), 'admin']);
    }

    /**
     * True if the user holds the super_admin role in ANY team. Team-agnostic
     * (unlike Spatie's team-scoped hasRole), so it drives the policy-bypass gate
     * reliably even when no team context is set on the request.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRoleInAnyTeam((string) config('filament-shield.super_admin.name', 'super_admin'));
    }

    /** The pivot columns AnyTeamRoleLookup matches this actor on. */
    public function authorizationIdentifier(): int|string
    {
        return $this->getKey();
    }

    public function authorizationType(): string
    {
        return $this->getMorphClass();
    }

    /**
     * Team-agnostic role check. Spatie's hasRole() is bound to the active team
     * context, which is unset on plain web requests and when canAccessPanel()
     * runs, so the pivot is queried directly across every team.
     *
     * The query itself moved into the authorization package in 1.0.4; the host
     * delegates rather than keeping its own copy.
     *
     * @param  string|list<string>  $roles
     */
    public function hasRoleInAnyTeam(string|array $roles): bool
    {
        return app(AnyTeamRoleLookup::class)->hasRoleInAnyTeam($this, $roles);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->latestTeam;
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function latestTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Only track safe profile fields — never password / 2FA / tokens.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'locale', 'theme_preference'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Admin = super_admin in any team, or an allowlisted email. Used to gate the
     * Telescope/Pulse dashboards.
     */
    public function isAdmin(): bool
    {
        return in_array($this->email, (array) config('app.admin_emails', []), true)
            || $this->isSuperAdmin();
    }
}
