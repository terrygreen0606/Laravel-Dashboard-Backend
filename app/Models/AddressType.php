<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddressType extends Model
{
    protected $table = 'address_types';
    protected $guarded = [];

    public function companyAddresses()
    {
        return $this->hasMany(CompanyAddress::class, 'address_type_id', 'id');
    }
}
