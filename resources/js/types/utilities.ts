/**
 * A backed enum as the backend sends it for display: its value and its label (the enum's label()).
 */
export interface EnumOption<T extends string> {
    value: T;
    label: string;
}

/**
 * A model type with some of its conditional fields present, the ones a Resource sends only when the controller loaded
 * or counted them, e.g. Loaded<Circle, 'members_count'>. A relation that was loaded but is empty stays null.
 */
export type Loaded<T, K extends keyof T> = T & Required<Pick<T, K>>;

/**
 * A date and time as the backend sends it, in ISO 8601 (Carbon's toIso8601String()), e.g. 2026-11-08T09:00:00+00:00.
 */
export type Timestamp = `${number}-${number}-${number}T${number}:${number}:${number}${string}`;
