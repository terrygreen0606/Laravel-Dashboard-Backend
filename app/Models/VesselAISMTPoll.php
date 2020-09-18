<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselAISMTPoll extends Model
{
    //
    protected $table = 'vessel_ais_mt_poll';
    protected $primaryKey = 'id';

    protected $fillable = [
        'vessel_id',
        'imo',
        'satellite',
        'extended',
        'interval',
        'last_update',
        'created_at',
        'updated_at',
    ];

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }
}
