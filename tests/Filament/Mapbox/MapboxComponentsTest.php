<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Tests\Filament\Mapbox;

use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationPicker;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxLocationViewer;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsDrawer;
use HiTaqnia\Haykal\Filament\Mapbox\Components\MapboxPolygonsViewer;
use HiTaqnia\Haykal\Tests\Filament\FilamentTestCase;

final class MapboxComponentsTest extends FilamentTestCase
{
    public function test_location_picker_and_polygons_drawer_are_filament_form_fields(): void
    {
        $this->assertInstanceOf(Field::class, MapboxLocationPicker::make('a'));
        $this->assertInstanceOf(Field::class, MapboxPolygonsDrawer::make('b'));
    }

    public function test_location_viewer_and_polygons_viewer_are_filament_infolist_entries(): void
    {
        $this->assertInstanceOf(Entry::class, MapboxLocationViewer::make('a'));
        $this->assertInstanceOf(Entry::class, MapboxPolygonsViewer::make('b'));
    }

    public function test_each_component_resolves_the_expected_package_view(): void
    {
        $expected = [
            MapboxLocationPicker::class => 'haykal-filament::mapbox.components.mapbox-location-picker',
            MapboxLocationViewer::class => 'haykal-filament::mapbox.components.mapbox-location-viewer',
            MapboxPolygonsDrawer::class => 'haykal-filament::mapbox.components.mapbox-polygons-drawer',
            MapboxPolygonsViewer::class => 'haykal-filament::mapbox.components.mapbox-polygons-viewer',
        ];

        foreach ($expected as $class => $view) {
            $component = $class::make('x');

            // `getView()` is Filament's public accessor for the protected $view.
            $this->assertSame($view, $component->getView(), $class);
            $this->assertTrue(view()->exists($view), "View {$view} should resolve for {$class}.");
        }
    }

    public function test_filament_asset_registry_has_mapbox_css_entries(): void
    {
        $cssNames = collect(FilamentAsset::getStyles())
            ->map(fn (Css $css) => $css->getId())
            ->all();

        $this->assertContains('mapbox', $cssNames);
        $this->assertContains('mapbox-draw', $cssNames);
    }

    public function test_filament_asset_registry_has_all_four_mapbox_alpine_components(): void
    {
        $alpine = collect(FilamentAsset::getAlpineComponents())
            ->map(fn (AlpineComponent $asset) => $asset->getId())
            ->all();

        $this->assertContains('mapbox-location-picker', $alpine);
        $this->assertContains('mapbox-location-viewer', $alpine);
        $this->assertContains('mapbox-polygons-drawer', $alpine);
        $this->assertContains('mapbox-polygons-viewer', $alpine);
    }

    public function test_mapbox_asset_dist_files_exist_on_disk(): void
    {
        $dist = __DIR__.'/../../../packages/haykal-filament/resources/js/mapbox/dist';

        $this->assertFileExists($dist.'/mapbox-location-picker.js');
        $this->assertFileExists($dist.'/mapbox-location-viewer.js');
        $this->assertFileExists($dist.'/mapbox-polygons-drawer.js');
        $this->assertFileExists($dist.'/mapbox-polygons-viewer.js');
    }
}
