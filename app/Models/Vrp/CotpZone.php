<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class CotpZone extends Model
{
    protected $table = 'vrp_cotp_zones';
    protected $connection = "mysql_vrp";
    protected $guarded = [];

    public function vrpPlan()
    {
        return $this->hasOne('App\Models\Vrp\VrpPlan', 'plan_number', 'vrp_plan_id');
    }

    public function cotp()
    {
        return $this->hasOne('App\Models\Vrp\Cotp', 'vrp_cotp_id', 'vrp_cotp_id');
    }
}
