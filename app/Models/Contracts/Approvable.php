<?php

namespace App\Models\Contracts;

use App\Models\Approval;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Something approvals are about, e.g. a circle: approved when new, and asked to join or invited into.
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
     * What happens once one of its approvals is approved, e.g. adding someone who asked to join.
     */
    public function whenApproved(Approval $approval): void;
}
