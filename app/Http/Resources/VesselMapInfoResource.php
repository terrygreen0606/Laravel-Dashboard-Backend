<?php

namespace App\Http\Resources;

use App\Models\NavStatus;
use App\Models\Fleet;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Network;
use App\Models\VesselAISPositions;
use App\Models\VesselType;
use App\Models\Zone;
use Illuminate\Support\Facades\Storage;

class VesselMapInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $tracks = VesselAISPositions::where('vessel_id', $this->id)
            ->orderBy('timestamp', 'desc')
            ->get();
        $latest = count($tracks) > 0 ? $tracks[0] : null;
        $type = VesselType::find($this->vessel_type_id);
        // $position = VesselAISPositions::where('vessel_id', $this->id)->latest('updated_at')->first();
        // if($position) {
        //     $navStatus = NavStatus::where('status_id', $position->status)->first();
        // }

        return [
            'id' => $this->id,
            'name' => $this->name ?? 'Not Set',
            'official_number' => $this->official_number ?? 'Unknown',
            'imo' => $this->imo,
            'has_photo' => (bool) $this->has_photo,
            'company' => $this->company_id
                ? [
                    'id' => $this->company_id,
                    'name' => $this->company->name,
                    'has_photo' => $this->company->has_photo,
                ]
                : null,
            'smff_service_id' => $this->smff_service_id,
            'count_tracks' => count($tracks),
            'networks' => $this->company_id
                ? $this->company->networks->map(function ($item) {
                    $network_id = $item->pivot->network_id;
                    $network = Network::find($network_id);
                    return [
                        'name' => $network->name,
                        'code' => $network->code,
                    ];
                })
                : [],
            'fleets' => $this->fleets->map(function ($item) {
                $fleet_id = $item->pivot->fleet_id;
                $fleet = Fleet::find($fleet_id);
                return [
                    'id' => $fleet->id,
                    'name' => $fleet->name,
                    'code' => $fleet->code,
                ];
            }),
            'nav_status' => $this->navStatus,
            'type' => $type ? $type->name : NULL,
            'timestamp' => $latest ? $latest->timestamp : NULL,
            'mmsi' => $latest ? $latest->mmsi : NULL,
            'latitude' => $latest ? $latest->lat : NULL,
            'longitude' => $latest ? $latest->lon : NULL,
            'course' => $latest ? $latest->course : NULL,
            'heading' => $latest ? $latest->heading : NULL,
            'speed' => $latest ? $latest->speed : NULL,
            'tonnage' => $latest ? $latest->grt : NULL,
            'destination' => $latest ? $latest->destination : NULL,
            'eta' => $latest ? $latest->eta : NULL,
            'zone' => $latest && $latest->zone_id
                ? Zone::find($latest->zone_id)->name
                : 'Outside US EEZ',
        ];
    }
}
