<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Ramsey\Uuid\Uuid;

class Sessions extends Model
{
    protected $primaryKey = 'session_id';

    protected $fillable = [
        "session_name"
    ];

    public function vrpPlans()
    {
        return $this->belongsToMany(
            VrpPlan::class,
            'session_vrp_plan',
            'session_id',
            'vrp_plan_id'
        )->withPivot('vrp_plan_status');
    }
}
