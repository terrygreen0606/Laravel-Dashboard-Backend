<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class VesselOsro extends Model
{
    protected $table = 'vessel_osro';
    protected $connection = "mysql_vrp";
    protected $primaryKey = 'id';
}
