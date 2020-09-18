<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class QualifiedIndividual extends Model
{
    protected $table = 'qualified_individuals';
    protected $connection = "mysql_vrp";
    protected $guarded = [];
    public $incrementing = false;
    protected $primaryKey = 'vrp_qi_id';

    public function VrpPlan()
    {
        return $this->hasMany('App\Models\Vrp\VrpPlan', 'qualified_individual_id', 'id');
    }

    public function QualifiedCompany()
    {
        return $this->belongsTo('App\Models\Vrp\QualifiedCompany', 'company_name', 'qualified_company_name');
    }

    public function Vessels()
    {
        return $this->belongsToMany('App\Models\Vrp\Vessel', 'vessel_qi', 'vrp_qi_id', 'vessel_id');
    }
}
