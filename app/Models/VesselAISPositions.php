<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselAISPositions extends Model
{
    //
    protected $table = 'vessel_ais_positions';
    protected $guarded = [];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class, 'vessel_id');
    }

    public function type ()
    {
        print_r ($this);
        exit;
        return $this->vessel->hasOne(VesselType::class, 'id', 'vessel_type_id')->withDefault([
            'id' => -1,
            'name' => 'Not Defined',
            'ais_category_id' => null
        ]);
    }
}
