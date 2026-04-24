<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Models;

use HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast;
use HiTaqnia\Haykal\Core\Identity\QueryBuilders\UserQueryBuilder;
use Huwiya\InteractsWithHuwiya;
use Huwiya\TokenClaims;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * Abstract base User model for Huwiya-authenticated Haykal applications.
 *
 * Extends Laravel's `Authenticatable` and fills in the team's defaults for
 * every override point that the Huwiya SDK's `InteractsWithHuwiya` trait
 * exposes. Concrete applications subclass this, add their own relations /
 * observers / scopes / factory, and otherwise inherit the full HiTaqnia
 * standard: ULID primary keys, soft deletes, Spatie role bindings,
 * Spatie media attachments, and the phone-number cast.
 *
 * Authentication itself is delegated to Huwiya — there is no local password
 * column, no Sanctum tokens, no MFA traits. The `huwiya_id` column is
 * populated on first OAuth login and the six profile columns (`name`,
 * `phone`, `email`, `locale`, `zoneinfo`, `theme`) are synced from the
 * token claims on every login.
 *
 * ### What this class fills in
 *
 * | SDK hook                         | Team default here                                                       |
 * |----------------------------------|-------------------------------------------------------------------------|
 * | `getHuwiyaCreateAttributes`      | Maps the six profile claims to columns of the same name.                |
 * | `getHuwiyaUpdateAttributes`      | Same map — columns stay in step with the IdP on every re-login.         |
 * | `$fillable`                      | The six profile columns (`huwiya_id` is added by `InteractsWithHuwiya`).|
 * | `$casts['phone']`                | `PhoneNumberCast` (Iraqi E.164).                                        |
 * | `newEloquentBuilder()`           | `UserQueryBuilder` for `wherePhoneNumber()` / `getByPhoneNumber()`.     |
 *
 * Hooks left at the SDK's default (`shouldAutoRegister`, `resolveHuwiyaConflict`,
 * etc.) should be overridden on the concrete subclass when the application
 * needs a non-default policy (invite-only registration, phone/email
 * recycling, etc.).
 *
 * ### Future auth modes
 *
 * When the team ships a non-Huwiya base (e.g., `BasePasswordUser`), it lives
 * alongside this class — projects pick the base whose auth story matches.
 * This class is the Huwiya-default branch and must not be used directly;
 * always subclass it.
 */
abstract class BaseHuwiyaUser extends Authenticatable implements HasMedia
{
    use HasRoles;
    use HasUlids;
    use InteractsWithHuwiya;
    use InteractsWithMedia;
    use Notifiable;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'locale',
        'zoneinfo',
        'theme',
    ];

    /** @var list<string> */
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

    // -------------------------------------------------------------
    // Huwiya integration — team defaults for claim synchronization.
    // Override on your subclass to sync a different column set.
    // -------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaCreateAttributes(TokenClaims $claims): array
    {
        return $this->attributesFromHuwiyaClaims($claims);
    }

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaUpdateAttributes(TokenClaims $claims): array
    {
        return $this->attributesFromHuwiyaClaims($claims);
    }

    /**
     * Columns synced from Huwiya claims. The sync runs identically on
     * create and on every re-login, keeping the application's copy of the
     * user in step with the IdP.
     *
     * @return array<string, mixed>
     */
    protected function attributesFromHuwiyaClaims(TokenClaims $claims): array
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
