<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VesselTrackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $vessel = $this->vessel()->first();
        return [
            'id' => $this->id,
            'vessel_id' => $vessel->id,
            'imo' => $vessel->imo,
            'name' => $vessel->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'speed' => $this->speed ?? 'Unknown',
            'heading' => $this->heading ?? 'Unknown',
            'course' => $this->course ?? 'Unknown',
            'ais_timestamp' => $this->ais_timestamp,
            'destination' => $this->destination,
            'eta' => $this->eta,
            'ais_status' => $this->navStatus->value,
            'ais_status_id' => $this->ais_status_id,
            'type' => $vessel->type ? $this->vessel->type->ais_category_id  : null
        ];
    }

}
