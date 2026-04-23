import { defaultMapboxConfig, mergeConfig, initMapbox, centerMap, createMarker } from './mapbox.js';

export default function mapboxLocationPicker({ statePath, config }) {
    return {
        map: null,
        marker: null,
        config: mergeConfig(defaultMapboxConfig, config),

        init() {
            this.map = initMapbox(this.config);

            const current = this.$wire.get(statePath);
            if (current && current.lng != null && current.lat != null) {
                this.addMarker(current.lng, current.lat, true, true);
            }

            this.map.on('click', (e) => {
                const { lng, lat } = e.lngLat;
                this.addMarker(lng, lat);
            });
        },

        addMarker(lng, lat, shouldCenter = false, animate = true) {
            if (!this.marker) {
                this.marker = createMarker({ lng, lat, draggable: true }).addTo(this.map);

                this.marker.on('dragend', () => {
                    const { lng, lat } = this.marker.getLngLat();
                    this.setCoords({ lng, lat });
                    centerMap(this.map, [lng, lat], { animate });
                });
            } else {
                this.marker.setLngLat([lng, lat]);
            }

            if (shouldCenter) centerMap(this.map, [lng, lat], { animate });
            this.setCoords({ lng, lat });
        },

        setCoords({ lng, lat }) {
            this.$wire.set(statePath, { lng, lat });
        }
    };
}
