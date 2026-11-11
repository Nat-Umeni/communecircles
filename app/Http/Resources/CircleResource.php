<?php

namespace App\Http\Resources;

use App\Models\Circle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A circle as the pages see it (resources/js/types/models/circle.ts). Fields from relations appear only when the
 * caller has loaded or counted them, so each page (and later the API's include) decides how much to send.
 *
 * @mixin Circle
 */
class CircleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'description' => $this->description,
            'location' => $this->location()?->toArray(),
            'members_count' => $this->whenCounted('members'),
            'status' => $this->whenLoaded('approval', fn (): array => [
                'value' => $this->approval->status->value,
                'label' => $this->approval->status->label(),
            ]),
        ];
    }
}
