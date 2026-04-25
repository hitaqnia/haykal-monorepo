<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Fixtures;

use HiTaqnia\Haykal\Core\Identity\Casts\PhoneNumberCast;
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
 * Test fixture mirroring the default Huwiya user shape that consuming
 * applications declare (see `Domain\Identity\Models\User` in the starter).
 *
 * Kept inside the test tree so the monorepo can exercise haykal-core's
 * utilities (`PhoneNumberCast`, tenancy) in combination with a realistic
 * model. Not part of the published package.
 */
final class TestHuwiyaUser extends Authenticatable implements HasMedia
{
    use HasFactory;
    use HasRoles;
    use HasUlids;
    use InteractsWithHuwiya;
    use InteractsWithMedia;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

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

    protected static function newFactory(): Factory
    {
        return TestHuwiyaUserFactory::new();
    }

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
