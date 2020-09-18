<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserCompanyResource extends JsonResource
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
            'parent' => $this->operatingCompany ? $this->operatingCompany->name : null,
            'name' => $this->name,
            'active' => (boolean)$this->active,
            'location' => count($this->primaryAddress) ? $this->primaryAddress[0]->country : 'N/A'
        ];
    }
}
