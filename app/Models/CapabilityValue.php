<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapabilityValue extends Model
{
    protected $table = 'capabilities_values';
    protected $guarded = [];


    protected $fillable = [
        'capabilities_id',
        'field_id',
        'value'
    ];

    public function capability()
    {
        return $this->belongsTo(Capability::class);
    }

    public function field()
    {
        return $this->hasOne(CapabilityField::class, 'id', 'field_id');
    }

}
