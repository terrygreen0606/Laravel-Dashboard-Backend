<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'shortname' => $this->shortname,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'company_email' => $this->company_email,
            'address' => $this->address,
            'vendor_type_id' => $this->vendor_type_id,
//            'vessels' => VesselResource::collection($this->vessels)
        ];
    }
}
