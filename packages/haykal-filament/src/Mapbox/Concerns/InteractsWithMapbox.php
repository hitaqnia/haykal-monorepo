<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Filament\Mapbox\Concerns;

/**
 * Fluent configuration for every Haykal Mapbox component.
 *
 * Consolidates the shared knobs — container id, height, base style,
 * initial center/zoom, navigation control — so the four concrete
 * components (`MapboxLocationPicker`, `MapboxLocationViewer`,
 * `MapboxPolygonsDrawer`, `MapboxPolygonsViewer`) share identical
 * fluent API. `getMapboxConfig()` and `getMapboxJsonConfig()` assemble
 * the payload passed to the Alpine component as initial config.
 */
trait InteractsWithMapbox
{
    /**
     * The Mapbox map container ID.
     */
    protected ?string $mapContainer = null;

    /**
     * The height of the map container in pixels.
     */
    protected int $mapHeight = 400;

    /**
     * Default map style.
     */
    protected ?string $mapStyle = null;

    /**
     * The initial map center coordinates. Only applied on first render,
     * when the component has no saved state to fit to.
     *
     * @var array{0: float|int, 1: float|int}
     */
    protected array $mapCenter = [-74.5, 40];

    /**
     * Default map zoom level.
     */
    protected int $mapZoom = 9;

    /**
     * Whether to show the navigation controls (zoom and rotation).
     */
    protected bool $navigationControl = false;

    public function mapContainer(string $id): static
    {
        $this->mapContainer = $id;

        return $this;
    }

    public function getMapContainer(): ?string
    {
        return $this->mapContainer ?? $this->getName();
    }

    public function mapHeight(int $height): static
    {
        $this->mapHeight = $height;

        return $this;
    }

    public function getMapHeight(): int
    {
        return $this->mapHeight;
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
     * @param  array{0: float|int, 1: float|int}  $center
     */
    public function mapCenter(array $center): static
    {
        $this->mapCenter = $center;

        return $this;
    }

    /**
     * @return array{0: float|int, 1: float|int}
     */
    public function getMapCenter(): array
    {
        return $this->mapCenter;
    }

    public function mapZoom(int $zoom): static
    {
        $this->mapZoom = $zoom;

        return $this;
    }

    public function getMapZoom(): int
    {
        return $this->mapZoom;
    }

    public function navigationControl(bool $control = true): static
    {
        $this->navigationControl = $control;

        return $this;
    }

    public function hasNavigationControl(): bool
    {
        return $this->navigationControl;
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
