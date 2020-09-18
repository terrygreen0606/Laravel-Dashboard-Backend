<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyContactShortResource extends JsonResource
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
            'id' => $this->id,
            'name' => $this->prefix . ' ' . $this->first_name . ' ' . $this->last_name . ($this->position !== null ? ' (' . $this->position . ')' : null)
        ];
    }
}
