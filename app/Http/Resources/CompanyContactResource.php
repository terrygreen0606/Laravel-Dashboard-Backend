<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyContactResource extends JsonResource
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
            'name' => $this->prefix . ' ' . $this->first_name . ' ' . $this->last_name . ($this->position !== null ? ' (' . $this->position . ')' : null),
            'prefix' => $this->prefix,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'work_phone' => $this->work_phone,
            'aoh_phone' => $this->aoh_phone,
            'fax' => $this->fax,
            'edit' => 0,
            'types' => $this->contactTypes()->pluck('id')
        ];
    }
}
