<?php

namespace App\Models;

use Laravel\Scout\Searchable;

class UserAddress extends BaseModel
{
    use Searchable;
    protected $table = 'user_address';
    protected $guarded = [];

    public function toSearchableArray()
    {
        return array_only($this->toArray(), ['street', 'city', 'country', 'zip']);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
