<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class VesselSmff extends Model
{
    protected $table = 'vessel_smff';
    protected $connection = "mysql_vrp";
    protected $primaryKey = 'id';
}
