<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyMapResource extends JsonResource
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
            'plan_number' => $this->plan_number,
            'addresses' => CompanyAddressMapResource::collection($this->addresses()->whereNotNull('latitude')->whereNotNull('longitude')->where([['latitude', '<>', 0], ['longitude', '<>', 0], ['latitude', '<>', ''], ['longitude', '<>', '']])->get())
        ];
    }
}
