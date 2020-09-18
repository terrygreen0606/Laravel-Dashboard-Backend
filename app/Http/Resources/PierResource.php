<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PierResource extends JsonResource
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
            "ID"=> $this->id,
//            "WTWY_NAME"=> $this->WTWY_NAME,
            "LATITUDE1"=> $this->LATITUDE1,
            "LONGITUDE1"=> $this->LONGITUDE1

        ];
    }
}
