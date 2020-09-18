<?php

namespace App\Http\Resources;

use App\Models\Vendor;
use App\Models\Vrp\VrpPlan;
use Illuminate\Http\Resources\Json\JsonResource;

class VesselResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $plan = VrpPlan::find($this->id);
        $plan_number = $plan->plan_number;
        //echo print_r($this->id, true);
        return [
            'id' => $this->id,
            'imo' => $this->imo,
            'official_number' => $this->official_number,
            'vrp_count' => $this->vrp_count ?? 'N/A',
            'vrp_status' => $this->vrp_status ?? '',
            'vrp_comparison' => $this->vrp_comparison ?? 'N/A',
            'vrp_plan_number' => $plan_number ?? '',
            'vrp_vessel_is_tank' => $this->vrp_vessel_is_tank,
            'name' => $this->name,
            'type' => $this->vrp_type,
            //(($this->type && $this->type->id > 0) ?
              //  $this->type->name :
              //  (($this->vrp_type) ? $this->vrp_type : 'Unknown')),
            'company' => $this->company_id ? [
                'id' => $this->company_id,
                'name' => $this->company_name,
                'plan_number' => trim($this->company_plan_number) ? $this->company_plan_number : '',
                'active' => $this->company_active
            ] : null,
            'tanker' => (boolean)$this->tanker,
            'resource_provider' => (boolean)$this->resource_provider,
            'active' => (boolean)$this->active,
            'vrp_import' => $this->vrp_import,
            'djs_active' => $this->djs_active,
            'networks_active' => $this->networks_active,
            'capabilies_active' => $this->capabilies_active,
            'vrp_primary_smff' => $this->vrp_primary_smff,
            'djs' => $this->djs,
            'linked' => $this->linked,
            'vessel_type_id' => $this->vessel_type_id,
            'dead_weight' => $this->dead_weight,
            'deck_area' => $this->deck_area,
            'oil_tank_volume' => $this->oil_tank_volume,
            'oil_group' => $this->oil_group,
            'qi' => $this->vendors()->whereHas('type', function ($q) {
                $q->where('id', Vendor::TYPE_QI);
            })->pluck('id'),
            'pi' => $this->vendors()->whereHas('type', function ($q) {
                $q->where('id', Vendor::TYPE_PANDI);
            })->pluck('id'),
            'societies' => $this->vendors()->whereHas('type', function ($q) {
                $q->where('id', Vendor::TYPE_SOCIETY);
            })->pluck('id'),
            'insurers' => $this->vendors()->whereHas('type', function ($q) {
                $q->where('id', Vendor::TYPE_HANDM);
            })->pluck('id'),
            'providers' => $this->vendors()->whereHas('type', function ($q) {
                $q->where('id', Vendor::TYPE_DAMAGE);
            })->pluck('id')
        ];
    }

    private function getCoverage () {

    }
}
