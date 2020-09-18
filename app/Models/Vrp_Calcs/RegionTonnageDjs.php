<?php

namespace App\Models\Vrp_Calcs;

use Illuminate\Database\Eloquent\Model;

class RegionTonnageDjs extends Model
{
    //
    protected $connection = "mysql_vrp_calcs";
    protected $table = 'region_tonnage_djs';
    protected $primaryKey = 'id';
}
