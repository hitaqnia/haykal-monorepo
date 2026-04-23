@php
    use Filament\Support\Facades\FilamentAsset;
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div
        x-load
        x-load-css="[
            @js(FilamentAsset::getStyleHref('mapbox')),
            @js(FilamentAsset::getStyleHref('mapbox-draw'))
        ]"
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('mapbox-polygons-viewer') }}"
        wire:ignore
        x-data="mapboxPolygonsViewer({
            featuresCollection: @js($getState()),
            config: {{ $getMapboxJsonConfig() }}
        })"
        x-ignore
        style="position: relative; height: {{ $getMapHeight() }}px;"
    >
        <div
            id="{{ $getMapContainer() }}"
            style="position: absolute; top: 0; bottom: 0; width: 100%;"
        ></div>
    </div>
</x-dynamic-component>
