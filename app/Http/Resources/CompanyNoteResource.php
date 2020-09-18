<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyNoteResource extends JsonResource
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
            'note' => $this->note,
            'user' => $this->user->first_name . ' ' . $this->user->last_name,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i') . ' - (' . $this->created_at->diffForHumans() . ')' : '-//-'
        ];
    }
}
