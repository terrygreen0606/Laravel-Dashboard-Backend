<?php

namespace App\Helpers;

use App\Models\Permission;
use App\Models\SystemComponent;
use Illuminate\Support\Facades\Auth;

class PermissionHelper {
    public static function hasVrpAccess ($component) {
        $roleID = Auth::user()->role_id;
        $componentID = SystemComponent::where('code', $component)->first()->id;
        $req_permissions = Permission::where('role_id', $roleID)->where('system_component_id', $componentID)->pluck('permissions')->toArray();
        foreach ($req_permissions as $req_permission) {
            $match = !array_diff(['V'], explode(',', $req_permission));
            if ($match) {
                return true;
            }
        }
        return false;

    }
}