<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyMapInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'plan_number' => $this->plan_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'description' => $this->description,
            'networks' => count($this->networks) ? $this->networks()->pluck('name') : ['Not in a network'],
            'network_codes' => count($this->networks) ? $this->networks()->pluck('code') : ['Not in a network'],
            'smff_service_id' => $this->smff_service_id,
            'has_photo' => (bool) $this->has_photo,
            'poc' => $this->companyPOC()->select('id', 'first_name', 'last_name', 'email', 'home_number', 'mobile_number')->first()
        ];
    }
}
