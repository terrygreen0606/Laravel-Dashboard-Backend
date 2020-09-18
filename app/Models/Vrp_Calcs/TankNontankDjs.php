<?php

namespace App\Models\Vrp_Calcs;

use Illuminate\Database\Eloquent\Model;

class TankNontankDjs extends Model
{
    //
    protected $connection = "mysql_vrp_calcs";
    protected $table = 'tank_nontank_djs';
    protected $primaryKey = 'id';
}
