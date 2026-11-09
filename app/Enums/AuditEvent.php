<?php

namespace App\Enums;

/**
 * Audit log events of our own, beside the created/updated/deleted ones Spatie's LogsActivity writes
 * (Spatie\Activitylog\Enums\ActivityEvent). Use the enum when writing or filtering the log, so names can't drift.
 */
enum AuditEvent: string
{
    case ApprovalRequested = 'approval_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
