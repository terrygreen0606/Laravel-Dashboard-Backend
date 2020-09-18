<?php

namespace App\Helpers;

use App\Models\Vrp\Vessel as VesselAlias;
use App\Models\Vrp\Vessel;
use App\Models\Vrp\VrpPlan as VrpPlanAlias;
use App\Models\Vrp\VrpPlan;

class VRPExpressVesselHelper {
    public static function getVessels($vessel_data)
    {
        $vessels = [];

        if (!PermissionHelper::hasVrpAccess('vessels'))
            return $vessels;

        if ($vessel_data) {
            foreach ($vessel_data as $id => $vessel) {
                $plan = VrpPlanAlias::where('plan_number', '=', $vessel['plan_number'])->first(['id']);
                $vessel_model = Vessel::where('imo', $vessel['imo']);
                if ($vessel['official_number']) {
                    $vessel_model->orWhere('official_number', $vessel['official_number']);
                }
                $vessel_found = $vessel_model->first();
                $vessel_count = $vessel_model->count();
                if ($vessel_found) {
                    if ($plan) {
                        $vessel_match = Vessel::where([['imo', $vessel['imo']], ['plan_number_id', $plan->id]])->with('VrpPlan')->first();
                        if ($vessel_match) {
                            $vessels[$vessel['id']]['status'] = $vessel_match->vessel_status;
                            $vessels[$vessel['id']]['vessel_is_tank'] = $vessel_match->vessel_is_tank === 'NT' ? 0 : 1;
                            $vessels[$vessel['id']]['plan_number'] = $vessel_match->VrpPlan->plan_number;
                            $vessels[$vessel['id']]['vrp_comparison'] = 'match';
                            $vessels[$vessel['id']]['plan_holder'] = $vessel_match->VrpPlan->plan_holder;
                            $vessels[$vessel['id']]['primary_smff'] = $vessel_match->VrpPlan->primary_smff;
                        }
                    } else {
                        $vessels[$vessel['id']]['status'] = $vessel_found->vessel_status;
                        $vessels[$vessel['id']]['vessel_is_tank'] = $vessel_found->vessel_is_tank === 'NT' ? 0 : 1;
                        $vessels[$vessel['id']]['plan_number'] = $vessel_found->VrpPlan->plan_number;
                        $vessels[$vessel['id']]['vrp_comparison'] = 'conflict';
                        $vessels[$vessel['id']]['plan_holder'] = $vessel_found->VrpPlan->plan_holder;
                        $vessels[$vessel['id']]['primary_smff'] = $vessel_found->VrpPlan->primary_smff;
                    }
                } else {
                    $vessels[$vessel['id']]['status'] = '';
                    $vessels[$vessel['id']]['vessel_is_tank'] = 0;
                    $vessels[$vessel['id']]['plan_number'] = '';
                    $vessels[$vessel['id']]['vrp_comparison'] = 'N/A';
                    $vessels[$vessel['id']]['plan_holder'] = '';
                    $vessels[$vessel['id']]['primary_smff'] = '';
                }
                $vessels[$vessel['id']]['vrp_count'] = $vessel_count;
            }
        }
        return $vessels;
    }

    public static function getVesselsBySearch($query, $exclude_ids, $vrp_status)
    {
        $vesselsData = [];

        if (!PermissionHelper::hasVrpAccess('vessels'))
            return $vesselsData;

        if ($query) {
//            $exclude_ids = $exclude_ids;
//            $vrp_status = $vrp_status;
            $vessels = Vessel::search($query)->get();
            if ($vessels) {
                foreach ($vessels as $vessel) {
                    if (!in_array($vessel->imo, $exclude_ids, false)) {
                        if ($vrp_status === -1 || ($vrp_status === 1 && $vessel->vessel_status === 'Authorized') || ($vrp_status === 0 && $vessel->vessel_status === 'Not Authorized')) {
                            $data = Vessel::where('id', $vessel->id)->select('id', 'plan_number_id', 'vessel_name', 'imo', 'official_number', 'vessel_status', 'vessel_type', 'vessel_is_tank')->with('VrpPlan:id,plan_number,plan_holder,primary_smff')->first();
                            $vesselsData[] = [
                                'imo' => $data->imo,
                                'official_number' => $data->official_number,
                                'vessel_status' => $data->vessel_status,
                                'vrp_comparison' => 'imported',
                                'vrp_plan_number' => $data->VrpPlan->plan_number,
                                'vessel_is_tank' => $data->vessel_is_tank === 'NT' ? 0 : 1,
                                'vrp_count' => VesselAlias::where('imo', $vessel->imo)->count(),
                                'vessel_name' => $data->vessel_name,
                                'vessel_type' => $data->vessel_type,
                                'plan_holder' => $data->VrpPlan->plan_holder ?? '',
                                'primary_smff' => $data->VrpPlan->primary_smff ?? ''
                            ];
                        }
                    }
                }
            }

        }
        return $vesselsData;
    }

    public static function getVesselsUnderPlan($plan_number, $exclude_imo = [99999999999])
    {
        $vesselsData = [];

        if (!PermissionHelper::hasVrpAccess('vessels'))
            return $vesselsData;

        if ($plan_number && $exclude_imo) {
            $plan = VrpPlanAlias::where('plan_number', '=', $plan_number)->first();
            $vessels = VesselAlias::where('plan_number_id', $plan->id)->whereNotNull('imo')->whereNotIn('imo', $exclude_imo)->select('vessel_name', 'imo', 'official_number', 'vessel_status', 'vessel_type', 'vessel_is_tank')->get();
            foreach ($vessels as $vessel) {
                $vessel = (object)$vessel;
                $vesselsData[] = [
                    'id' => $vessel->imo,
                    'imo' => $vessel->imo,
                    'official_number' => $vessel->official_number,
                    'vrp_status' => $vessel->vessel_status ?? 'N/A',
                    'vrp_comparison' => 'imported',
                    'vrp_plan_number' => $plan->plan_number,
                    'company' => [
                        'plan_number' => $plan->plan_number
                    ],
                    'vrp_count' => 0,
                    'name' => $vessel->vessel_name,
                    'type' => $vessel->vessel_type,
                    'tanker' => (boolean)$vessel->vessel_is_tank,
                    'resource_provider' => false,
                    'active' => false,
                    'fleets' => [],
                    'vrp_express' => true,
                    'wcd' => $vessel->wcd,
                    'vessel_status' => $vessel->vessel_status,
                    'plan_holder' => $plan->plan_holder,
                    'primary_smff' => $plan->primary_smff,

                ];
            }
        }
        return $vesselsData;
    }

    public static function getVesselsUnderPlanById($id)
    {
        $vesselsData = [];

        if (!PermissionHelper::hasVrpAccess('vessels'))
            return $vesselsData;

        if ($id > 0) {

            $vessel = Vessel::find($id);
            $plan = VrpPlan::where('id',$vessel->plan_number_id)->first();
            return  [
                    'id' => $id,
                    'imo' => $vessel->imo,
                    'official_number' => $vessel->official_number,
                    'vrp_status' => $vessel->vessel_status ?? 'N/A',
                    'vrp_comparison' => 'imported',
                    'vrp_plan_number' => $plan->plan_number,
                    'company' => [
                        'plan_number' => $plan->plan_number
                    ],
                    'vrp_count' => 0,
                    'name' => $vessel->vessel_name,
                    'type' => $vessel->vessel_type,
                    'tanker' => (boolean)$vessel->vessel_is_tank,
                    'resource_provider' => false,
                    'active' => false,
                    'vrp_express' => true,
                    'wcd' => $vessel->wcd,
                    'vessel_status' => $vessel->vessel_status,
                    'plan_holder' => $plan->plan_holder,
                    'primary_smff' => $plan->primary_smff,

                ];
            }
        return null;
    }
}
