<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Core\MediaLibrary;

use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Tenant-aware path generator for Spatie Media Library.
 *
 * Media attached to the Haykal User model is filed under `identity/<uuid>/`
 * (users are global, not tenant-scoped). Everything else is filed under
 * `<tenantId>/<uuid>/`, pulling the tenant from the active `Tenancy` context.
 * Tests and background jobs that persist media outside a tenant context must
 * set a tenant first or switch to a different path generator.
 *
 * Apps that need additional special cases (e.g., their own non-tenant-scoped
 * models) can extend this generator and override `getBasePath()`.
 */
class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    protected function getBasePath(Media $media): string
    {
        $media->loadMissing('model');

        if ($media->model instanceof User) {
            return 'identity/'.$media->getAttribute('uuid');
        }

        $tenantId = Tenancy::getTenantId();

        if ($tenantId === null) {
            throw new RuntimeException(
                'A tenant must be active before storing or retrieving tenant-scoped media.',
            );
        }

        return $tenantId.'/'.$media->getAttribute('uuid');
    }
}
