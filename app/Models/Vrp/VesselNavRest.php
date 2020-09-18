<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class VesselNavRest extends Model
{
    protected $table = 'vessel_nav_rest';
    protected $connection = "mysql_vrp";
    protected $primaryKey = 'id';
}
