<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class NavRest extends Model
{
    protected $table = 'vrp_nav_rest';
    protected $connection = "mysql_vrp";
    protected $guarded = [];
    public $incrementing = false;
    protected $primaryKey = 'vrp_nav_rest_id';

    public function Vessels()
    {
        return $this->belongsToMany('App\Models\Vrp\Vessel', 'vessel_nav_rest', 'vrp_nav_rest_id', 'vessel_id');
    }

    public function Cotp()
    {
        return $this->belongsTo('App\Models\Vrp\Cotp', 'vrp_cotp_id', 'vrp_cotp_id');
    }
}
