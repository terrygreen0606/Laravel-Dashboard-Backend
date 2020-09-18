<?php

namespace App\Models;

class VesselType extends BaseModel
{
    protected $table = 'vessel_types';
    protected $guarded = [];

    public function vessels()
    {
        return $this->hasMany(Vessel::class, 'vessel_type_id', 'id');
    }
}
