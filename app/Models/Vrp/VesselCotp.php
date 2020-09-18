<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class VesselCotp extends Model
{
    protected $table = 'vessel_cotp';
    protected $connection = "mysql_vrp";
    protected $primaryKey = 'id';
}
