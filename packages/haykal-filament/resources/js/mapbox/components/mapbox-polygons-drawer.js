import { defaultMapboxConfig, mergeConfig, initMapbox, fitToFeatures } from './mapbox.js';
import MapboxDraw from '@mapbox/mapbox-gl-draw';

export default function mapboxPolygonsDrawer({ statePath, config }) {
    return {
        map: null,
        draw: null,
        config: mergeConfig(defaultMapboxConfig, config),

        init() {
            if (this.config.maxPolygons === -1) this.config.maxPolygons = Infinity;

            this.map = initMapbox(this.config);

            this.draw = new MapboxDraw({
                displayControlsDefault: false,
                controls: { polygon: true, trash: true },
                defaultMode: 'draw_polygon',
                styles: [
                    {
                        'id': 'gl-draw-polygon-fill-inactive',
                        'type': 'fill',
                        'filter': ['all', ['==', 'active', 'false'],
                            ['==', '$type', 'Polygon'],
                            ['!=', 'mode', 'static']
                        ],
                        'paint': {
                            'fill-color': '#818cf8',
                            'fill-outline-color': '#818cf8',
                            'fill-opacity': 0.3
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-fill-active',
                        'type': 'fill',
                        'filter': ['all', ['==', 'active', 'true'],
                            ['==', '$type', 'Polygon']
                        ],
                        'paint': {
                            'fill-color': '#818cf8',
                            'fill-outline-color': '#818cf8',
                            'fill-opacity': 0.1
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-midpoint',
                        'type': 'circle',
                        'filter': ['all', ['==', '$type', 'Point'],
                            ['==', 'meta', 'midpoint']
                        ],
                        'paint': {
                            'circle-radius': 3,
                            'circle-color': '#1d4ed8'
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-stroke-inactive',
                        'type': 'line',
                        'filter': ['all', ['==', 'active', 'false'],
                            ['==', '$type', 'Polygon'],
                            ['!=', 'mode', 'static']
                        ],
                        'layout': {
                            'line-cap': 'round',
                            'line-join': 'round'
                        },
                        'paint': {
                            'line-color': '#3bb2d0',
                            'line-width': 2
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-stroke-active',
                        'type': 'line',
                        'filter': ['all', ['==', 'active', 'true'],
                            ['==', '$type', 'Polygon']
                        ],
                        'layout': {
                            'line-cap': 'round',
                            'line-join': 'round'
                        },
                        'paint': {
                            'line-color': '#1d4ed8',
                            'line-dasharray': [0.2, 2],
                            'line-width': 2
                        }
                    },
                    {
                        'id': 'gl-draw-line-inactive',
                        'type': 'line',
                        'filter': ['all', ['==', 'active', 'false'],
                            ['==', '$type', 'LineString'],
                            ['!=', 'mode', 'static']
                        ],
                        'layout': {
                            'line-cap': 'round',
                            'line-join': 'round'
                        },
                        'paint': {
                            'line-color': '#3bb2d0',
                            'line-width': 2
                        }
                    },
                    {
                        'id': 'gl-draw-line-active',
                        'type': 'line',
                        'filter': ['all', ['==', '$type', 'LineString'],
                            ['==', 'active', 'true']
                        ],
                        'layout': {
                            'line-cap': 'round',
                            'line-join': 'round'
                        },
                        'paint': {
                            'line-color': '#1d4ed8',
                            'line-dasharray': [0.2, 2],
                            'line-width': 2
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-and-line-vertex-stroke-inactive',
                        'type': 'circle',
                        'filter': ['all', ['==', 'meta', 'vertex'],
                            ['==', '$type', 'Point'],
                            ['!=', 'mode', 'static']
                        ],
                        'paint': {
                            'circle-radius': 5,
                            'circle-color': '#fff'
                        }
                    },
                    {
                        'id': 'gl-draw-polygon-and-line-vertex-inactive',
                        'type': 'circle',
                        'filter': ['all', ['==', 'meta', 'vertex'],
                            ['==', '$type', 'Point'],
                            ['!=', 'mode', 'static']
                        ],
                        'paint': {
                            'circle-radius': 3,
                            'circle-color': '#3730a3'
                        }
                    },
                    {
                        'id': 'gl-draw-point-point-stroke-inactive',
                        'type': 'circle',
                        'filter': ['all', ['==', 'active', 'false'],
                            ['==', '$type', 'Point'],
                            ['==', 'meta', 'feature'],
                            ['!=', 'mode', 'static']
                        ],
                        'paint': {
                            'circle-radius': 5,
                            'circle-opacity': 1,
                            'circle-color': '#fff'
                        }
                    },
                    {
                        'id': 'gl-draw-point-inactive',
                        'type': 'circle',
                        'filter': ['all', ['==', 'active', 'false'],
                            ['==', '$type', 'Point'],
                            ['==', 'meta', 'feature'],
                            ['!=', 'mode', 'static']
                        ],
                        'paint': {
                            'circle-radius': 3,
                            'circle-color': '#3bb2d0'
                        }
                    },
                    {
                        'id': 'gl-draw-point-stroke-active',
                        'type': 'circle',
                        'filter': ['all', ['==', '$type', 'Point'],
                            ['==', 'active', 'true'],
                            ['!=', 'meta', 'midpoint']
                        ],
                        'paint': {
                            'circle-radius': 7,
                            'circle-color': '#fff'
                        }
                    },
                    {
                        'id': 'gl-draw-point-active',
                        'type': 'circle',
                        'filter': ['all', ['==', '$type', 'Point'],
                            ['!=', 'meta', 'midpoint'],
                            ['==', 'active', 'true']
                        ],
                        'paint': {
                            'circle-radius': 5,
                            'circle-color': '#1d4ed8'
                        }
                    },
                ]
            });

            this.map.addControl(this.draw);

            // Preload saved polygons
            this.load(this.$wire.get(statePath));

            const sync = () => this.$wire.set(statePath, this.draw.getAll());

            const onCreate = (e) => {
                const all = this.draw.getAll();
                const polyIds = all.features
                    .filter(f => f.geometry?.type === 'Polygon' || f.geometry?.type === 'MultiPolygon')
                    .map(f => f.id);

                if (polyIds.length > this.config.maxPolygons) {
                    const createdIds = (e?.features ?? []).map(f => f.id);
                    this.draw.delete(createdIds.length ? createdIds : polyIds.slice(this.config.maxPolygons));

                    this.draw.changeMode('simple_select');
                }

                sync();
            };

            this.map.on('draw.create', onCreate);
            this.map.on('draw.delete', sync);
            this.map.on('draw.update', sync);
        },

        load(fc) {
            if (!fc || fc.type !== 'FeatureCollection') return;

            let features = (fc.features ?? []).filter(f =>
                f?.geometry?.type === 'Polygon' || f?.geometry?.type === 'MultiPolygon'
            );

            if (Number.isFinite(this.config.maxPolygons) && features.length > this.config.maxPolygons) {
                features = features.slice(0, this.config.maxPolygons);
            }

            if (features.length === 0) return;

            this.draw.changeMode('simple_select');
            this.draw.add({ type: 'FeatureCollection', features });
            this.$wire.set(statePath, this.draw.getAll());

            fitToFeatures(this.map, features);
        },
    }
}
