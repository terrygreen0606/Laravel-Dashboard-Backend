<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Http\Resources\UserNoteResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserShortResource;
use App\Http\Resources\UserShowResource;
use App\Http\Resources\CompanyContactTypesResource;
use App\Models\Network;
use App\Notifications\PasswordChangeSuccess;
use App\Notifications\PasswordResetRequest;
use App\Models\PasswordReset;
use App\Models\Capability;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use App\Models\ContactType;
use App\Models\ContactHasType;
use App\Models\Vessel;
use App\Models\CompanyUser;
use App\Models\UserAddress;
use App\Models\Country;
use App\Models\TrackChange;
use App\Models\ChangesTableName;
use App\Models\Action;
use http\Env\Response;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\This;
use App\Helpers\GeoHelper;
use App\Mail\SendMail;

class UserController extends Controller
{
    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getAll(Request $request)
    {
        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'u.updated_at';
        if ($sort == "name") $sort = "last_name";
        if ($sort == "company") $sort = "c.name";
        if ($sort == "email") $sort = "u.email";
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';
        DB::enableQueryLog();
        $query = $request->get('query');
        $baseTable = new User;
        $userTable = $this->getUserModel();
        $userTableName = $baseTable->table();
        if (!empty($query) && strlen($query) > 2) {
            $uids_name = User::whereRaw("CONCAT(first_name, ' ', last_name) LIKE CONCAT('%', ?, '%')", $query)->get('id')->pluck('id')->toArray();
      // return print_r(DB::getQueryLog(), true);
            $uids = array_merge($uids_name, User::search($query)->get('id')->pluck('id')->toArray());
            $userModel = $userTable
                    ->select(DB::raw(User::FIELDS_SELECT))
                ->from($userTableName . ' AS u')
                ->leftJoin('companies_users AS cu', 'u.id', '=', 'cu.user_id')
                ->leftJoin('companies AS c', 'c.id', '=', 'cu.company_id')
                ->leftJoin('capabilities AS us', 'u.smff_service_id', '=', 'us.id')
                ->whereRaw('(us.status IS NULL OR us.status=1)')
                ->whereIn('u.id', $uids)->groupBy('u.id')
                ->orderByRaw("(us.id IS NOT NULL) AND CONCAT(first_name, last_name) LIKE CONCAT('%', ?, '%') DESC", $query)
                ->orderByRaw("CONCAT(first_name, ' ', last_name) LIKE CONCAT('%', ?, '%') DESC", $query)
                ->orderByRaw("(us.id IS NOT NULL) AND u.email LIKE CONCAT('%', ?, '%') DESC", $query)
                ->orderByRaw("u.email LIKE CONCAT('%', ?, '%') DESC", $query)
                ->orderBy($sort, $sortDir);
        } else {
            $userModel = $userTable
                ->select(DB::raw(User::FIELDS_SELECT))
                ->from($userTableName . ' AS u')
                ->leftJoin('companies_users AS cu', 'u.id', '=', 'cu.user_id')
                ->leftJoin('companies AS c', 'c.id', '=', 'cu.company_id')
                ->leftJoin('capabilities AS us', 'u.smff_service_id', '=', 'us.id')
                ->whereRaw('(us.status IS NULL OR us.status=1)')->groupBy('u.id')
                ->orderBy($sort, $sortDir);
        }
        $per_page = request('per_page') == -1  ? count($this->staticSearch($userModel, \request('staticSearch'))->get()) : request('per_page');
        $users = $this->staticSearch($userModel, \request('staticSearch'))->paginate($per_page);
       //return print_r(DB::getQueryLog(), true);
        return UserResource::collection($users);
    }

    private function staticSearch($model, $staticSearch)
    {

        if ($staticSearch['active'] !== -1) {
            $model = $model->where('active', (boolean)$staticSearch['active']);
        }

        if (array_key_exists('resource_provider', $staticSearch) && $staticSearch['resource_provider'] !== -1) {
            if ($staticSearch['resource_provider']) {
                $model = $model->whereRaw('u.smff_service_id IS NOT NULL OR c.smff_service_id IS NOT NULL');
            } else {
                $model = $model->whereRaw('u.smff_service_id IS NULL AND c.smff_service_id IS NULL');
            }
        }

        if (count($staticSearch['companies'])) {
            $model = $model->whereNotNull('c.id')->whereIn('c.id', $staticSearch['companies']);
        }

        if ($staticSearch['role']) {
            $model = $model
                ->where('role_id', $staticSearch['role']);
        }

        return $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return UserResource::collection($this->getUserModel()->with('companies:id,name,active')->get());
    }

    public function indexShortId($id)
    {
        return UserShortResource::collection(User::where('id', $id)->get())[0];
    }

    public function indexShortByCompany($id)
    {
        return UserShortResource::collection(User::where('company_id', $id)->get());
    }

    // Not Using Right Now
    public function getPOCs()
    {
        return UserShortResource::collection($this->getUserModel(request('id'))->get());
    }

    public function getAllUsersName()
    {
        $users = User::where('active', 1)->orderBy('last_name')->get();

        $results = [];
        foreach($users as $user)
        {
            if($user->roles()->first()) {
                $results[] = [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name . ' :' . $user->roles()->first()->name,
                ];
            }

        }

        return response()->json(['message' => 'All users list','users'=>$results]);

        $getAllUsersName = User::select('id',DB::raw('CONCAT(first_name," ", last_name) AS name'))
                            ->where('active',1)->get();

        return response()->json(['message' => 'All users list','users'=>$getAllUsersName]);
    }

    public function assignMultipleUsers(Request $request)
    {
        $user_ids = request('user.individual_ids');
        foreach ($user_ids as $key => $value) {
        User::where('id',$value)
            ->update(['primary_company_id' => request('user.primary_company_id')]);
        }
        return response()->json(['message' => 'Company Added Successfully']);
    }

    public function storePhoto(User $user, Request $request)
    {
        $this->validate($request, [
            'file' => [
                'mimes:png,jpg,jpeg'
            ]
        ]);

        $frect = $request->file('file_rect');
        $fsqr = $request->file('file_sqr');

        $image_rect = Image::make($frect->getRealPath());
        $image_sqr = Image::make($fsqr->getRealPath());

        $directory = 'pictures/individuals/' . $user->id . '/';

        $name1 = 'cover_rect.jpg';
        $name2 = 'cover_sqr.jpg';

        if (
            Storage::disk('gcs')->put($directory . $name2, (string)$image_sqr->encode('jpg'), 'public') &&
            Storage::disk('gcs')->put($directory . $name1, (string)$image_rect->encode('jpg'), 'public')
        ) {
            $user->has_photo = true;
            $user->save();
            return response()->json(['message' => 'Picture uploaded.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function destroyPhoto(User $user)
    {
        $directory = 'pictures/individuals/' . $user->id . '/';
        if (
            Storage::disk('gcs')->delete($directory . 'cover_rect.jpg') &&
            Storage::disk('gcs')->delete($directory . 'cover_sqr.jpg')
        ) {
            $user->has_photo = false;
            $user->save();
            return response()->json(['message' => 'Picture deleted.']);
        }
        return response()->json(['message' => 'Can not delete a company photo.']);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Exception
     */
    public function store(Request $request)
    {
        if (!request('permitted')) {
            if ($request->has('email') && User::where('email', request('email'))->first()) {
                return response()->json(['success'=> false, 'message' => 'The Email already exists.']);
            }
        }
        $user = new User();
        $user->first_name = request('first_name');
        $user->last_name = request('last_name');
        $user->email = request('email');
        $user->primary_company_id = request('company');
        $user->title = request('title');
        $user->suffix = request('suffix');
        $user->mobile_number = request('mobileNumber');
        $user->work_phone = request('work_phone');
        $user->aoh_phone = request('aoh_phone');
        $user->fax = request('fax');
        $user->alternate_email = request('alternate_email');
        $user->home_number = request('homeNumber');
        $user->occupation = request('occupation');
        $user->username = request('email');
        $user->password = Hash::make(request('password'));
        $user->role_id = request('role');

        if ($user->save()) {
            $userId = $user->id;
            $address = $user->address()->create([
                'user_id' => $userId,
                'street' => $request->input('street'),
                'unit'   => $request->input('unit'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'country' => $request->input('country'),
                'zip' => $request->input('zip'),
                'phone' => $request->input('mobileNumber')
            ]);

            $addressData = $request->all();

            if ($addressData['street'] || $addressData['city']) {
                $geocoder = app('geocoder')->geocode(request('street') . ' ' . request('city') . ' ' . request('state') . ' ' . request('country') . ' ' . request('zip'))->get()->first();
                if ($geocoder) {
                    $coordinates = $geocoder->getCoordinates();
                    $address->latitude = $coordinates->getLatitude();
                    $address->longitude = $coordinates->getLongitude();
                    $address->save();
                }
            }

            if ($request->input('comments')) {
                $user->userNotes()->create([
                    'note' => $request->input('comments'),
                    'note_type' => 1,
                    'creator_id' => Auth::user()->id
                ]);
            }

            $user->contactTypes()->attach(request('type'));

            if(request('company')) {
                CompanyUser::create([
                    'user_id' => $userId,
                    'company_id' => request('company'),
                ]);
            }

            $userIds = [];
            $userIds[] = $user->id;
            $ids = '';
            foreach($userIds as $userId)
            {
                $ids .= $userId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 1,
                'action_id' => 1,
                'count' => 1,
                'ids' => $ids,
            ]);
            // $data = array(
            //     'subject' => 'Notification',
            //     'message' => 'You have a account for cdt app.',
            // );
            // Mail::to(request('email'))->send(new SendMail($data));

            return response()->json(['success' => true, 'message' => 'User added.', 'id' => $user->id]);
        }
        return response()->json(['success' => false, 'message' => 'Something unexpected happened.']);
    }

    // Get Duplicate Email
    public function getDuplicateEmail($email)
    {
        if (User::where('email', $email)->first()) {
            return response()->json(['success' => false]);
        } else {
            return response()->json(['success' => true]);
        }
    }

    public function contactTypes()
    {
        return CompanyContactTypesResource::collection(ContactType::all());
    }

    /**
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(User $user)
    {
        $user->active = !$user->active;
        if ($user->save()) {
            return response()->json(['message' => 'Status changed.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function show($id)
    {
        $baseTable = new User;
        $userTable = $this->getUserModel();
        $userTableName = $baseTable->table();

        $userModel = $userTable
            ->select(DB::raw(User::FIELDS_SELECT))
            ->from($userTableName . ' AS u')
            //->leftJoin('companies_users AS cu', 'u.id', '=', 'cu.user_id')
            ->leftJoin('companies AS c', 'c.id', '=', 'u.primary_company_id')
            ->leftjoin('capabilities AS us', 'u.smff_service_id', '=', 'us.id')
            //->whereRaw('(us.status IS NULL OR us.status=1 OR us.status)')
            ->where('u.id', $id);

        return UserShowResource::collection($userModel->get());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!request('user.permitted')) {
            if ($request->has('user.email') && $user->email != request('user.email')) {
                if (User::where('email', request('user.email'))->count()) {
                    return response()->json(['success' => false, 'message' => 'The Email already exists.']);
                }
            }
            if ($request->has('user.username') && $user->username != request('user.username')) {
                if (User::where('username', request('user.username'))->count()) {
                    return response()->json(['success' => false, 'message' => 'Username already exists.']);
                }
            }
        }

        if ($request->has('user.title')) $user->title = request('user.title');
        if ($request->has('user.first_name')) $user->first_name = request('user.first_name');
        if ($request->has('user.last_name')) $user->last_name = request('user.last_name');
        if ($request->has('user.suffix')) $user->suffix = request('user.suffix');
        if ($request->has('user.username')) $user->username = request('user.username');
        if ($request->has('user.email')) $user->email = request('user.email');
        if ($request->has('user.work_phone')) $user->work_phone = request('user.work_phone');
        if ($request->has('user.aoh_phone')) $user->aoh_phone = request('user.aoh_phone');
        if ($request->has('user.fax')) $user->fax = request('user.fax');
        if ($request->has('user.alternate_email')) $user->alternate_email = request('user.alternate_email');
        if ($request->has('user.primary_company_id')) $user->primary_company_id = request('user.primary_company_id');
        if ($request->has('user.role_id')) $user->role_id = request('user.role_id');
        if ($request->has('user.active')) $user->active = (bool)request('user.active');
        if ($request->has('user.type_ids')){
            $type_ids = request('user.type_ids');
            $user->contactTypes()->detach();
            foreach($type_ids as $type_id){
                $user->contactTypes()->attach($type_id);
            }
        }
        if ($request->has('user.companies')){
            $companies =  request('user.companies');
            $user->companies()->detach();
            foreach ($companies as $company){
                $user->companies()->attach($company);
            }
        }
        if ($request->has('user.home_number')) $user->home_number = request('user.home_number');
        if ($request->has('user.mobile_number')) $user->mobile_number = request('user.mobile_number');
        if ($request->has('user.occupation')) $user->occupation = request('user.occupation');
        if ($request->has('user.resume_link')) $user->resume_link = request('user.resume_link');
        if ($request->has('user.description')) $user->description = request('user.description');
        if (request('password.change') && request('password.valid')) {
            $this->validate($request, [
                'password.password' => 'required|min:6'
            ]);

           $user->password = Hash::make(request('password.password'));
        }

        if ($user->save()) {
            if ($request->has('user.type_ids')) $user->contactTypes()->sync(request('user.type_ids'));

            $userIds = [];
            $userIds[] = $id;
            $ids = '';
            foreach($userIds as $userId)
            {
                $ids .= $userId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 1,
                'action_id' => 3,
                'count' => 1,
                'ids' => $ids
            ]);
            return response()->json(['success' => true, 'message' => 'User saved.']);
        }

        return response()->json(['success' => false, 'message' => 'Something unexpected happened.']);
    }

     public function getUserName(Request $request){
        if (User::where('username',request('user'))->count()) {
                return response()->json(['message' => 'The username exists.','status'=>409]);
        }else{
            return response()->json(['message' => 'The username does not exists.','status'=>200]);
        }
    }

    public function quickUpdate(Request $request, $id)
    {
        $this->validate($request, [
            'user.first_name' => 'required'
        ]);

        $user = User::find($id);
        $user->first_name = request('user.first_name');
        $user->last_name = request('user.last_name');
        $user->company_id = request('user.company_id');

        if ($user->save()) {
            $user->roles()->sync(request('user.roles_ids'));
            return response()->json(['message' => 'User saved.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function resumeUpdate(Request $request, $id)
    {
        $this->validate($request, [
            'resume_link' => 'required'
        ]);

        $user = User::find($id);
        $user->resume_link = request('resume_link');

        if ($user->save()) {
            return response()->json(['message' => 'User resume_link field updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            if($user->delete()) {
                if(UserAddress::where('user_id', $id)->first()) {
                    UserAddress::where('user_id', $id)->delete();
                }
                if(CompanyUser::where('user_id', $id)->first()) {
                    CompanyUser::where('user_id', $id)->delete();
                }
                if(ContactHasType::where('contact_id', $id)->first()) {
                    ContactHasType::where('contact_id', $id)->delete();
                }

                $userIds = [];
                $userIds[] = $id;
                $ids = '';
                foreach($userIds as $userId)
                {
                    $ids .= $userId.',';
                }
                $ids = substr($ids, 0, -1);
                TrackChange::create([
                    'changes_table_name_id' => 1,
                    'action_id' => 2,
                    'count' => 1,
                    'ids' => $ids
                ]);
                return response()->json(['message' => 'User deleted.']);
            }
            return response()->json(['message' => 'Could not delete user.']);
        }

        return response()->json(['message' => 'No user found.'], 404);
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getBySearch()
    {
       // echo "<pre>";print_r(request('query'));die;where('text', 'LIKE', '%' . $term . '%')

        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $search_string = explode(' ',request('query'));
        if(count($search_string)>1){
        	$uids = User::where('first_name','like','%'.$search_string[0].'%')
        		    ->where('last_name','like','%'.$search_string[1].'%')
        			->get('id');
        }else{
        	$uids = User::search(request()->query('query'))->get('id');
        }


        //$uids = User::where('text', 'LIKE', '%' . request()->query('query') . '%')->get('id');
        $ids = array();
        foreach ($uids as $u) {
            $ids[] = $u->id;
        }
        $role_type =  request()->query('searchType');
        if($role_type)
        {
            $user_code = ($role_type == 7)?'POC':(($role_type == 2)?'USER':'');
            $users = $this->staticSearch($this->getUserModel()->whereIn('id', $ids)->whereHas('roles', static function ($q) use ($user_code,$ids) {
                $q->where('code', $user_code);
            }), \request('staticSearch'))->paginate($per_page);
            return UserResource::collection($users);
        }
        else
        {
            //echo "<pre>";print_r(request()->query('query'));die;
           $users = $this->staticSearch($this->getUserModel()->whereIn('id', $ids), \request('staticSearch'))->paginate($per_page);
        }
        return UserResource::collection($users);
    }

    // @todo: for removing smff data from user's who don't have smff data permission from backend
    private function getUserModel ($company_id = null) {
        $role_id = Auth::user()->role_id;
        if ($role_id == 7) { // Company Plan Manager
            return User::whereIn('primary_company_id', Auth::user()->companies()->pluck('id'));
        } else if ($role_id == 6) { // Navy / Nasa
            return User::whereIn('primary_company_id', Company::where('networks_active', 1)->orWhere('smff_service_id', '<>', 0)->pluck('id'));
        }

        if ($company_id) {
            return User::where('primary_company_id', $company_id);
        } else {
            return new User;
        }
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getByOrder()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');

        $role_type =  request()->query('role_type');
        if($role_type)
        {
            $user_code = ($role_type == 7)?'POC':(($role_type == 2)?'USER':'');
            $users = $this->staticSearch($this->getUserModel()->whereHas('roles', static function ($q) use ($user_code) {
                $q->where('code', $user_code);
            }), \request('staticSearch'))->paginate($per_page);
            return UserResource::collection($users);
        }
        $users = $this->staticSearch($this->getUserModel()->orderBy($sortBy, $direction), \request('staticSearch'))->paginate($per_page);
        return UserResource::collection($users);
    }

    //SMFF Capabilities
    public function storeSMFF($id)
    {
        $user = User::find($id);
        $smff = null;

        if ($user) {
            if ($user->smff_service_id) {
                $smff = Capability::find($user->smff_service_id);
                if (empty($smff)) {
                    // error???
                } else {
                    $smff->status = 1; // undelete
                    $smff->save();
                }
            } else {
                if ($user->company && $user->company->smffCapability) {
                    $smff_copy = $user->company->smffCapability->replicate();
                    $user->smff_service_id = Capability::create($smff_copy->toArray())->id;
                } else {
                    $user->smff_service_id = Capability::create()->id;
                }
            }
            return $user->save() ? response()->json(['message' => 'SMFF Capabilities created.']) : response()->json(['message' => 'Could not create SMFF Capabilities.']);
        }

        return response()->json(['message' => 'No Individual found.'], 404);
    }


    public function showSMFF($id)
    {
        $user = User::where('id', $id)->first();
        $smff =  $user->smff();
        $networks = $user->networks;
        return response()->json([
            'user' => $user->smff_service_id,
            'smff' => $smff,
            'networks' => $networks->pluck('code'),
            'serviceItems' => Capability::primaryServiceAvailable()
        ]);
    }

    public function updateSMFF(Request $request, $id)
    {
        $user = User::find($id);
        $capabilities = Capability::find($user->smff_service_id);
        if (!$capabilities) {
            $this->storeSMFF($id);
            $user = User::find($id);
            $capabilities = Capability::find($user->smff_service_id);
        }
        $smffFields = request('smff');
        if (!$capabilities->updateValues(
            isset($smffFields['primary_service']) ? $smffFields['primary_service'] : null,
            isset($smffFields['notes']) ? $smffFields['notes'] : null,
            $smffFields)) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        return response()->json(['message' => 'User SMFF Capabilities updated.']);
    }


    public function updateNetwork(Request $request, $id)
    {
        $user = User::find($id);
        $network_ids = Network::whereIn('code', request('networks'))->pluck('id');
        if ($user->networks()->sync($network_ids)) {
            return response()->json(['message' => 'Individual Network Membership Updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function destroySMFF($id)
    {
        $user = User::find($id);
        if ($user) {
            $smff = Capability::find($user->smff_service_id);
            if (!empty($smff)) {
                $smff->status = 0;
            }
            return empty($smff) || $smff->save() ? response()->json(['message' => 'SMFF Capabilities deleted.']) : response()->json(['message' => 'Could not delete SMFF Capabilities.']);
        }

        return response()->json(['message' => 'No Individual found.'], 404);
    }

    //Notes
    public function addNote(User $user, Request $request)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        $this->validate($request, [
            'note_type' => 'required',
            'note' => 'required'
        ]);

        $note = $user->userNotes()->create([
            'note_type' => request('note_type'),
            'note' => request('note'),
            'creator_id' => Auth::id()
        ]);

        if ($note) {
            return response()->json(['message' => 'Note added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function getNotes(User $user)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        return UserNoteResource::collection($user->userNotes()->get());
    }

    public function destroyNote(User $user, $id)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        if ($user->userNotes()->find($id)->delete()) {
            return response()->json(['message' => 'Note deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }


    //Documents
    public function getFiles(User $user, $type)
    {
        $files = [];
        $directory = 'users/' . $user->id . '/Documents/' . $type . '/';
        $filesInFolder = Storage::disk('gcs')->files($directory);
        foreach ($filesInFolder as $path) {
            $files[] = [
                'name' => pathinfo($path)['basename'],
                'size' => $this->formatBytes(Storage::disk('gcs')->size($directory . pathinfo($path)['basename'])),
                'ext' => pathinfo($path)['extension'] ?? null
            ];
        }
        return $files;
    }

    public function destroyFile(User $user, $type, $fileName)
    {
        $directory = 'users/' . $user->id . '/Documents/' . $type . '/';
        if (Storage::disk('gcs')->delete($directory . $fileName)) {
            return response()->json(['message' => 'File deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    // bulk destroy
    public function bulkDestroyFile(Request $request, User $user, $type)
    {
        $removeData = $request->all();
        for($i = 0; $i < count($removeData); $i ++) {
            $directory = 'users/' . $user->id . '/Documents/' . $type . '/';
            Storage::disk('gcs')->delete($directory . $removeData[$i]['name']);
        }
        return response()->json(['message' => 'File deleted.']);
    }
    // end bulk destroy

    public function downloadFile(User $user, $type, $fileName)
    {
        $directory = 'users/' . $user->id . '/Documents/' . $type . '/';
        $url = Storage::disk('gcs')->temporaryUrl(
            $directory . $fileName, now()->addMinutes(5)
        );
        return response()->json(['message' => 'Download started.', 'url' => $url]);
    }

    public function downloadFileForce(User $user, $type, $fileName)
    {
        $directory = 'users/' . $user->id . '/Documents/' . $type . '/';
        return response()->streamDownload(function() use ($directory, $fileName) {
            echo Storage::disk('gcs')->get($directory . $fileName);
        }, $fileName, [
                'Content-Type' => 'application/octet-stream'
            ]);
    }

    public function uploadFile(User $user, $type, Request $request)
    {
        $fileName = $request->file->getClientOriginalName(); //preg_replace("/[^0-9A-Za-z\. \-\_]/", "-", $request->file->getClientOriginalName());
        $directory = 'users/' . $user->id . '/Documents/' . $type . '/';

        if (Storage::disk('gcs')->exists($directory . $fileName)) {
            $fileName = date('m-d-Y_h:ia_') . $fileName;
        }
        if (Storage::disk('gcs')->putFileAs($directory, \request('file'), $fileName)) {
            if($type == 'cv') {
                $user = User::find($user->id);
                $user->resume_link = $fileName;
                $user->save();
            }
            return response()->json(['message' => 'File uploaded.', 'filename' => $fileName]);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    private function formatBytes($size, $precision = 2)
    {
        if ($size > 0) {
            $size = (int)$size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');

            return round(1024 ** ($base - floor($base)), $precision) . $suffixes[floor($base)];
        }

        return $size;
    }

    public function sendPasswordResetEmail(User $user)
    {
        if (!trim($user->email))
            return response()->json(['message' => 'We can not find a user with that info.'], 404);
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email, 'username' => $user->username],
            [
                'username' => $user->username,
                'email' => $user->email,
                'token' => Str::random(10)
            ]
        );
        if ($user && $passwordReset) {
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
        }
        return response()->json([
            'message' => 'We have e-mailed your password reset link.'
        ]);
    }

    /**
     * @param User $user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword (Request $request)
    {
        try {
            $this->validate($request, [
                'password.password' => 'required'
            ]);
            $user = User::find(request('password.user_id'));
            $user->password = Hash::make(request('password.password'));
            $user->save();

            TrackChange::create([
                'changes_table_name_id' => 1,
                'action_id' => 4,
                'count' => 1,
                'ids' => $user->id
            ]);
            /*if (\request('send_email', 0) == 1) {
                $user->notify(new PasswordChangeSuccess(\request('password')));
                return response()->json([
                    'message' => 'Password has changed successfully and email sent'
                ]);
            }*/

            return response()->json([
                'message' => 'Password has changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
               'message' => "Something unexpected happened."
            ], 500);
        }

    }

    public function checkPassword(Request $request)
    {
        $user = User::find($request->id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'That user does not exist.'
            ]);
        }
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'The passwords do not match'
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => 'Something unexpected happened.'
        ]);
    }

    public function storeCsv(Request $request,User $user)
    {
        /*
        Response Values
        success => response has succeeded or not
        error => server unexpected error ? true : false
        type => 'user'
        dup_emails => duplicatedEmails array
        message => response message
        */
        if($request->hasFile('file'))
        {
            $path = $request->file('file')->getPathName();
            $csvFile = fopen($path, 'r');
            $total = 0;
            $first = 0;
            $userIds = [];
            $fields = [
                'title',
                'first_name',
                'last_name',
                'suffix',
                'email',
                'alternate_email',
                'password',
                'home_number',
                'mobile_number',
                'work_phone',
                'aoh_phone',
                'fax',
                'occupation',
                'primary_company_id',
                'role_id'
            ];

            while(($row = fgetcsv($csvFile)) !== FALSE)
            {
                if ($first < 3) {
                    $first++;
                    continue;
                } else {
                    // Check all values in the last row are empty
                    if (!empty(array_filter($row, function ($value) { return $value != ""; }))) {
                        $user = new User();
                        foreach ($fields as $index => $field) {
                            if ($index === 6) {
                                $user->$field = Hash::make($row[$index]);
                            } else if ($index === 14) {
                                $user->$field = Role::where('name', $row[14])->first()->id;
                            } else {
                                $user->$field = $row[$index];
                            }
                        }

                        if($user->save()) {
                            $userId = $user->id;
                            $countryCode = '';
                            if(Country::where('name', $row[22])->first()) {
                                $countryCode = Country::where('name', $row[22])->first()->code;
                            } else {
                                return response()->json(['success' => false, 'message' => 'Country code does not match.', 'error' => true, 'type' => 'user']);
                            }
                            $addressData = [
                                'user_id' => $userId,
                                'street' => $row[17],
                                'unit' => $row[18],
                                'city' => $row[19],
                                'province' => $row[20],
                                'state' => $row[21],
                                'country' => $countryCode,
                                'zip' => $row[23],
                                'phone' => $row[8],
                            ];

                            if ($addressData['street'] || $addressData['city']) {
                                $geocoder = app('geocoder')->geocode($addressData['street'] . ' ' . $addressData['city'] . ' ' . $addressData['state'] . ' ' . $addressData['country'] . ' ' . $addressData['zip'])->get()->first();
                                if ($geocoder) {
                                    $coordinates = $geocoder->getCoordinates();
                                    $addressData['latitude'] = $coordinates->getLatitude();
                                    $addressData['longitude'] = $coordinates->getLongitude();
                                }
                            }

                            $zone_id = '';
                            if(!empty($addressData['latitude']) && !empty($addressData['longitude'])) {
                                $zone_id = getGeoZoneID($addressData['latitude'], $addressData['longitude']);
                            }
                            $addressData['zone_id'] = $zone_id;
                            $user->address()->create($addressData);

                            if(Company::where('name', $row[13])->first()) {
                                $companyId = Company::where('name', $row[13])->first()->id;
                                CompanyUser::create([
                                    'user_id' => $user->id,
                                    'company_id' => $companyId,
                                ]);
                            }
                            if(ContactType::where('name', $row[15])->first()) {
                                ContactHasType::create([
                                    'contact_id' => $user->id,
                                    'contact_type_id' => ContactType::where('name', $row[15])->first()->id
                                ]);
                            }
                            if(ContactType::where('name', $row[16])->first()) {
                                ContactHasType::create([
                                    'contact_id' => $user->id,
                                    'contact_type_id' => ContactType::where('name', $row[16])->first()->id
                                ]);
                            }
                            $total++;
                            $userIds[] = $user->id;
                        } else {
                            return response()->json(['success'=> 'error', 'message' => 'Something unexpected happened.']);
                        }
                    } else {
                        break;
                    }
                }
            }

            $ids = '';
            foreach($userIds as $userId)
            {
                $ids .= $userId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 1,
                'action_id' => 1,
                'count' => $total,
                'ids' => $ids,
            ]);
            return response()->json(['success'=> 'success', 'message' => $total . ' Users are added.']);
            
        }
        return response()->json(['success'=> 'error', 'message' => 'File not found.']);
    }

    public function userInfo()
    {
        $users = User::all()->sortBy('last_name');

        $results = [];
        foreach($users as $user)
        {
            $results[] = [
                'user_info' => $user->first_name . ' ' . $user->last_name . ' ' . $user->roles->name,
            ];
        }
        return $results;
    }
}
