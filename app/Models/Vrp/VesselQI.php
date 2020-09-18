<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class VesselQI extends Model
{
    protected $table = 'vessel_qi';
    protected $connection = "mysql_vrp";
    protected $primaryKey = 'id';
}
