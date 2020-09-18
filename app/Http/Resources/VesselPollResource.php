<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VesselPollResource extends JsonResource
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
             'type' => $this->type_id,
             'repeating_interval' => $this->repeating_interval_minutes,
             'last_run_date' => $this->last_run_date
        ];
    }
}
