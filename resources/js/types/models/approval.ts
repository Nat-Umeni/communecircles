import type { ApprovalStatus, ApprovalType } from '../enums';
import type { ApprovalPermissions } from '../policies';
import type { EnumOption, Timestamp } from '../utilities';
import type { User } from './user';

/**
 * An approval, as app/Http/Resources/ApprovalResource.php sends it. Who it's about, who asked and the thing waiting
 * for approval come only when the controller loaded them; a page says which it gets with Loaded<Approval, …>.
 */
export interface Approval {
    id: number;
    type: EnumOption<ApprovalType>;
    /** 'expired' is what the person invited is told about an invite that was withdrawn; they never see 'cancelled'. */
    status: EnumOption<ApprovalStatus | 'expired'>;
    reason: string | null;
    /** When it was asked for, or asked for again. */
    requested_at: Timestamp;
    can: ApprovalPermissions;
    /** Who it's about: whoever created the new thing, the person asking to join, or the person invited. */
    user?: User;
    /** Who asked, or sent the invite; null once their account is gone. */
    requester?: User | null;
    /** The thing waiting for approval, summed up by its Approvable::approvalSummary(). */
    subject?: ApprovalSubject;
}

/**
 * What the thing waiting for approval is, from App\Models\Contracts\Approvable::approvalSummary().
 */
export interface ApprovalSubject {
    name: string;
    description: string;
    url: string;
}
