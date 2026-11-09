import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import TimeAgo from '@/components/ui/TimeAgo.vue';

function ago(datetime: string): string {
    return mount(TimeAgo, { props: { datetime } }).text();
}

describe('TimeAgo', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-11-09T12:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('marks up the moment it describes', () => {
        const time = mount(TimeAgo, { props: { datetime: '2026-11-06T12:00:00+00:00' } }).get('time');

        expect(time.attributes('datetime')).toBe('2026-11-06T12:00:00+00:00');
    });

    it('says how long ago it was, in the largest whole unit', () => {
        expect(ago('2026-11-09T11:59:30Z')).toBe('30 seconds ago');
        expect(ago('2026-11-09T11:55:00Z')).toBe('5 minutes ago');
        expect(ago('2026-11-09T09:30:00Z')).toBe('2 hours ago');
        expect(ago('2026-11-08T06:00:00Z')).toBe('yesterday');
        expect(ago('2026-11-06T12:00:00Z')).toBe('3 days ago');
        expect(ago('2026-10-26T12:00:00Z')).toBe('2 weeks ago');
        expect(ago('2026-08-01T12:00:00Z')).toBe('3 months ago');
        expect(ago('2024-11-01T12:00:00Z')).toBe('2 years ago');
    });

    it('says now for this very moment', () => {
        expect(ago('2026-11-09T12:00:00Z')).toBe('now');
    });
});
