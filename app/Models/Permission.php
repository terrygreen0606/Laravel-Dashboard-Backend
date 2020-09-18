<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $guarded = [];

    public function roles()
    {
        return $this->hasMany(Role::class, 'id', 'role_id');
    }

    public function components()
    {
        return $this->hasMany(SystemComponent::class, 'id', 'system_component_id');
    }
}
