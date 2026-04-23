import { defaultMapboxConfig, mergeConfig, initMapbox, centerMap, createMarker } from './mapbox.js';

export default function mapboxLocationViewer({ location, config }) {
    return {
        map: null,
        config: mergeConfig(defaultMapboxConfig, config),

        init() {
            this.map = initMapbox(this.config);

            createMarker({ lng: location.lng, lat: location.lat, draggable: false }).addTo(this.map);

            centerMap(this.map, location, { animate: true });
        }
    };
}
