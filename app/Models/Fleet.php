<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Fleet extends Model
{
    use Searchable;
    protected $table = 'fleets';
    protected $guarded = [];

    public function toSearchableArray()
    {
        return array_only($this->toArray(), ['name']);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'fleets_users');
    }

    public function vessels()
    {
        return $this->belongsToMany(Vessel::class, 'vessels_fleets');
    }
}
