<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class VrpCountryMapping extends Model
{
    protected $table = 'vrp_country_mapping';
    protected $connection = "mysql_vrp";
    protected $guarded = [];
}
