<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Http\Resources\UserNoteResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserShortResource;
use App\Http\Resources\UserShowResource;
use App\Models\Network;
use App\Notifications\PasswordChangeSuccess;
use App\Notifications\PasswordResetRequest;
use App\Models\PasswordReset;
use App\Models\SMFFCapability;
use App\Models\User;
use App\Models\Role;
use App\Models\Vendor;
use App\Models\Vessel;
use App\Models\Fleet;
use App\Models\Vrp\Smff;
use App\Models\Vrp_Calcs\CountriesDjs;
use App\Models\Vrp_Calcs\PlanPreparerDjs;
use App\Models\Vrp_Calcs\RegionShipsDjs;
use App\Models\Vrp_Calcs\RegionTonnageDjs;
use App\Models\Vrp_Calcs\RegionVesselsDjs;
use App\Models\Vrp_Calcs\TankNontankDjs;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use phpDocumentor\Reflection\Types\This;

class DashboardController extends Controller
{
    const ADMIN_PROVIDER = 12;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function home()
    {
    	$company = Company::where('vendor_active',0)->count();
    	$individuals = User::count();
    	$vessels = Vessel::count();
    	$clients = 0;
    	$vendors = Company::where('vendor_active',1)->count();
        $fleets = Fleet::count();
        $topCountries = CountriesDjs::orderBy('percent', 'DESC')->limit(10)->get();
        $planPreparer = PlanPreparerDjs::all();
        $regionShips = RegionShipsDjs::all();
        $regionTonnage = RegionTonnageDjs::all();
        $regionVessels = RegionVesselsDjs::all();
        $planType = TankNontankDjs::all();

    	$data = array("companies"=>$company,
                        "individuals" => $individuals,
                        "vessels" => $vessels,
                        "clients" => $clients,
                        "vendors" => $vendors,
                        "fleets" => $fleets,
                        "top_countries" => $topCountries,
                        "plan_preparer" => $planPreparer,
                        "regionShips" => $regionShips,
                        "regionTonnage" => $regionTonnage,
                        "regionVessels" => $regionVessels,
                        "planType" => $planType,
    				);
    	return json_encode(['status' => true,'result' => $data], 200);
    }

    public function test() {
        $credentials = ['username' => 'adminmaster',
            'password' => 'Test123'
        ];
        return die(phpinfo());
        return Hash::make('Test123');
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['type' => 'error', 'message' => 'Incorrect username or password.'], 401);
        }
    }
}
