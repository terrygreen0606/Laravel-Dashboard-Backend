<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavStatus extends Model
{
    protected $table = 'nav_statuses';
    protected $guarded = [];

    public function vessels()
    {
        return $this->hasMany(Vessel::class, 'ais_nav_status_id', 'status_id');
    }
}
