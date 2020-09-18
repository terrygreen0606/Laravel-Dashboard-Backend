<?php

namespace App\Http\Resources;

use App\Models\Capability;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyAddressMapResource extends JsonResource
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
            'text' => $this->company['name'],
            'company_id' => $this->company_id,
            'latlng' => [$this->latitude, $this->longitude],
            'primary_service' => $this->primary_service,
        ];
    }
}
