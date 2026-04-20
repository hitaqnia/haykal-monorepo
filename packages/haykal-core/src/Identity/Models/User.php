<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\Identity\Models;

use HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast;
use HiTaqnia\Haykal\Core\Identity\QueryBuilders\UserQueryBuilder;
use Huwiya\InteractsWithHuwiya;
use Huwiya\TokenClaims;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
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
 * column is populated on first login via `InteractsWithHuwiya`.
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

    public const AVATARS_COLLECTION = 'user-avatars';

    protected $fillable = [
        'name',
        'phone',
        'email',
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

    // -------------------------------------------------------------
    // Huwiya integration — sync basic profile data from claims.
    // Apps override these to sync more (or fewer) fields.
    // -------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaCreateAttributes(TokenClaims $claims): array
    {
        return [
            'name' => $claims->name,
            'phone' => $claims->phone,
            'email' => $claims->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHuwiyaUpdateAttributes(TokenClaims $claims): array
    {
        return [
            'name' => $claims->name,
            'phone' => $claims->phone,
            'email' => $claims->email,
        ];
    }

    // -------------------------------------------------------------
    // Media: avatar collection (single file).
    // -------------------------------------------------------------

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(static::AVATARS_COLLECTION)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg'])
            ->singleFile();
    }

    protected function avatar(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getAvatarUrl(),
        );
    }

    public function getAvatarUrl(?int $ttlMinutes = 15): ?string
    {
        $media = $this->getFirstMedia(static::AVATARS_COLLECTION);

        return $media?->getTemporaryUrl(now()->addMinutes($ttlMinutes));
    }
}
