<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Core\MediaLibrary;

use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Core\MediaLibrary\CustomPathGenerator;
use HiTaqnia\Haykal\Core\Tenancy\Tenancy;
use HiTaqnia\Haykal\Tests\Core\CoreTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DummyTenantModel extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'dummies';

    protected $guarded = [];
}

final class CustomPathGeneratorTest extends CoreTestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Tenancy::clear();
        parent::tearDown();
    }

    public function test_user_owned_media_is_filed_under_identity(): void
    {
        $user = User::create([
            'id' => '01HX0000000000000000000042',
            'name' => 'Test User',
            'phone' => '+9647701234567',
        ]);

        $media = new Media;
        $media->uuid = 'abc-123';
        $media->model_type = User::class;
        $media->model_id = $user->getKey();

        $path = (new CustomPathGenerator)->getPath($media);

        $this->assertSame('identity/abc-123/', $path);
    }

    public function test_non_user_media_is_filed_under_active_tenant(): void
    {
        Tenancy::setTenantId('tenant-a');

        $owner = new DummyTenantModel(['id' => 1]);
        $owner->exists = true;

        $media = new Media;
        $media->uuid = 'def-456';
        $media->setRelation('model', $owner);

        $path = (new CustomPathGenerator)->getPath($media);

        $this->assertSame('tenant-a/def-456/', $path);
    }

    public function test_non_user_media_without_an_active_tenant_throws(): void
    {
        $owner = new DummyTenantModel(['id' => 1]);
        $owner->exists = true;

        $media = new Media;
        $media->uuid = 'def-456';
        $media->setRelation('model', $owner);

        $this->expectException(RuntimeException::class);

        (new CustomPathGenerator)->getPath($media);
    }

    public function test_conversions_and_responsive_images_extend_the_base_path(): void
    {
        Tenancy::setTenantId('tenant-a');

        $owner = new DummyTenantModel(['id' => 1]);
        $owner->exists = true;

        $media = new Media;
        $media->uuid = 'def-456';
        $media->setRelation('model', $owner);

        $generator = new CustomPathGenerator;

        $this->assertSame('tenant-a/def-456/conversions/', $generator->getPathForConversions($media));
        $this->assertSame('tenant-a/def-456/responsive-images/', $generator->getPathForResponsiveImages($media));
    }
}
