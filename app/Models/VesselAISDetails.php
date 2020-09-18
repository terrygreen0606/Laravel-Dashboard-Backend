<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselAISDetails extends Model
{
    //
    protected $table = 'vessel_ais_details';
    protected $guarded = [];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
