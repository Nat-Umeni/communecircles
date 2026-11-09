<?php

namespace App\Models\Contracts;

use App\Models\Approval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Something that can be waiting for approval: when it's new, or when someone asks to join it or is invited into it.
 */
interface Approvable
{
    /**
     * All its approvals, of every type (App\Enums\ApprovalType).
     *
     * @return MorphMany<Approval, $this>
     */
    public function approvals(): MorphMany;

    /**
     * What happens once one of its approvals is approved, e.g. letting in the person who asked to join.
     */
    public function whenApproved(Approval $approval): void;
}
