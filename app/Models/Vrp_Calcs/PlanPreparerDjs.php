<?php

namespace App\Models\Vrp_Calcs;

use Illuminate\Database\Eloquent\Model;

class PlanPreparerDjs extends Model
{
    //
    protected $connection = "mysql_vrp_calcs";
    protected $table = 'plan_preparer_djs';
    protected $primaryKey = 'id';
}
