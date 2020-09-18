<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemComponent extends Model
{
    protected $table = 'system_components';
    protected $guarded = [];

		const USERS = 1;
		const SETTINGS = 2;
		const COMPANIES = 3;
		const VESSELS = 4;
		const MAP = 5;
		const FLEETS = 6;
		const VENDORS = 7;
		const SYSTEM_REPORTS = 8;
		const CLIENTS = 9;

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'system_component_id', 'id');
    }
}
