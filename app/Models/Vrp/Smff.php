<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class Smff extends Model
{
    protected $table = 'vrp_smff';
    protected $connection = "mysql_vrp";
    protected $guarded = [];
    public $incrementing = false;
    protected $primaryKey = 'vrp_smff_id';

    public function Vessels()
    {
        return $this->belongsToMany('App\Models\Vrp\Vessel', 'vessel_smff', 'vrp_smff_id', 'vessel_id');
    }
}
