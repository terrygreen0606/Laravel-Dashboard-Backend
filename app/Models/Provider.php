<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Vessel;

class Provider extends Model
{
    protected $table = 'ais_providers';
    protected $guarded = [];

    public function vessels()
    {
        return $this->hasMany(Vessel::class, 'ais_provider_id', 'id');
    }
}
