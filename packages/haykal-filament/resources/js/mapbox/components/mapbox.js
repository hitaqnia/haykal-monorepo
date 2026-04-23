import mapboxgl from 'mapbox-gl';

export const defaultMapboxConfig = {
    token: null,
    map: {
        container: null,
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [0, 0],
        zoom: 9,
        controls: {
            navigation: true,
        }
    },
    maxPolygons: Infinity,
};

export function mergeConfig(base, override = {}) {
    const out = structuredClone(base);
    const stack = [[out, override]];
    while (stack.length) {
        const [target, src] = stack.pop();
        for (const k of Object.keys(src ?? {})) {
            if (src[k] && typeof src[k] === 'object' && !Array.isArray(src[k])) {
                if (!target[k] || typeof target[k] !== 'object') target[k] = {};
                stack.push([target[k], src[k]]);
            } else {
                target[k] = src[k];
            }
        }
    }
    return out;
}

export function initMapbox(config) {
    if (!config?.token) throw new Error('Mapbox token is required');
    if (!config?.map?.container) throw new Error('Map container is required');

    mapboxgl.accessToken = config.token;

    const map = new mapboxgl.Map({
        container: config.map.container,
        style: config.map.style,
        center: config.map.center,
        zoom: config.map.zoom,
        attributionControl: false,
    });

    addControls(map, config.map.controls);

    return map;
}

export function addControls(map, controls = {}) {
    if (controls.navigation) map.addControl(new mapboxgl.NavigationControl());
}

export function centerMap(map, centerLngLat, { animate = true } = {}) {
    if (animate) map.flyTo({ center: centerLngLat, essential: true });
    else map.setCenter(centerLngLat);
}

export function createMarker({ lng, lat, draggable = false }) {
    return new mapboxgl.Marker({ draggable }).setLngLat([lng, lat]);
}

export function fitToFeatures(map, features) {
    let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;

    const eachCoord = (coords, type) => {
        if (type === 'Polygon') {
            coords.forEach(ring => ring.forEach(([x, y]) => {
                if (x < minX) minX = x; if (y < minY) minY = y;
                if (x > maxX) maxX = x; if (y > maxY) maxY = y;
            }));
        } else if (type === 'MultiPolygon') {
            coords.forEach(poly => poly.forEach(ring => ring.forEach(([x, y]) => {
                if (x < minX) minX = x; if (y < minY) minY = y;
                if (x > maxX) maxX = x; if (y > maxY) maxY = y;
            })));
        }
    };

    features.forEach(f => eachCoord(f.geometry.coordinates, f.geometry.type));

    if (isFinite(minX) && isFinite(minY) && isFinite(maxX) && isFinite(maxY)) {
        map.fitBounds([[minX, minY], [maxX, maxY]], { padding: 40, duration: 0 });
    }
}
