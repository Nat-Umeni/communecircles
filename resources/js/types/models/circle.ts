import type { Location } from '../dto';
import type { ApprovalStatus } from '../enums';
import type { EnumOption } from '../utilities';

/**
 * A circle, as app/Http/Resources/CircleResource.php sends it. The optional fields come only when the controller
 * counted or loaded them; a page says which it gets with Loaded<Circle, …>.
 */
export interface Circle {
    public_id: string;
    name: string;
    description: string;
    /** Null until the circle says where it is. */
    location: Location | null;
    /** When its members were counted. */
    members_count?: number;
    /** Where its own approval stands, when that was loaded. */
    status?: EnumOption<ApprovalStatus>;
}
