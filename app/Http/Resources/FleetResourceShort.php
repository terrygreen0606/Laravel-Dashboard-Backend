<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\VesselAISMTPoll;

class FleetResourceShort extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $fleetVessels = $this->vessels()->where('fleet_id',$this->id)->get();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vessels' => $fleetVessels,
            'ter_status' => $this->ter_status,
            'sat_status' => $this->sat_status,
            'ais_last_updated_at' => $this->ais_last_updated_at,
        ];
    }
}
