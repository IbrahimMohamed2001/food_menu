<?php

namespace Modules\Rooms\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Rooms\Models\Device;

/**
 * @mixin Device
 */
class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'paired_at' => $this->paired_at->toIso8601String(),
            'room' => $this->whenLoaded('room', fn () => [
                'number' => $this->room->room_number,
                'floor' => $this->room->floor,
            ]),
        ];
    }
}
