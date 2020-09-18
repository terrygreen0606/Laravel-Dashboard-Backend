<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class QIProviderChanges extends Model
{
    protected $table = 'vrp_qi_provider_changes';
    protected $connection = "mysql_vrp";
    protected $guarded = [];

    public function Plan(){
        return $this->hasOne('App\Models\Vrp\VrpPlan', 'id', 'plan_id');
    }
}
