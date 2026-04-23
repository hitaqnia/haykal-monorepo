import { defaultMapboxConfig, mergeConfig, initMapbox, fitToFeatures } from './mapbox.js';

export default function mapboxPolygonsViewer({ featuresCollection, config }) {
    return {
        map: null,
        sourceId: 'readonly-polygons',
        fillLayerId: 'readonly-polygons-fill',
        lineLayerId: 'readonly-polygons-line',
        config: mergeConfig(defaultMapboxConfig, config),

        init() {
            this.map = initMapbox(this.config);
            this.map.on('load', () => this.load(featuresCollection));
        },

        load(fc) {
            if (!fc || fc.type !== 'FeatureCollection' || !(fc.features ?? []).length) return;

            if (!this.map.getSource(this.sourceId)) {
                this.map.addSource(this.sourceId, { type: 'geojson', data: fc });
            } else {
                this.map.getSource(this.sourceId).setData(fc);
            }

            if (!this.map.getLayer(this.fillLayerId)) {
                this.map.addLayer({
                    id: this.fillLayerId,
                    type: 'fill',
                    source: this.sourceId,
                    paint: {
                        'fill-color': '#3b82f6',
                        'fill-opacity': 0.25
                    },
                    filter: ['in', ['get', 'type'], ['literal', ['Feature']]] // show all features
                });
            }

            if (!this.map.getLayer(this.lineLayerId)) {
                this.map.addLayer({
                    id: this.lineLayerId,
                    type: 'line',
                    source: this.sourceId,
                    paint: {
                        'line-color': '#1d4ed8',
                        'line-width': 2
                    }
                });
            }

            // Fit to data
            fitToFeatures(this.map, fc.features);
        },
    }
}
