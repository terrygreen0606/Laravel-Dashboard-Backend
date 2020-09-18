<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VesselShortResource extends JsonResource
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
            'imo' => $this->imo,
            'official_number' => $this->official_number,
            'name' => $this->name,
            'active' => $this->active
        ];
    }
}
