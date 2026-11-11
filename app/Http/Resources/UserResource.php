<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A member as the pages see them (resources/js/types/models/user.ts). Everyone gets their name; only the member
 * themselves gets their email, whether it's confirmed and their site role. Coming from a circle's members, they also
 * carry their membership of it.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Keys ending in ? are sent only to the member themselves (email, email_verified, role) or when they come with
     * a circle membership.
     *
     * @return array{first_name: string, last_name: string, 'email'?: string, 'email_verified'?: bool, 'role'?: array{value: string, label: string, internal: bool}, 'membership'?: array{id: int, role: array{value: string, label: string}, joined_at: string}}
     */
    public function toArray(Request $request): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            $this->mergeWhen($request->user()?->is($this->resource) ?? false, fn (): array => [
                'email' => $this->email,
                'email_verified' => $this->hasVerifiedEmail(),
                'role' => [
                    'value' => $this->role->value,
                    'label' => $this->role->label(),
                    'internal' => $this->role->internal(),
                ],
            ]),
            'membership' => $this->whenPivotLoaded('circle_user', fn (): array => [
                'id' => $this->pivot->id,
                'role' => ['value' => $this->pivot->role->value, 'label' => $this->pivot->role->label()],
                'joined_at' => $this->pivot->created_at->toIso8601String(),
            ]),
        ];
    }
}
