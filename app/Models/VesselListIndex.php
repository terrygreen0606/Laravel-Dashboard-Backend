<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;
use App\Models\VesselFleets;

class VesselListIndex extends Model
{
    use Searchable;
    protected $table = 'vessels_list_index';
    protected $guarded = [];


    public function toSearchableArray()
    {
        return Arr::only($this->toArray(), ['imo', 'mmsi', 'name']);
    }
    
    public function type()
    {
        return $this->hasOne(VesselType::class, 'id', 'vessel_type_id')->withDefault([
            'id' => -1,
            'name' => 'Not Defined',
            'ais_category_id' => null
        ]);
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'vessels_vendors', 
            'vessel_id', // Foreign key on users table...
            'vendor_id', // Foreign key on history table...
            'id', // Local key on suppliers table...
            'id' // Local key on users table...););
        );
    }

    public function fleets()
    {
        return $this->belongsToMany(Fleet::class, 'vessels_fleets', 
            'vessel_id', // Foreign key on users table...
            'fleet_id', // Foreign key on history table...
            'id', // Local key on suppliers table...
            'id' // Local key on users table...);
        );
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->withDefault([
            'id' => -1,
            'plan_number' => '',
            'name' => 'Unknown',
            'active' => 0,
        ]);
    }
    public function networks()
    {
        return $this->belongsToMany(Network::class, 'network_vessels', 
            'vessel_id', // Foreign key on users table...
            'network_id', // Foreign key on history table...
            'id', // Local key on suppliers table...
            'id' // Local key on users table...);
        );
    }
}
