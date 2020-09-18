<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Vessel;

class Zone extends Model
{
    protected $table = 'zones';
    protected $guarded = [];

    public function vessels()
    {
        return $this->hasMany(Vessel::class, 'zone_id', 'id');
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'zone_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'zone_id', 'id');
    }
}
