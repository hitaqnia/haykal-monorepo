<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Models;

use HiTaqnia\Haykal\Core\Database\Factories\UserFactory;
use HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast;
use HiTaqnia\Haykal\Core\Identity\QueryBuilders\UserQueryBuilder;
use Huwiya\InteractsWithHuwiya;
use Huwiya\TokenClaims;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * Haykal default User model.
 *
 * Intended to be used directly by simple apps and extended by apps that
 * need extra relations or Filament-specific tenancy/panel logic (which
 * live in `haykal-filament`, not here).
 *
 * Authentication is delegated entirely to Huwiya — there is no local
 * password column, no Sanctum tokens, no MFA traits. The `huwiya_id`
 * column is populated on first login via `InteractsWithHuwiya`, and
 * the profile columns (name, phone, email, locale, zoneinfo, theme)
 * are synced from the token claims on every login.
 */
class User extends Authenticatable implements HasMedia
{
    use HasFactory;
    use HasRoles;
    use HasUlids;
    use InteractsWithHuwiya;
    use InteractsWithMedia;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'locale',
        'zoneinfo',
        'theme',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'phone' => PhoneNumberCast::class,
        ];
    }

    public function newEloquentBuilder($query): UserQueryBuilder
    {
        return new UserQueryBuilder($query);
    }

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    // -------------------------------------------------------------
    // Huwiya integration — sync profile data from claims on every
    // login. Apps that want a subset or different mapping should
    // override these on their subclass.
    // -------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaCreateAttributes(TokenClaims $claims): array
    {
        return $this->attributesFromClaims($claims);
    }

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaUpdateAttributes(TokenClaims $claims): array
    {
        return $this->attributesFromClaims($claims);
    }

    /**
     * Columns synced from Huwiya claims. Sync applies identically on
     * create and on every re-login so the app's copy stays in step
     * with the IdP.
     *
     * @return array<string, mixed>
     */
    protected function attributesFromClaims(TokenClaims $claims): array
    {
        return [
            'name' => $claims->name,
            'phone' => $claims->phone,
            'email' => $claims->email,
            'locale' => $claims->locale,
            'zoneinfo' => $claims->zoneinfo,
            'theme' => $claims->theme,
        ];
    }
}
