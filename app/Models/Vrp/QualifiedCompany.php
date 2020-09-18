<?php

namespace App\Models\Vrp;

use Illuminate\Database\Eloquent\Model;

class QualifiedCompany extends Model
{
    protected $table = 'qualified_companies';
    protected $connection = "mysql_vrp";
    protected $guarded = [];

    public function QualifiedIndividuals()
    {
        return $this->hasMany('App\Models\Vrp\QualifiedIndividual', 'company_name', 'qualified_company_name');
    }
}
