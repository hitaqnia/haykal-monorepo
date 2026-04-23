<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\ViewerJs;

use Filament\Infolists\Components\Entry;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use HiTaqnia\Haykal\Filament\ViewerJs\Components\ImageGallery;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;

final class ImageGalleryTest extends FilamentTestCase
{
    public function test_component_is_a_filament_infolist_entry(): void
    {
        $this->assertInstanceOf(Entry::class, ImageGallery::make('photos'));
    }

    public function test_component_resolves_the_package_view(): void
    {
        $component = ImageGallery::make('photos');

        $this->assertSame('haykal-filament::viewer-js.image-gallery', $component->getView());
        $this->assertTrue(view()->exists($component->getView()));
    }

    public function test_images_accepts_a_plain_array_and_returns_it(): void
    {
        $component = ImageGallery::make('photos')
            ->images([
                'floor_plan' => 'https://cdn.example.test/a.jpg',
                'elevation' => 'https://cdn.example.test/b.jpg',
            ]);

        $this->assertSame([
            'floor_plan' => 'https://cdn.example.test/a.jpg',
            'elevation' => 'https://cdn.example.test/b.jpg',
        ], $component->getImages());
    }

    public function test_images_accepts_a_closure_and_resolves_it_on_access(): void
    {
        $component = ImageGallery::make('photos')
            ->images(fn () => ['hero' => 'https://cdn.example.test/c.jpg']);

        $this->assertSame(['hero' => 'https://cdn.example.test/c.jpg'], $component->getImages());
    }

    public function test_images_defaults_to_empty_array(): void
    {
        $this->assertSame([], ImageGallery::make('photos')->getImages());
    }

    public function test_viewerjs_css_and_alpine_component_are_registered_with_filament(): void
    {
        $css = collect(FilamentAsset::getStyles())
            ->map(fn (Css $c) => $c->getId())
            ->all();

        $alpine = collect(FilamentAsset::getAlpineComponents())
            ->map(fn (AlpineComponent $c) => $c->getId())
            ->all();

        $this->assertContains('viewerjs', $css);
        $this->assertContains('image-gallery', $alpine);
    }

    public function test_image_gallery_dist_file_exists_on_disk(): void
    {
        $this->assertFileExists(
            __DIR__.'/../../../packages/haykal-filament/resources/js/viewer-js/dist/image-gallery.js',
        );
    }
}
