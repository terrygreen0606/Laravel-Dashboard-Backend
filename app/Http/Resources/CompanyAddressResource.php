<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyAddressResource extends JsonResource
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
            'address_type_id' => $this->address_type_id,
            'co' => $this->co,
            'street' => $this->street,
            'unit' => $this->unit,
            'city' => $this->city,
            'province' => $this->province,
            'state' => $this->state,
            'country' => $this->country,
            'zip' => $this->zip,
            'phone' => $this->phone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'zone_id' => $this->zone_id,
            'document_format' => $this->document_format,
            'zone_name' => $this->zone->name
        ];
    }
}
