<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Mapbox\Components;

use Filament\Infolists\Components\Entry;
use HiTaqnia\Haykal\Filament\Mapbox\Concerns\InteractsWithMapbox;

/**
 * Read-only infolist entry that renders a stored GeoJSON `FeatureCollection` of polygons.
 */
class MapboxPolygonsViewer extends Entry
{
    use InteractsWithMapbox;

    protected string $view = 'haykal-filament::mapbox.components.mapbox-polygons-viewer';
}
