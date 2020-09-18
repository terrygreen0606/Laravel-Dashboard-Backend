<?php

namespace App\Models\Vrp_Calcs;

use Illuminate\Database\Eloquent\Model;

class RegionVesselsDjs extends Model
{
    //
    protected $connection = "mysql_vrp_calcs";
    protected $table = 'region_vessels_djs';
    protected $primaryKey = 'id';
}
