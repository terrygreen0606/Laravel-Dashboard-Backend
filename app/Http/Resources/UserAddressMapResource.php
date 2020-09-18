<?php

namespace App\Http\Resources;

use App\Models\Capability;
use App\Models\CapabilityField;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressMapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $capability =  Capability::where(['id' => $this->smff_service_id, 'status' => 1])->first();
        if(isset($capability)){
            $primary  = CapabilityField::where('id',$capability->primary_service)->first();
        }


        $smff = $this->smff();
        return [
            'id' => $this->address_id,
            'text' => $this->first_name . ' ' . $this->last_name,
            'user_id' => $this->id,
            'latlng' => [$this->latitude, $this->longitude],
           // 'smff' =>$smff,
            'primary_service' => isset($capability) ? $capability->primary_service : null,
            'primary_service_code' => isset($primary) ? $primary->code : null
        ];
    }
}
