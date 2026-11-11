<?php

namespace App\Http\Resources;

use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An approval as the pages see it (resources/js/types/models/approval.ts): what kind it is, where it stands, and what
 * the viewer can do with it. Who it's about, who asked and the thing waiting for approval appear only when the caller
 * has loaded them.
 *
 * @mixin Approval
 */
class ApprovalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => ['value' => $this->type->value, 'label' => $this->type->label()],
            'status' => $this->statusFor($request),
            'reason' => $this->reason,
            'requested_at' => $this->updated_at->toIso8601String(),
            'can' => [
                'decide' => $request->user()?->can('decide', $this->resource) ?? false,
                'cancel' => $request->user()?->can('cancel', $this->resource) ?? false,
            ],
            'user' => $this->whenLoaded('user', fn (): UserResource => UserResource::make($this->user)),
            'requester' => $this->whenLoaded('requester', fn (): UserResource => UserResource::make($this->requester)),
            'subject' => $this->whenLoaded('approvable', fn (): array => $this->approvable->approvalSummary()),
        ];
    }

    /**
     * Where it stands, as the viewer is told. The person invited only hears that a withdrawn invite expired, never
     * that it was cancelled.
     *
     * @return array{value: string, label: string}
     */
    private function statusFor(Request $request): array
    {
        if ($this->type === ApprovalType::Invite && $this->status === ApprovalStatus::Cancelled && $request->user()?->getKey() === $this->user_id) {
            return ['value' => 'expired', 'label' => 'Expired'];
        }

        return ['value' => $this->status->value, 'label' => $this->status->label()];
    }
}
