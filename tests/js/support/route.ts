/**
 * Stands in for Ziggy's route(): "/" plus the route name, so tests can check which route a link or form uses.
 */
export function fakeRoute(name: string): string {
    return `/${name}`;
}
