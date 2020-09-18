<?php

namespace App\Models\Vrp_Calcs;

use Illuminate\Database\Eloquent\Model;

class RegionShipsDjs extends Model
{
    //
    protected $connection = "mysql_vrp_calcs";
    protected $table = 'region_ships_djs';
    protected $primaryKey = 'id';
}
