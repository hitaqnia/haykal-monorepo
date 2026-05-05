<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Mapbox\Concerns;

use Closure;

/**
 * Fluent configuration for every Haykal Mapbox component.
 *
 * Consolidates the shared knobs — container id, height, base style,
 * initial center/zoom, navigation control — so the four concrete
 * components (`MapboxLocationPicker`, `MapboxLocationViewer`,
 * `MapboxPolygonsDrawer`, `MapboxPolygonsViewer`) share identical
 * fluent API. `getMapboxConfig()` and `getMapboxJsonConfig()` assemble
 * the payload passed to the Alpine component as initial config.
 *
 * `mapHeight`, `mapCenter`, `mapZoom`, and `navigationControl` accept
 * either a static value or a Filament-style closure. Closures receive
 * the same named/typed injections as any other Filament component
 * closure (`$state`, `$record`, `$get`, `$livewire`, etc.) and are
 * resolved on every render — so a map's center can react to another
 * field via `live()` + `->mapCenter(fn (Get $get) => ...)`.
 */
trait InteractsWithMapbox
{
    /**
     * The Mapbox map container ID.
     */
    protected ?string $mapContainer = null;

    /**
     * The height of the map container in pixels.
     *
     * @var int|Closure
     */
    protected $mapHeight = 400;

    /**
     * Default map style.
     */
    protected ?string $mapStyle = null;

    /**
     * The initial map center coordinates. Only applied on first render,
     * when the component has no saved state to fit to.
     *
     * @var array{0: float|int, 1: float|int}|Closure
     */
    protected $mapCenter = [-74.5, 40];

    /**
     * Default map zoom level.
     *
     * @var int|Closure
     */
    protected $mapZoom = 9;

    /**
     * Whether to show the navigation controls (zoom and rotation).
     *
     * @var bool|Closure
     */
    protected $navigationControl = false;

    public function mapContainer(string $id): static
    {
        $this->mapContainer = $id;

        return $this;
    }

    public function getMapContainer(): ?string
    {
        return $this->mapContainer ?? $this->getName();
    }

    /**
     * @param  int|Closure  $height
     */
    public function mapHeight($height): static
    {
        $this->mapHeight = $height;

        return $this;
    }

    public function getMapHeight(): int
    {
        return (int) $this->evaluate($this->mapHeight);
    }

    public function mapStyle(string $style): static
    {
        $this->mapStyle = $style;

        return $this;
    }

    public function getMapStyle(): ?string
    {
        return $this->mapStyle;
    }

    /**
     * @param  array{0: float|int, 1: float|int}|Closure  $center
     */
    public function mapCenter($center): static
    {
        $this->mapCenter = $center;

        return $this;
    }

    /**
     * @return array{0: float|int, 1: float|int}
     */
    public function getMapCenter(): array
    {
        return $this->evaluate($this->mapCenter);
    }

    /**
     * @param  int|Closure  $zoom
     */
    public function mapZoom($zoom): static
    {
        $this->mapZoom = $zoom;

        return $this;
    }

    public function getMapZoom(): int
    {
        return (int) $this->evaluate($this->mapZoom);
    }

    /**
     * @param  bool|Closure  $control
     */
    public function navigationControl($control = true): static
    {
        $this->navigationControl = $control;

        return $this;
    }

    public function hasNavigationControl(): bool
    {
        return (bool) $this->evaluate($this->navigationControl);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMapboxConfig(): array
    {
        return [
            'token' => config('mapbox.token'),
            'map' => [
                'container' => $this->getMapContainer(),
                'style' => $this->getMapStyle(),
                'center' => $this->getMapCenter(),
                'zoom' => $this->getMapZoom(),
                'controls' => [
                    'navigation' => $this->hasNavigationControl(),
                ],
            ],
        ];
    }

    public function getMapboxJsonConfig(): string
    {
        return (string) json_encode($this->getMapboxConfig());
    }
}
