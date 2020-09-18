<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    protected $table = 'networks';
    protected $guarded = [];
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'network_companies');
    }

    public function aisPoll()
    {
        return $this->hasMany(VesselAisPoll::class, 'vessels_vessel_ais_poll');
    }
}
