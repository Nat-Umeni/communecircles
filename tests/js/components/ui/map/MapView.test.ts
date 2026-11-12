import { enableAutoUnmount, flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import MapView from '@/components/ui/map/MapView.vue';
import { useTheme } from '@/composables/ui/useTheme';
import type { MapMarker, MapPoint, MapTiles } from '@/types';

/**
 * Leaflet, recording what the map draws instead of drawing it (jsdom has no layout for it to draw into).
 */
const leaflet = vi.hoisted(() => {
    const drawn = {
        map: { setView: vi.fn(), remove: vi.fn(), on: vi.fn() },
        tiles: { addTo: vi.fn(), remove: vi.fn() },
        pins: { addTo: vi.fn(), addLayer: vi.fn(), clearLayers: vi.fn() },
    };
    drawn.pins.addTo.mockReturnValue(drawn.pins);
    drawn.tiles.addTo.mockReturnValue(drawn.tiles);

    return {
        drawn,
        map: vi.fn(() => drawn.map),
        tileLayer: vi.fn(() => drawn.tiles),
        layerGroup: vi.fn(() => drawn.pins),
        marker: vi.fn((at: [number, number], options: object) => ({ at, options })),
        divIcon: vi.fn((options: object) => ({ divIcon: options })),
    };
});

vi.mock('leaflet', () => leaflet);

const tiles: MapTiles = {
    light: { url: 'https://tiles.example.com/light/{z}/{x}/{y}.png', attribution: '&copy; Example Maps', max_zoom: 17 },
    dark: { url: 'https://tiles.example.com/dark/{z}/{x}/{y}{r}.png', attribution: '&copy; Example Dark Maps', max_zoom: 20 },
};

const { setTheme } = useTheme();

// The theme is shared, so a map left from an earlier test would follow it too.
enableAutoUnmount(afterEach);

const totnes: MapPoint = { latitude: 50.43107, longitude: -3.6843 };

const stroud: MapPoint = { latitude: 51.75, longitude: -2.2 };

/** The accent dot every point is marked with. */
const dot = { divIcon: { className: expect.stringContaining('bg-accent'), iconSize: [16, 16] } };

function mapView(props: Partial<{ center: MapPoint; zoom: number; markers: MapMarker[]; options: object }> = {}): VueWrapper {
    return mount(MapView, {
        props: { center: totnes, zoom: 11, label: 'Where the seed exchange is', tiles, ...props },
        attrs: { class: 'min-h-64' },
    });
}

describe('MapView', () => {
    afterEach(() => {
        setTheme('system');
    });

    it('is a region named for what it shows, kept under the page around it, at the size the page gives it', () => {
        const region = mapView().get('[role="region"]');

        expect(region.attributes('aria-label')).toBe('Where the seed exchange is');
        expect(region.classes()).toEqual(expect.arrayContaining(['isolate', 'min-h-64']));
    });

    it('draws the map on its own element once Leaflet has loaded, where and as close as it is told, with the other options passed on', async () => {
        const wrapper = mapView({ options: { scrollWheelZoom: false } });

        expect(leaflet.map).not.toHaveBeenCalled();

        await flushPromises();

        expect(leaflet.map).toHaveBeenCalledExactlyOnceWith(wrapper.get('[role="region"] > div').element, {
            scrollWheelZoom: false,
            center: [50.43107, -3.6843],
            zoom: 11,
        });
    });

    it('shows the light tiles it is given on a light page, with their credit and how far they zoom', async () => {
        mapView();
        await flushPromises();

        expect(leaflet.tileLayer).toHaveBeenCalledExactlyOnceWith('https://tiles.example.com/light/{z}/{x}/{y}.png', { attribution: '&copy; Example Maps', maxZoom: 17 });
        expect(leaflet.drawn.tiles.addTo).toHaveBeenCalledWith(leaflet.drawn.map);
    });

    it('shows the dark tiles on a dark page, swapping them as the theme changes', async () => {
        setTheme('dark');
        mapView();
        await flushPromises();

        expect(leaflet.tileLayer).toHaveBeenLastCalledWith('https://tiles.example.com/dark/{z}/{x}/{y}{r}.png', { attribution: '&copy; Example Dark Maps', maxZoom: 20 });

        setTheme('light');
        await flushPromises();

        expect(leaflet.drawn.tiles.remove).toHaveBeenCalledOnce();
        expect(leaflet.tileLayer).toHaveBeenLastCalledWith('https://tiles.example.com/light/{z}/{x}/{y}.png', { attribution: '&copy; Example Maps', maxZoom: 17 });
        expect(leaflet.drawn.tiles.addTo).toHaveBeenCalledTimes(2);
    });

    it('waits for Leaflet before following the theme', async () => {
        mapView();
        setTheme('dark');
        await flushPromises();

        expect(leaflet.tileLayer).toHaveBeenCalledExactlyOnceWith('https://tiles.example.com/dark/{z}/{x}/{y}{r}.png', { attribution: '&copy; Example Dark Maps', maxZoom: 20 });
        expect(leaflet.drawn.tiles.remove).not.toHaveBeenCalled();
    });

    it('marks each point with an accent dot, titled with its label', async () => {
        mapView({ markers: [{ ...totnes, label: 'Totnes' }, { ...stroud, label: 'Stroud' }] });
        await flushPromises();

        expect(leaflet.drawn.pins.addTo).toHaveBeenCalledWith(leaflet.drawn.map);
        expect(leaflet.drawn.pins.addLayer.mock.calls).toEqual([
            [{ at: [50.43107, -3.6843], options: { title: 'Totnes', icon: dot } }],
            [{ at: [51.75, -2.2], options: { title: 'Stroud', icon: dot } }],
        ]);
    });

    it('follows a new centre and zoom', async () => {
        const wrapper = mapView();
        await flushPromises();

        await wrapper.setProps({ center: stroud, zoom: 8 });

        expect(leaflet.drawn.map.setView).toHaveBeenCalledExactlyOnceWith([51.75, -2.2], 8);
    });

    it('redraws the points when they change', async () => {
        const wrapper = mapView({ markers: [{ ...totnes, label: 'Totnes' }] });
        await flushPromises();

        await wrapper.setProps({ markers: [{ ...stroud, label: 'Stroud' }] });

        expect(leaflet.drawn.pins.clearLayers).toHaveBeenCalledTimes(2);
        expect(leaflet.drawn.pins.addLayer).toHaveBeenLastCalledWith({ at: [51.75, -2.2], options: { title: 'Stroud', icon: dot } });
    });

    it('draws what it was last given when that changes while Leaflet loads', async () => {
        const wrapper = mapView();

        await wrapper.setProps({ center: stroud, zoom: 8, markers: [{ ...stroud, label: 'Stroud' }] });
        await flushPromises();

        expect(leaflet.map).toHaveBeenCalledExactlyOnceWith(expect.anything(), { center: [51.75, -2.2], zoom: 8 });
        expect(leaflet.drawn.pins.addLayer).toHaveBeenCalledExactlyOnceWith({ at: [51.75, -2.2], options: { title: 'Stroud', icon: dot } });
        expect(leaflet.drawn.map.setView).not.toHaveBeenCalled();
    });

    it('tells the page where it was clicked, brought back onto the globe past the date line', async () => {
        const wrapper = mapView();
        await flushPromises();
        const [event, onClick] = leaflet.drawn.map.on.mock.calls[0];

        onClick({ latlng: { wrap: () => ({ lat: 50.4271, lng: -3.7124 }) } });

        expect(event).toBe('click');
        expect(wrapper.emitted('pick')).toEqual([[{ latitude: 50.4271, longitude: -3.7124 }]]);
    });

    it('removes the map when it goes', async () => {
        const wrapper = mapView();
        await flushPromises();

        wrapper.unmount();

        expect(leaflet.drawn.map.remove).toHaveBeenCalledOnce();
    });

    it('never draws a map once it has gone, even when Leaflet loads after', async () => {
        mapView().unmount();
        await flushPromises();

        expect(leaflet.map).not.toHaveBeenCalled();
    });
});
