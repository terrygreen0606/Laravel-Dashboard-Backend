<?php

namespace App\Models;


class VendorType extends BaseModel
{
    protected $table = 'vendor_types';
    protected $guarded = [];

    public function vendors()
    {
        return $this->hasMany(Company::class, 'vendor_type', 'id');
    }
}
