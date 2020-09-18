<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class Cotp extends Model
{
    protected $table = 'vrp_cotp';
    protected $connection = "mysql_vrp";
    protected $guarded = [];
    public $incrementing = false;
    protected $primaryKey = 'vrp_cotp_id';

    public function Vessels(){
        return $this->belongsToMany('App\Models\Vrp\Vessel', 'vessel_cotp','vrp_cotp_id', 'vessel_id');
    }

    public function NavRest(){
        return $this->hasMany('App\Models\Vrp\NavRest', 'vrp_cotp_id', 'vrp_cotp_id');
    }
}
