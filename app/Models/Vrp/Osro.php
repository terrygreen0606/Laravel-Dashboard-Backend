<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class Osro extends Model
{
    protected $table = 'vrp_osro';
    protected $connection = "mysql_vrp";
    protected $guarded = [];
    public $incrementing = false;
    protected $primaryKey = 'vrp_osro_id';

    public function Vessels(){
        return $this->belongsToMany('App\Models\Vrp\Vessel', 'vessel_osro','vrp_osro_id', 'vessel_id');
    }
}
