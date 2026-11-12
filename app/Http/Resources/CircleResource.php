<?php

namespace App\Http\Resources;

use App\DTO\Location;
use App\Models\Circle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * A circle as the pages see it (resources/js/types/models/circle.ts). Fields from relations appear only when the
 * caller has loaded or counted them, so each page (and later the API's include) decides how much to send.
 *
 * @mixin Circle
 */
class CircleResource extends JsonResource
{
    /**
     * Keys ending in ? are sent only when the caller loaded or counted them.
     *
     * @return array{public_id: string, name: string, description: string, location: array<string, mixed>|null, 'members_count'?: int, 'status'?: array{value: string, label: string}}
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'description' => $this->description,
            'location' => $this->locationForViewer()?->toArray(),
            'members_count' => $this->whenCounted('members'),
            'status' => $this->whenLoaded('approval', fn (): array => [
                'value' => $this->approval->status->value,
                'label' => $this->approval->status->label(),
            ]),
        ];
    }

    /**
     * Where the circle is, with its exact spot only for those who may see it (CirclePolicy::viewExactLocation);
     * everyone else is told there is one.
     * NOTE: only a circle with an exact spot is checked, so a list of circles doesn't look the viewer's membership
     * up for every one.
     */
    private function locationForViewer(): ?Location
    {
        $location = $this->location();

        if ($location?->coordinates === null || Gate::allows('viewExactLocation', $this->resource)) {
            return $location;
        }

        return $location->withoutCoordinates();
    }
}
