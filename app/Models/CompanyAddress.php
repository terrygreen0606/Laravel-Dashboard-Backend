<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class CompanyAddress extends Model
{
    use Searchable;
    protected $table = 'company_addresses';
    protected $guarded = [];
    //protected $fillable = ['longitude','street','city','latitude'];

    public function toSearchableArray()
    {
        return array_only($this->toArray(), ['street', 'city', 'country', 'zip']);
    }

    public function addressType()
    {
        return $this->belongsTo(AddressType::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function zone()
    {
        return $this->hasOne(Zone::class, 'id', 'zone_id')->withDefault([
            'id' => -1,
            'name' => 'Outside US EEZ',
            'code' => 'NOT-TESTED'
        ]);
    }

    public static function boot()
    {
        parent::boot();
        self::updating(function ($model) {
            if ($model->isDirty('latitude') || $model->isDirty('longitude')) {
                //test zone and save
                $model->zone_id = getGeoZoneID($model->latitude, $model->longitude);
            }
        });
    }
}
