<?php

namespace App\Models\Vrp_Calcs;

use Illuminate\Database\Eloquent\Model;

class CountriesDjs extends Model
{
    //
    protected $connection = "mysql_vrp_calcs";
    protected $table = 'countries_djs';
    protected $primaryKey = 'id';
}
