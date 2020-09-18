<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VesselListAISPollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $loc = $this->currentLocation();
        return [
            'id' => $this->id,
            'imo' => $this->imo,
            'official_number' => $this->official_number,
            'name' => $this->name,
            'ais_timestamp' => $this->ais_timestamp,
            'zone_id' => $this->zone_id,
            'zone_name' => $this->zone->name
        ];
    }
}
