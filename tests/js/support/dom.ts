import type { DOMWrapper, VueWrapper } from '@vue/test-utils';

/**
 * A mounted component or any element in it, including what wrapper.get() returns.
 */
type Wrapper = VueWrapper | Omit<DOMWrapper<Element>, 'exists'>;

/**
 * The form control with the given name attribute, e.g. field(wrapper, 'email').element.value.
 */
export function field(wrapper: Wrapper, name: string): Omit<DOMWrapper<HTMLInputElement>, 'exists'> {
    return wrapper.get<HTMLInputElement>(`[name="${name}"]`);
}

/**
 * Every link's text and href, in page order, e.g. to check a page links to the right routes.
 */
export function links(wrapper: Wrapper): [string, string | undefined][] {
    return wrapper.findAll('a').map((link) => [link.text(), link.attributes('href')]);
}

/**
 * The buttons of a labelled group, such as a SegmentedControl, in order.
 */
export function groupButtons(wrapper: Wrapper, label: string): DOMWrapper<HTMLButtonElement>[] {
    return wrapper.get(`[role="group"][aria-label="${label}"]`).findAll<HTMLButtonElement>('button');
}
