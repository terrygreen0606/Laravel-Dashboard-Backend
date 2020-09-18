<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\SystemComponent;
use Closure;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param $component
     * @param $permissions
     * @return mixed
     */
    public function handle($request, Closure $next, $component, ...$permissions)
    {
        //Check if the current user by id
        if ($request->route('id') == (int)$request->user()->id)
            return  $next($request);
        
        //Check current user by model binding
        if (isset($request->route('user')->id) && $request->route('user')->id == (int)$request->user()->id)
            return $next($request);

        //Get role id
        $roleID = $request->user()->role_id;
        
        //Get system_components of the endpoint requested
        $componentID = SystemComponent::where('code', $component)->first()->id;
        
        //Get permissions for each user role
        $reqPermissions = Permission::where([['role_id', $roleID], ['system_component_id', $componentID]])->pluck('permissions')->toArray();
        
        //Check if the current user have a role with the permission required
        foreach ($reqPermissions as $reqPermission) {
            $match = !array_diff($permissions, explode(',', $reqPermission));
            if ($match) {
                return $next($request);
            }
        }
        return abort(403, 'Forbidden Access.');
    }
}
