<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\SystemComponent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['username', 'password']);
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['type' => 'error', 'message' => 'Incorrect username or password.'], 401);
        }
        if (!User::where('username', request('username'))->first()->active) {
            return response()->json(['type' => 'warning', 'message' => 'That user is inactive.'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json([
            'user' => auth()->user(),
            'role' => auth()->user()->roles()->first(),
            // 'permissions' => $this->getPermissions()
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user(),
            'role' => auth()->user()->roles()->first(),
            // 'permissions' => $this->getPermissions(),
            'message' => 'Successfully logged in'
        ]);
    }

    // Not Using Now!!!!!
    protected function getPermissions()
    {

        $roleID = auth()->user()->role_id;
        $components = SystemComponent::select('id', 'name', 'code')->get();
        $permissions_data = [];
        $num_permissions = 0;
        foreach ($components as $component) {
            $permissions = Permission::where([['role_id', $roleID], ['system_component_id', $component->id]])->get();
            if (count($permissions)) {
                $merged_permissions = [];
                foreach ($permissions as $permission) {
                    $merged_permissions = array_unique(array_merge($merged_permissions, array_filter(explode(',', $permission->permissions))), SORT_REGULAR);
                }
                $permissions_data[$component->code] = $merged_permissions;
                if (count($merged_permissions)) {
                    $num_permissions++;
                }
            } else {
                $permissions_data[$component->code] = [];
            }
        }
        $has_permissions = $num_permissions ? true : false;

        return [
            'has' => $has_permissions,
            'data' => $permissions_data
        ];
    }
}
