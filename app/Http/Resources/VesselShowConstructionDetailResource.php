<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Vessel;

class VesselShowConstructionDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_id' => $this->company_id,
            'imo' => $this->imo,
            'lead_ship' => $this->lead_ship,
            'lead_ship_id' => $this->vesselObjects($this->lead_ship_id),
            'lead_sister_ship_id' => $this->vesselObjects($this->lead_sister_ship_id),
            'active' => $this->active,
            'sister_vessels' => $this->vesselObjects($this->sisters()->pluck('id')->toArray()),
            'child_vessels' => $this->vesselObjects($this->childs()->pluck('id')->toArray()),
            'providers' => $this->vendors()->whereHas('type', function ($q) {
                $q->where('name', 'Damage Stability Certificate Provider');
            })->pluck('id'),
            'construction_detail' => [
                'construction_length_overall' => $this->construction_length_overall,
                'construction_length_bp' => $this->construction_length_bp,
                'construction_length_reg' => $this->construction_length_reg,
                'construction_bulbous_bow' => $this->construction_bulbous_bow,
                'construction_breadth_extreme' => $this->construction_breadth_extreme,
                'construction_breadth_moulded' => $this->construction_breadth_moulded,
                'construction_draught' => $this->construction_draught,
                'construction_depth' => $this->construction_depth,
                'construction_height' => $this->construction_height,
                'construction_tcm' => $this->construction_tcm,
                'construction_displacement' => $this->construction_displacement,
            ]
        ];
    }

    public function vesselObjects($ids) {
        if (is_array($ids)) {
            $vessels = Vessel::whereIn('id', $ids)->get();
            $ret = [];
            foreach ($vessels as $vessel) {
                $is_lead_sister = Vessel::where('lead_sister_ship_id', $vessel->id)->exists();
                $ret[] = [
                    'id' => $vessel->id,
                    'name' => $vessel->name,
                    'lead_ship' => $vessel->lead_ship,
                    'lead_sister_ship' => $is_lead_sister
                ];
            }
            return $ret;
        } else {
            $vessel = Vessel::find($ids);
            if (empty($vessel)) return null;
            return [
                'id' => $vessel->id,
                'name' => $vessel->name
            ];
        }
    }
}
