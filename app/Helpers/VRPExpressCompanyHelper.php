<?php

namespace App\Helpers;

use App\Models\Vrp\VrpPlan;
use Illuminate\Support\Facades\Auth;

class VRPExpressCompanyHelper {
    public static function getCompanies($company_ids)
    {
        $companies = [];
//        return PermissionHelper::hasVrpAccess('companies');
        if (!PermissionHelper::hasVrpAccess('companies'))
            return $companies;

        if ($company_ids) {
            foreach ($company_ids as $id => $plan_number) {
                $plan = VrpPlan::where('plan_number', $plan_number)->withCount('Vessels')->first();
                if ($plan) {
                    $companies[$id]['plan_holder'] = $plan->plan_holder;
                    $companies[$id]['status'] = $plan->status;
                    $companies[$id]['plan_type'] = $plan->plan_type;
                    $companies[$id]['vessels_count'] = $plan->vessels_count;
                    $companies[$id]['primary_smff'] = $plan->primary_smff;
                }
            }
        }

        return $companies;
    }

    public static function getCompaniesBySearch($query, $exclude_ids, $vrp_status)
    {
        $companies = [];

        if (!PermissionHelper::hasVrpAccess('companies'))
            return $companies;

        if ($query) {
            $plans = VrpPlan::search($query)->get();
            if ($plans) {
                foreach ($plans as $plan) {
                    if (!in_array($plan->plan_number, $exclude_ids, false)) {
                        if ($vrp_status === -1 || ($vrp_status === 1 && $plan->status === 'Authorized') || ($vrp_status === 0 && $plan->status === 'Not Authorized')) {
                            $companies[] = VrpPlan::where('id', $plan->id)->select('plan_number', 'plan_holder', 'holder_country', 'status', 'plan_type', 'primary_smff')->withCount('Vessels')->first();
                        }
                    }
                }
            }

        }
        return $companies;
    }

    public static function getCompaniesByPlan($plan)
    {
        if (!PermissionHelper::hasVrpAccess('companies'))
            return [];

        $vrp_vessels = VrpPlan::where('plan_number', $plan)->with('Vessels')->first()->vessels;
        $plan_detail = VrpPlan::where('plan_number', $plan)->select('plan_number', 'plan_holder', 'plan_preparer', 'status', 'plan_exp_date', 'nt_expiration_date', 'tank_expiration_date', 'approval_date', 'plan_type', 'primary_smff', 'osro', 'holder_address_1', 'holder_address_2', 'holder_city', 'holder_state', 'holder_zip', 'holder_country', 'wcd_barrels')->first();
        return ['vrp_vessels' => $vrp_vessels, 'plan_detail' => $plan_detail];
    }

}
