<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Mapbox\Components;

use Filament\Forms\Components\Field;
use HiTaqnia\Haykal\Filament\Mapbox\Concerns\InteractsWithMapbox;

/**
 * Form field that captures a `{lng, lat}` pair via a draggable marker on a Mapbox map.
 */
class MapboxLocationPicker extends Field
{
    use InteractsWithMapbox;

    protected string $view = 'haykal-filament::mapbox.components.mapbox-location-picker';
}
