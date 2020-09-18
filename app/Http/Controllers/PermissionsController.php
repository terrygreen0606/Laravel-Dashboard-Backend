<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemComponent;
use Illuminate\Http\Request;

class PermissionsController extends Controller
{
    public function getRoles()
    {
        return Role::select('id', 'name', 'code')->where('code', '<>', 'ADMIN')->get();
    }

    public function getComponents()
    {
        return SystemComponent::select('id', 'name', 'code')->get();
    }

    public function getComponentsByRole($roleId)
    {
        $components = SystemComponent::select('id', 'name', 'code')->get();
        foreach ($components as $component) {
            $permissions = Permission::where([['role_id', $roleId], ['system_component_id', $component->id]])->first();
            if ($permissions) {
                $component->permissions = explode(',', $permissions->permissions);
            } else {
                $component->permissions = [];
            }
        }
        return $components;
    }

    public function updatePermissions()
    {
        foreach (\request('components') as $component) {
            $arr_permissions = array_filter($component['permissions']);
            $permissions = count($arr_permissions) ? implode(',', $arr_permissions) : null;
            Permission::updateOrCreate(['role_id' => \request('roleId'), 'system_component_id' => $component['id']], [
                'permissions' => $permissions
            ]);
        }
        return response()->json(['message' => 'Success. Permissions updated.']);
    }
}
