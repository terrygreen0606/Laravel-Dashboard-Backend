<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VesselShowAISResource extends JsonResource
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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'ais_timestamp' => $this->ais_timestamp,
            'speed' => $this->speed,
            'heading' => $this->heading,
            'course' => $this->course,

            'ais_mmsi' => $this->ais_mmsi,
            'ais_imo' => $this->ais_imo,

            'eta' => $loc ? $loc->eta : null,
            'eta_mt' => $loc ? $loc->eta_mt : null,
            'last_port_time' => $loc ? $loc->eta_mt : null,
            'destination' => $loc ? $loc->eta_mt : null,

            'zone_id' => $this->zone_id,
            'active' => $this->active,
            'nav_status' => $this->navStatus->value,
            'fleets' => $this->fleets()->where('internal', 0)->pluck('fleets.id'),
            'ais_source' => $this->ais_source,

            // photos
            'ais_photo_url' => $this->ais_photo_url,

            // extended
            'ports' => $loc ? [
                'last_port' => $loc->lastPort()->first() ? $loc->lastPort()->first()->attributesToArray() : null,
                'current_port' => $loc->currentPort()->first() ? $loc->currentPort()->first()->attributesToArray() : null,
                'next_port' => $loc->nextPort()->first() ? $loc->nextPort()->first()->attributesToArray() : null
            ] : null,

            'ais_name' => $this->ais_name,
            'ais_shiptype' => $this->ais_shiptype,
            'ais_type_name' => $this->ais_type_name,
            'ais_type_summary' => $this->ais_type_summary,
            'ais_callsign' => $this->ais_callsign,
            'ais_flag' => $this->ais_flag,
            'ais_length' => $this->ais_length,
            'ais_width' => $this->ais_width,
            'ais_draught' => $this->ais_draught,
            'ais_gross_tonnage' => $this->ais_gross_tonnage,
            'ais_deadweight' => $this->ais_deadweight,
            'ais_year_built' => $this->ais_year_built,
            'ais_status_id' => $this->ais_status_id,
            'vessel_type' => $this->type->ais_category_id,
            'rotation_angle' => round($this->heading, 5)
        ];
    }
}
