<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Mapbox\Components;

use Filament\Forms\Components\Field;
use HiTaqnia\Haykal\Filament\Mapbox\Concerns\InteractsWithMapbox;

/**
 * Form field that captures a GeoJSON `FeatureCollection` of polygons drawn on a Mapbox map.
 *
 * Use `maxPolygons()` to cap the number of polygons a user may draw
 * before the drawer refuses to add more. `-1` (the default) is
 * treated as unlimited.
 */
class MapboxPolygonsDrawer extends Field
{
    use InteractsWithMapbox {
        InteractsWithMapbox::getMapboxConfig as protected baseGetMapboxConfig;
    }

    protected string $view = 'haykal-filament::mapbox.components.mapbox-polygons-drawer';

    protected int $maxPolygons = -1;

    public function maxPolygons(int $maxPolygons): static
    {
        $this->maxPolygons = $maxPolygons;

        return $this;
    }

    public function getMaxPolygons(): int
    {
        return $this->maxPolygons;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMapboxConfig(): array
    {
        $config = $this->baseGetMapboxConfig();

        $config['maxPolygons'] = $this->getMaxPolygons();

        return $config;
    }
}
