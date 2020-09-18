<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselAisPoll extends Model
{
    protected $table = 'vessel_ais_poll';
    protected $guarded = [];


    protected $fillable = [
        'type_id',
        'created_user_id',
        'company_id',
        'status',
        'description',
        'repeating',
        'repeating_interval_minutes',
        'start_date',
        'last_run_date',
        'stop_date',
    ];

    public function vessels()
    {
        return $this->belongsToMany(Vessel::class, 'vessels_vessel_ais_poll');
    }

    public function networks()
    {
        return $this->belongsToMany(Network::class, 'vessels_vessel_ais_poll');
    }

    public function fleets()
    {
        return $this->belongsToMany(Fleet::class, 'vessels_vessel_ais_poll');
    }

}
