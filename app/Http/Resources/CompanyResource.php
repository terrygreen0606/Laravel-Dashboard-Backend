<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\Vrp\VrpCountryMapping;
use App\Models\Company;
use App\Models\Vessel;
use App\Models\VendorType;
use App\Models\VesselVendor;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $vendor_type = 0;
        if($this->id > 0) {
            $vendor_type = Company::where('id', $this->id)->first()->vendor_type;
        }
        $country = '';
        if (count($this->primaryAddress)) {
            $country = $this->primaryAddress[0]->country;
        } else if ($this->vrp_country && !empty($this->vrp_country)) {
            $countryMapping = VrpCountryMapping::where('vrp_country_name', $this->vrp_country)->first();
            if ($countryMapping) {
                $country = $countryMapping->code;
            }
        }

        if(!empty($request->staticSearch['parent'])) {
            if($request->staticSearch['parent'] == $this->id) {
                $operatingCompanyID = Company::where('id', $this->id)->first()->operating_company_id;
                if($operatingCompanyID) {
                    $operatingCompany = Company::where('id', $operatingCompanyID)->first();
                    $vendor_type = Company::where('id', $operatingCompany->id)->first()->vendor_type;
                    $vessel = $this->vessels;
                    return [
                        'id' => $operatingCompany->id ? $operatingCompany->id : -1,
                        'name' => $operatingCompany->name,
                        'email' => $operatingCompany->email,
                        'fax' => $operatingCompany->fax,
                        'phone' => $operatingCompany->phone,
                        'vrp_plan_name' => $operatingCompany->plan_holder,
                        'plan_number' => strlen(trim($operatingCompany->vrp_plan_number)) ? $operatingCompany->plan_number : '',
                        'vrp_plan_number' => strlen(trim($operatingCompany->vrp_plan_number)) ? $operatingCompany->vrp_plan_number : '',
                        'resource_provider' => $operatingCompany->smffCapability ? true : false,
                        'active' => (boolean)$operatingCompany->active,
                        'location' => count($operatingCompany->primaryAddress) ? codeToCountryToCode($operatingCompany->primaryAddress[0]->country) : '',
                        'country'  => $country ?? '',
                        'stats' => [
                            'individuals' => count($operatingCompany->users),
                            'vessels' => $this->vessels ? count($this->vessels) : count(Vessel::where('company_id', $operatingCompany->id)->get()),
                            'contacts' => count($operatingCompany->contacts)
                        ],
                        'vrp_status' => $operatingCompany->vrp_status ?? 'NO VRP LINK',
                        'vrp_stats' => [
                            'plan_type' => $operatingCompany->vrpPlan->plan_type ?? '',
                            'vessels' => $operatingCompany->vrpPlan ? count($operatingCompany->vrpPlan->vessels) : 0
                        ],
                        'vrp_express' => strlen(trim($operatingCompany->vrp_plan_number)) ? 1 : 0,
            //            'coverage'    => str_contains(strtolower($this->primary_smff), 'donjon') ? 1 : 0,
                        'response'   => $operatingCompany->smff_service_id ? 1 : 0,
                        'is_tank' => $operatingCompany->vrp_plan_type ?? '',
                        'coverage' => $operatingCompany->active,
                        'vrp_import' => $operatingCompany->vrp_import,
                        'djs_active' => $operatingCompany->djs_active,
                        'networks_active' => $operatingCompany->networks_active,
                        'vendor_active' => $operatingCompany->vendor_active,
                        'vendor_type' => $vendor_type ? VendorType::where('id', $vendor_type)->first() : [],
                        'capabilies_active' => $operatingCompany->capabilies_active,
                        'vrp_primary_smff' => $operatingCompany->vrp_primary_smff,
                        'vendor_category' => $operatingCompany->vendor_category,
                        'qi_id' => $operatingCompany->qi_id
                    ];
                }
            }
        }
        $vesselVendor = VesselVendor::where('company_id', $this->id)->first();
        return [
            'id' => $this->id ? $this->id : -1,
            'name' => $this->name,
            'email' => $this->email,
            'fax' => $this->fax,
            'phone' => $this->phone,
            'vrp_plan_name' => $this->plan_holder,
            'plan_number' => strlen(trim($this->vrp_plan_number)) ? $this->plan_number : '',
            'vrp_plan_number' => strlen(trim($this->vrp_plan_number)) ? $this->vrp_plan_number : '',
            'resource_provider' => $this->smffCapability ? true : false,
            'active' => (boolean)$this->active,
            'location' => count($this->primaryAddress) ? codeToCountryToCode($this->primaryAddress[0]->country) : '',
            'country'  => $country ?? '',
            'stats' => [
                'individuals' => count($this->users),
                'vessels' => $vesselVendor ? count(VesselVendor::where('company_id', $this->id)->get()) : count($this->vessels),
                'contacts' => count($this->contacts)
            ],
            'vrp_status' => $this->vrp_status ?? 'NO VRP LINK',
            'vrp_stats' => [
                'plan_type' => $this->vrpPlan->plan_type ?? '',
                'vessels' => $this->vrpPlan ? count($this->vrpPlan->vessels) : 0
            ],
            'vrp_express' => strlen(trim($this->vrp_plan_number)) ? 1 : 0,
//            'coverage'    => str_contains(strtolower($this->primary_smff), 'donjon') ? 1 : 0,
            'response'   => $this->smff_service_id ? 1 : 0,
            'is_tank' => $this->vrp_plan_type ?? '',
            'coverage' => $this->active,
            'vrp_import' => $this->vrp_import,
            'djs_active' => $this->djs_active,
            'networks_active' => $this->networks_active,
            'vendor_active' => (int)$this->vendor_active,
            'vendor_type' => $vendor_type ? VendorType::where('id', $vendor_type)->first() : [],
            'capabilies_active' => $this->capabilies_active,
            'vrp_primary_smff' => $this->vrp_primary_smff,
            'vendor_category' => $this->vendor_category,
            'qi_id' => $this->qi_id
        ];
    }
}
