<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;

class Vendor extends Model
{
    use Searchable;
    protected $table = 'vendors';
    protected $guarded = [];

    const TYPE_HANDM = 1;
    const TYPE_PANDI = 2;
    const TYPE_QI = 3;
    const TYPE_RESPONSE = 4;
    const TYPE_DAMAGE = 5;
    const TYPE_SOCIETY = 6;


    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return Arr::only($this->toArray(), ['name', 'shortname', 'company_email']);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function companies()
    {
        return $this->hasMany(Company::class, 'qi_id', 'id');
    }

    public function vessels()
    {
        return $this->belongsToMany(Vessel::class, 'vessels_vendors');
    }

    public function vesselIndex()
    {
        return $this->belongsToMany(VesselListIndex::class, 'vessels_vendors');
    }

    public function type()
    {
        return $this->hasOne(VendorType::class, 'id', 'vendor_type_id');
    }

    public function hm()
    {
        return $this->type()->where('name', 'H&M Insurer');
    }
}
