<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselHistoricalTrack extends Model
{
    protected $table = 'vessels_historical_track';
    protected $guarded = [];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function lastPort()
    {
        return $this->hasOne(Port::class, 'id', 'last_port');
    }

    public function currentPort()
    {
        return $this->hasOne(Port::class, 'id', 'current_port');
    }

    public function nextPort()
    {
        return $this->hasOne(Port::class, 'id', 'next_port');
    }

    public function navStatus()
    {
        return $this->hasOne(AisStatus::class, 'status_id', 'ais_status_id')->withDefault([
            'status_id' => -1,
            'value' => 'Unknown'
        ]);
    }

}
