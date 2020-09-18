<?php

namespace App\Http\Resources;

use App\Models\Network;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserMapInfoResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'resume_link' => $this->resume_link,
            'description' => $this->description,
            'networks' => $this->networks->map(function ($item) {
                $network_id = $item->pivot->network_id;
                $network = Network::find($network_id);
                return [
                    'name' => $network->name,
                    'code' => $network->code,
                ];
            }),
            'smff_service_id' => $this->smff_service_id,
            'has_photo' => (bool) $this->has_photo,
            'primary_company' => $this->primaryCompany,
        ];
    }
}
