<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->code,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'iso3' => $this->iso3,
            'continent_code' => $this->continent_code
        ];
    }
}
