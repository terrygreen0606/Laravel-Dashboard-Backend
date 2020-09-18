<?php

namespace App\Http\Resources;

use App\Models\Vessel;
use App\Models\VesselAISMTPoll;

use Illuminate\Http\Resources\Json\JsonResource;

class NetworkResourceShort extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $network_vessels = Vessel::
            select(['vessels.*'])
            ->distinct()
            ->join("companies AS c1", 'vessels.company_id','=','c1.id')
            ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
            ->where('c1.networks_active', 1)
            ->where('nc.network_id', $this->id)
            ->get();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'ter_status' => $this->ter_status,
            'sat_status' => $this->sat_status,
            'ais_last_updated_at' => $this->ais_last_updated_at,
        ];
    }
}
