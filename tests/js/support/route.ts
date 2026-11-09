/**
 * Stands in for Ziggy's route(): "/" plus the route name, and a parameter after it when given, so tests can check
 * which route (and which record) a link or form uses, e.g. route('circles.show', 'k3x9q2mzt8') → "/circles.show/k3x9q2mzt8".
 */
export function fakeRoute(name: string, parameter?: string | number): string {
    return parameter === undefined ? `/${name}` : `/${name}/${parameter}`;
}
