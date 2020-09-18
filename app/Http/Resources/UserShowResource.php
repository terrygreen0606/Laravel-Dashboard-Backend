<?php

namespace App\Http\Resources;

use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /*if($this->company_id && $this->company()->where('id',$this->company_id)->first()){
            $company_name = $this->company()->where('id',$this->company_id)->first()->name;
        }else{
            $company_name = '';
        }*/
        return [
            'id' => $this->id,
            'title' => $this->title,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'email' => $this->email,
            'username' => $this->username,
            'active' => (boolean)$this->active,
            'companies' => $this->companies->pluck('id'),
             //'effectiveCompanies' => EffectiveCompanyResource::collection($this->companies),
            'roles' => Role::where('id', $this->role_id)->first(),
            'role_id' => $this->role_id,
            'type_ids' => $this->contactTypes()->pluck('contact_type_id'),
            'address' => $this->address,
            'zone_name' => $this->address ? $this->address->zone->name : null,
            'primary_company' => $this->primary_company_id > 0 ?  Company::find($this->primary_company_id) : $this->companies()->first(),
            "companies" => $this->companies->pluck('id'),
            'primary_company_id' => $this->primary_company_id,
            //'coverage' => $this->company_id ? (stristr($company_name, 'donjon') ? 1 : 0) : 0,
            'response_asset_provider' => $this->response_asset_provider,
            'home_number' => $this->home_number,
            'mobile_number' => $this->mobile_number,
            'work_phone' => $this->work_phone,
            'aoh_phone' => $this->aoh_phone,
            'fax' => $this->fax,
            'alternate_email' => $this->alternate_email,
            'occupation' => $this->occupation,
            'resume_link' => $this->resume_link,
            'description' => $this->description,
            'has_photo' => (bool) $this->has_photo,
            'vrp_import' => $this->vrp_import,
            'djs_active' => $this->djs_active,
            'networks_active' => $this->networks_active,
            'capabilies_active' => $this->capabilies_active
        ];
    }
}
