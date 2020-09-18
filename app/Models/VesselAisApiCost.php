<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VesselAisApiCost extends Model
{
    protected $table = 'vessel_ais_api_cost';
    protected $guarded = [];

    const PARTICULARS = 1;
    const PHOTOS = 2;
    const POS_TER_SIMPLE = 3;
    const POS_SAT_SIMPLE = 4;
    const POS_TER_EXTENDED = 5;
    const POS_SAT_EXTENDED = 6;
    const TRACK_TER_SIMPLE = 7;
    const TRACK_SAT_SIMPLE = 8;
    const TRACK_TER_EXTENDED = 9;
    const TRACK_SAT_EXTENDED = 10;
}
