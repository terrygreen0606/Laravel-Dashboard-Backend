<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $guarded = [];

    const ADMIN = 1;
    const DUTY_TEAM = 2;
    const QI_COMPANIES = 3;
    const COAST_GUARD = 4;
    const VESSEL_VIEWER = 5;
    const NAVY_NASA = 6;
    const COMPANY_PLAN_MANAGER = 7;
    
    public function users()
    {
        return $this->hasOne(User::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'role_id', 'id');
    }
}
