<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\CompanyContact;
use App\Models\CompanyUser;
use App\Models\ContactType;
use App\Models\CompanyNotes;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Vessel;
use App\Models\VesselFleets;
use App\Models\Network;
use App\Models\Capability;
use App\Models\Country;
use App\Models\VesselVendor;
use App\Models\TrackChange;
use App\Models\ChangesTableName;
use App\Models\Action;
use App\Models\PlanPreparer;
use App\Models\NetworkCompanies;
use Illuminate\Support\Facades\DB;

use App\Models\Vrp\VrpPlan;


use App\Helpers\VRPExpressCompanyHelper;
use App\Http\Helpers\VRPExpressVesselHelper;
use App\Http\Resources\CompanyContactResource;
use App\Http\Resources\CompanyContactShortResource;
use App\Http\Resources\CompanyContactTypesResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\CompanyShortResource;
use App\Http\Resources\CompanyShortWithAddressResource;
use App\Http\Resources\CompanyShowResource;
use App\Http\Resources\CompanyNoteResource;
use App\Http\Resources\VendorShortResource;
use App\Http\Resources\UserResource;
use GuzzleHttp\Client;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use stdClass;
use Intervention\Image\ImageManagerStatic as Image;

ini_set('memory_limit', '-1');

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        return $this->getAll($request);
    }

    public function getAll(Request $request)
    {
        $userInfo = Auth::user()->first();
        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'updated_at';
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';
        DB::enableQueryLog();
        $query = $request->get('query');
        $baseModel = new Company;

        $vrp = Auth::user()->hasVRP();

        $companyTable = $this->getCompaniesModalList($baseModel, $request->get('staticSearch'));
        $companyTableName = $baseModel->table();

        $companies = $companyTable->from($companyTableName . ' AS c1')
            ->select(DB::raw(empty($vrp) ? Company::FIELDS_COMPANY : Company::UNION_FIELDS_COMPANY))
            ->leftjoin('capabilities AS cs', 'c1.smff_service_id', '=', 'cs.id')
            ->whereRaw('(cs.status IS NULL OR cs.status=1 OR cs.status=0)');

        if ($request->has('staticSearch')) {
            $this->staticSearch($companies, $request->get('staticSearch'));
        }
        if (empty($vrp)) {
            // $resultsQuery =  $companies
            //         ->orderBy($sort, $sortDir);

            if (!empty($query) && strlen($query) > 2) {
                if(preg_match('/[\'^£$%&*( )}{@#~?><>,|=_+¬-]/', $query, $specialChar)) {
                    $strings = explode($specialChar[0], $query);
                    $uids = Company::where([['name', 'like', '%' . $strings[0] . '%'], ['name', 'like', '%' . $strings[1] . '%']])->get('id')->pluck('id');
                } else {
                    $uids = Company::search($query)->get('id')->pluck('id');
                }

                $companies = $companies->whereIn('c1.id', $uids);

                $resultsQuery =
                    $companies
                        ->orderBy('linked', 'desc')
                        ->orderBy('djs', 'desc')
                        ->orderBy('auth', 'desc')
                        ->orderBy($sort, $sortDir);
            } else {
                $resultsQuery =
                    $companies
                        ->orderBy($sort, $sortDir);
            }
        } else {
            $planTable = new VrpPlan;
            $planTableName = $planTable->table();

            $companies
                ->leftJoin($planTableName . " AS p1", 'c1.plan_number','=','p1.plan_number');

            if(request('staticSearch')['merge'] == -1) {
                $plans = VrpPlan::from($planTableName . " AS p2")->select(DB::raw(Company::UNION_FIELDS_PLAN))
                ->whereRaw('p2.plan_number NOT IN (SELECT cx.plan_number FROM ' . $companyTableName . ' AS cx WHERE cx.plan_number IS NOT NULL)');
            } else {
                $plans = VrpPlan::from($planTableName . " AS p2")->select(DB::raw(Company::UNION_FIELDS_PLAN));
            }

            if ($request->has('staticSearch')) {
                $this->staticSearchPlans($plans, $request->get('staticSearch'));
            }

            if (!empty($query) && strlen($query) > 2) {
                if(preg_match('/[\'^£$%&*( )}{@#~?><>,|=_+¬-]/', $query, $specialChar)) {
                    $strings = explode($specialChar[0], $query);
                    $uids = Company::where([['name', 'like', '%' . $strings[0] . '%'], ['name', 'like', '%' . $strings[1] . '%']])->get('id')->pluck('id');
                    $companies = $companies->whereIn('c1.id', $uids);
                    $uids = VrpPlan::where([['plan_holder', 'like', '%' . $strings[0] . '%'], ['plan_holder', 'like', '%' . $strings[1] . '%']])->get('id')->pluck('id');
                    $plans = $plans->whereIn('p2.id', $uids);
                } else {
                    $uids = Company::search($query)->get('id')->pluck('id');
                    $companies = $companies->whereIn('c1.id', $uids);
                    $uids = VrpPlan::search($query)->get('id')->pluck('id');
                    $plans = $plans->whereIn('p2.id', $uids);
                }

                $resultsQuery =
                    $companies
                        ->union($plans)
                        ->orderBy('linked', 'desc')
                        ->orderBy('djs', 'desc')
                        ->orderBy('auth', 'desc')
                        ->orderBy($sort, $sortDir);
            } else {
                $resultsQuery =
                    $companies
                        ->union($plans)
                        ->orderBy($sort, $sortDir);
            }
        }
        $per_page = request('per_page') == -1  ? count($resultsQuery->get()) : request('per_page');

        $results = CompanyResource::collection($resultsQuery->paginate($per_page));
        //return print_r(DB::getQueryLog(), true);
        return $results;
    }

    private function staticSearch($model, $staticSearch)
    {
        if ($staticSearch['active'] !== -1) {
            $model = $model->where('active', (boolean)$staticSearch['active']);
        }

        if ($staticSearch['vrp_status'] !== -1) {
            $statusSearch = '';
            if ($staticSearch['vrp_status'] === 1) {
                $statusSearch = 'Authorized';
            } else if ($staticSearch['vrp_status'] === 0) {
                $statusSearch = 'Not Authorized';
            }
            $model = $model->where('p1.status', $statusSearch);
        }

        if (array_key_exists('resource_provider', $staticSearch) && $staticSearch['resource_provider'] !== -1) {
            if ($staticSearch['resource_provider']) {
                $model = $model->whereNotNull('cs.id');
            } else {
                $model = $model->whereNull('cs.id');
            }
        }

        $userInfo = Auth::user();
        // $mainCompanies = 0;
        // if ($userInfo->role_id == 7) { // Company Plan Manager
        //     if(!isset($staticSearch['parent'])) {
        //         $staticSearch['parent'] = $userInfo->primary_company_id;
        //         $mainCompanies = 1;
        //     }
        // }
        if ($userInfo->role_id !== 7 && array_key_exists('parent', $staticSearch)) {
            $operate = Company::where('operating_company_id', $staticSearch['parent'])->first();
            if($operate) {
                $model = $model->where('c1.operating_company_id', $staticSearch['parent']);
            } else {
                $operatingCompanyID = Company::where('id', $staticSearch['parent'])->first()->operating_company_id;
                if($operatingCompanyID) {
                    // if($mainCompanies && $userInfo->role_id == 7) {
                    //     $model = $model->where('c1.operating_company_id', $operatingCompanyID)->orWhere('c1.id', $operatingCompanyID);
                    // } else {
                    //     $model = $model->where('c1.operating_company_id', $operatingCompanyID);
                    // }
                    $model = $model->where('c1.operating_company_id', $operatingCompanyID);
                } else {
                    $model = $model->where('c1.operating_company_id', $staticSearch['parent']);
                }
            }
        }

        if($userInfo->role_id !== 6) {
            if (array_key_exists('networks', $staticSearch) && count($staticSearch['networks'])) {
                $model = $model
                        ->where('c1.networks_active', 1) //->orWhere('c1.smff_service_id', '<>', 0)
                        ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                        ->whereIn('nc.network_id', $staticSearch['networks']);
            }
        }

        return $model;
    }

    private function staticSearchPlans($model, $staticSearch)
    {
        if ($staticSearch['vrp_status'] !== -1) {
            $statusSearch = '';
            if ($staticSearch['vrp_status'] === 1) {
                $statusSearch = 'Authorized';
            } else if ($staticSearch['vrp_status'] === 0) {
                $statusSearch = 'Not Authorized';
            }
            $model = $model->where('p2.status', $statusSearch);
        }

        if (array_key_exists('parent', $staticSearch) ||
          (array_key_exists('networks', $staticSearch) && count($staticSearch['networks'])) ||
          (array_key_exists('resource_provider', $staticSearch) && $staticSearch['resource_provider'] !== -1) ||
          (array_key_exists('include_vrp', $staticSearch) && $staticSearch['include_vrp'] !== -1)
                ) {
            $model = $model->whereRaw('0=1');
        }

        return $model;
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function indexShort()
    {
        return CompanyShortResource::collection($this->getCompaniesModal()->orderBy('name')->get());
    }

    public function getShort($id)
    {
        return CompanyShortResource::collection($this->getCompaniesModal()->where('id', $id)->get())[0];
    }

    public function getShortWithAddress($id)
    {
        return CompanyShortWithAddressResource::collection($this->getCompaniesModal()->where('id', $id)->get())[0];
    }

    public function getPrimaryContacts(Company $company)
    {
        DB::enableQueryLog();
        $results = CompanyContactResource::collection($company->primaryContacts()->get());
        return print_r(DB::getQueryLog(), true);
    }

    public function getSecondaryContacts(Company $company)
    {
        return CompanyContactResource::collection($company->secondaryContacts()->get());
    }

    public function getContacts(Company $company)
    {
        DB::enableQueryLog();
        $results = $company->users()->get();
        // $results = $company->contacts()->get();
        //return print_r(DB::getQueryLog(), true);
        return UserResource::collection($results);
    }

    public function getQI(Company $company)
    {
        $vessel_ids = $company->vessels()->pluck('id');
        return VendorShortResource::collection(Company::whereHas('type', function ($q) {
            $q->where('name', 'QI Company');
        })->whereHas('vessels', function ($q) use ($vessel_ids) {
            $q->whereIn('id', $vessel_ids);
        })->get());
    }

    public function quickUpdateCompany(Request $request)
    {
        Company::where('id',request('company.id'))
                ->update(['name' => request('company.name'),
                          'operating_company_id' => request('company.operating_company_id')]);

        return response()->json(['message' => 'Operating Company Updated Successfully']);
    }

    public function assignMultipleCompany(Request $request)
    {
       $company_ids = request('company.operating_company_ids');
       foreach ($company_ids as $key => $value) {
        Company::where('id',$value)
                ->update(['operating_company_id' => request('company.company_id')]);
       }
        return response()->json(['message' => 'Company Added Successfully']);
    }

    public function quickUpdateVessel(Request $request)
    {
        Vessel::where('id',request('vessel.id'))
                ->update(['name' => request('vessel.name'),
                          'company_id' => request('vessel.operating_company_id')]);

        VesselFleets::where('vessel_id',request('vessel.id'))
                        ->update(['fleet_id' => request('vessel.fleet_id')]);

        return response()->json(['message' => 'Vessel Updated Successfully']);
    }

    public function getContactsDPA(Company $company)
    {
        return CompanyContactShortResource::collection($company->contacts()->whereHas('contactTypes', function ($q) {
            $q->where('name', 'DPA');
        })->get());
    }

    public function getContactTypes()
    {
        return CompanyContactTypesResource::collection(ContactType::all());
    }

    public function storePhoto(Company $company, Request $request)
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

        $directory = 'pictures/companies/' . $company->id . '/';

        $name1 = 'cover_rect.jpg';
        $name2 = 'cover_sqr.jpg';

        if (Storage::disk('gcs')->put($directory.$name2, (string)$image_sqr->encode('jpg'), 'public') &&
            Storage::disk('gcs')->put($directory.$name1, (string)$image_rect->encode('jpg'), 'public')) {
            $company->has_photo = true;
            $company->save();
            return response()->json(['message' => 'Picture uploaded.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function destroyPhoto(Company $company)
    {
        $directory = 'pictures/companies/' . $company->id . '/';
        if (
            Storage::disk('gcs')->delete($directory . 'cover_rect.jpg') &&
            Storage::disk('gcs')->delete($directory . 'cover_sqr.jpg')
        ) {
            $company->has_photo = false;
            $company->save();
            return response()->json(['message' => 'Picture deleted.']);
        }
        return response()->json(['message' => 'Can not delete a parent company photo.']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $company = new Company();
        $company->name = request('name');
        $company->plan_number = request('plan_number');
        $company->email = request('email');
        $company->fax = request('fax');
        $company->phone = request('phone_number');
        $company->work_phone = request('work_phone');
        $company->aoh_phone = request('aoh_phone');
        $company->website = request('website');
        $company->operating_company_id = request('operating_company');
        $company->qi_id = request('qi');
        $company->active = (boolean)request('djs_coverage');
        $company->networks_active = 0;
        $company->vendor_active = request('is_vendor') ? 1 : null ;
        $company->vendor_type = request('is_vendor') ? request('vendor_type_id') : null;
        $company->shortname = request('is_vendor') ? request('shortname') : null;
        $company->plan_preparer_id = request('plan_preparer_id');

        if (!request('permitted')) {
            if ($request->has('plan_number') && (int)request('plan_number') != 0 && Company::where('plan_number', request('plan_number'))->first()) {
                return response()->json(['message' => 'This Plan Number is already in the system.', 'success' => false]);
            }
            if ($request->has('email') && Company::where('email', request('email'))->first()) {
                return response()->json(['success'=> false, 'message' => 'Duplicate Email Detected.']);
            }
        }

        if ($company->save()) {
            // Company Address
            $address = $company->addresses()->create([
                'address_type_id' => '3',
                'street' => $request->input('street'),
                'unit' => $request->input('unit'),
                'country' => $request->input('country'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'zip' => $request->input('zip'),
                'phone' => $request->input('phone_number')
            ]);

            $addressData = $request->all();

            if (request('street') || request('city')) {
                $geocoder = app('geocoder')->geocode(request('street') . ' ' . request('city') . ' ' . request('state') . ' ' . request('country') . ' ' . request('zip'))->get()->first();
                if ($geocoder) {
                    $coordinates = $geocoder->getCoordinates();
                    $address->latitude = $coordinates->getLatitude();
                    $address->longitude = $coordinates->getLongitude();
                    $address->save();
                }
            }

            $company->notes()->create([
                'note' => $request->input('comments'),
                'note_type' => 1,
                'user_id' => Auth::user()->id
            ]);

            /*Image upload to S3*/
            if($request->image){
                $request_image = $request->image;
                $imageInfo = explode(";base64,", $request_image);
                $image1 = str_replace(' ', '+', $imageInfo[1]);
                $image = Image::make($image1);
                $image->fit(400, 225);
                $directory = 'pictures/companies/' . $company->id . '/';
                $name = 'cover.jpg';
                if (Storage::disk('gcs')->put($directory.$name, (string)$image->encode('jpg'), 'public')) {
                    $company->photo = $name;
                    $company->save();
                }
            }

            $companyIds = [];
            $companyIds[] = $company->id;
            $ids = '';
            foreach($companyIds as $companyId)
            {
                $ids .= $companyId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 3,
                'action_id' => 1,
                'count' => 1,
                'ids' => $ids,
            ]);

            return response()->json(['message' => 'Company added.', 'id' => $company->id, 'success' => true]);
        }
        return response()->json(['message' => 'Something unexpected happened.', 'success' => false]);
    }

    // Get Duplicate Plan Number
    public function getDuplicatePlanNumber($planNumber)
    {
        if ($planNumber && (int)$planNumber != 0) {
            if (Company::where('plan_number', $planNumber)->first()) {
                return response()->json(['success' => false]);
            }
        }
        return response()->json(['success' => true]);
    }

    // Get Duplicate Company Email
    public function getDuplicateCompanyEmail($email)
    {
        if ($email) {
            if (Company::where('email', $email)->first()) {
                return response()->json(['success' => false]);
            }
        }
        return response()->json(['success' => true]);
    }

    //Notes
    public function addNote(Company $company, Request $request)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        $this->validate($request, [
            'note_type' => 'required',
            'note' => 'required'
        ]);

        $note = $company->notes()->create([
            'note_type' => request('note_type'),
            'note' => request('note'),
            'user_id' => Auth::id()
        ]);

        if ($note) {
            return response()->json(['message' => 'Note added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function getNotes(Company $company)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        return CompanyNoteResource::collection($company->notes()->get());
    }

    public function destroyNote(Company $company, $id)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        if ($company->notes()->find($id)->delete()) {
            return response()->json(['message' => 'Note deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function storeContact($id, Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required'
        ]);

        $contact = new CompanyContact();
        $contact->prefix = request('prefix');
        $contact->first_name = request('first_name');
        $contact->last_name = request('last_name');
        $contact->email = request('email');
        $contact->work_phone = request('work_phone');
        $contact->mobile_phone = request('mobile_phone');
        $contact->aoh_phone = request('aoh_phone');
        $contact->fax = request('fax');
        $contact->company_id = $id;

        if ($contact->save()) {
            $contact->contactTypes()->sync(\request('types'));
            return response()->json(['message' => 'Company contact added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function updateContact($id, Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required'
        ]);

        $contact = CompanyContact::find(request('id'));
        $contact->prefix = request('prefix');
        $contact->first_name = request('first_name');
        $contact->last_name = request('last_name');
        $contact->email = request('email');
        $contact->work_phone = request('work_phone');
        $contact->mobile_phone = request('mobile_phone');
        $contact->aoh_phone = request('aoh_phone');
        $contact->fax = request('fax');

        if ($contact->save()) {
            $contact->contactTypes()->sync(\request('types'));
            return response()->json(['message' => 'Company contact updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * @param Company $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Company $company)
    {
        $company->active = !$company->active;
        if ($company->save()) {
            return response()->json(['message' => 'Company status changed.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * @param Company $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleVendor(Company $company)
    {
        $company->vendor_active = !$company->vendor_active;
        if ($company->save()) {
            return response()->json(['message' => 'Company vendor status changed.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * @param Company $company
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleNetworks(Company $company)
    {
        $company->networks_active = !$company->networks_active;
        if ($company->save()) {
            return response()->json(['message' => 'Company network status changed.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return AnonymousResourceCollection
     */
    public function show($id)
    {
        $company = Company::select(DB::raw(Company::FIELDS_COMPANY))
            ->from('companies AS c1')
            ->leftjoin('capabilities AS cs', function($join) {
                $join->on('c1.smff_service_id' , '=' , 'cs.id');
                $join->on('cs.status' , '=' , DB::raw('1'));
            })
            ->where('c1.id', $id);
        return CompanyShowResource::collection($company->get());
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

        $company = Company::find($id);
        if ($request->has('name')) $company->name = request('name');
        if ($request->has('plan_number')) $company->plan_number = request('plan_number');
        if ($request->has('email')) $company->email = request('email');
        if ($request->has('fax')) $company->fax = request('fax');
        if ($request->has('website')) $company->website = request('website');
        if ($request->has('description')) $company->description = request('description');
        if ($request->has('phone')) $company->phone = request('phone');
        $company->plan_preparer_id = request('plan_preparer_id');
        $request->has('qi_id') ? $company->qi_id = request('qi_id') : $company->qi_id = 0;
        $request->has('operating_company_id') ? $company->operating_company_id = request('operating_company_id') : $company->operating_company_id = 0;
        if ($request->has('company_poc_id')) $company->company_poc_id = request('company_poc_id');
        if ($request->has('active')) $company->active = (boolean)request('active');
        if ($request->has('shortname')) $company->shortname = request('shortname');
        // if ($request->has('networks_active')) $company->networks_active = (boolean)request('networks_active');

        $companyIds = [];
        $companyIds[] = $company->id;
        $ids = '';
        foreach($companyIds as $companyId)
        {
            $ids .= $companyId.',';
        }
        $ids = substr($ids, 0, -1);
        if ($company->save()) {
            TrackChange::create([
                'changes_table_name_id' => 3,
                'action_id' => 3,
                'count' => 1,
                'ids' => $ids,
            ]);
            return response()->json(['message' => 'Company updated.']);
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
        $company = Company::find($id);
        if ($company) {
            $directory = 'files/Documents/' . $id;
            Storage::disk('gcs')->delete($directory);
            $company->addresses()->delete();
            $company->smffCapability()->delete();
            CompanyUser::where('company_id', $id)->delete();
            CompanyNotes::where('company_id', $id)->delete();

            $companyIds = [];
            $companyIds[] = $id;
            $ids = '';
            foreach($companyIds as $companyId)
            {
                $ids .= $companyId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 3,
                'action_id' => 2,
                'count' => 1,
                'ids' => $ids
            ]);
            return $company->delete() ? response()->json(['message' => 'Company deleted.']) : response()->json(['message' => 'Could not delete company.']);
        }

        return response()->json(['message' => 'No company found.'], 404);
    }

    public function unlinkOperatingCompany($id){

        Company::where('id',$id)
          ->update(['operating_company_id' => null]);

        return response()->json(['message' => 'Company unlink successfully']);

    }

    public function unlinkIndividual($id){

        User::where('id',$id)
          ->update(['company_id' => null]);

        return response()->json(['message' => 'User unlink successfully']);

    }

     public function unlinkVessel($id){

        Vessel::where('id',$id)
          ->update(['company_id' => null]);

        return response()->json(['message' => 'Vessel unlink successfully']);

    }

    public function destroyContact($id)
    {
        $companyContact = CompanyContact::find($id);
        if ($companyContact) {
            return $companyContact->delete() ? response()->json(['message' => 'Contact deleted.']) : response()->json(['message' => 'Could not delete company contact.']);
        }

        return response()->json(['message' => 'No contact found.'], 404);
    }

    //SMFF Capabilities
    public function storeSMFF($id)
    {
        $company = Company::find($id);
        $smff = null;
        $message = '';
        if ($company) {
            $message = $company->smff_service_id;
            if ($company->smff_service_id) {
                $smff = Capability::find($company->smff_service_id);
                if (empty($smff)) {
                    // error???
                } else {
                    $smff->status = 1; // undelete
                    $smff->save();
                }
            } else {
                $company->smff_service_id = Capability::create()->id;
                $message = 'SMFF Capabilities created.';
            }
            return $company->save() ? response()->json(['message' => $message]) : response()->json(['message' => 'Could not create SMFF Capabilities.']);
        }

        return response()->json(['message' => 'No Company found.'], 404);
    }

    public function showSMFF($id)
    {
        $company = Company::where('id', $id)->first();
        $smff_service_id = $company->smff_service_id;
        $smff = $company->smff();
        return response()->json([
            'smff' => $smff,
            'networks' => $company->networks->pluck('code'),
            'serviceItems' => Capability::primaryServiceAvailable()
        ]);
    }

    public function updateNetworks(Request $request, $id)
    {
        $networks = request('networks');

        $network_ids = Network::whereIn('code', request('networks'))->pluck('id');
        if (!$company->networks()->sync($network_ids)) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        return response()->json(['message' => 'Company Networks updated.']);
    }

    public function updateSMFF(Request $request, $id)
    {
        $message = "";
        $company = Company::find($id);
        $capabilities = Capability::find($company->smff_service_id);
        $smffFields = request('smff');
        if (!$capabilities->updateValues(
            isset($smffFields['primary_service']) ? $smffFields['primary_service'] : null,
            isset($smffFields['notes']) ? $smffFields['notes'] : null,
            $smffFields)) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        $network_ids = Network::whereIn('code', request('networks'))->pluck('id');
        if (!$company->networks()->sync($network_ids)) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        return response()->json(['message' => 'Company SMFF Capabilities updated.']);
    }

    public function updateNetwork(Request $request, $id)
    {
        $networks = [1,2,3,4];
        $company = NetworkCompanies::where('company_id', $id)->first();
        $network_ids = Network::whereIn('code', request('networks'))->pluck('id');

        foreach($network_ids as $network_id)
        {
            if (NetworkCompanies::where([['company_id', $id], ['network_id', $network_id]])->first()) {
                if (($key = array_search($network_id, $networks)) !== false) {
                    NetworkCompanies::where([['network_id', $network_id], ['company_id', $id]])
                    ->update([
                        'active' => 1
                    ]);
                    unset($networks[$key]);
                }
            } else {
                if (($key = array_search($network_id, $networks)) !== false) {
                    NetworkCompanies::create([
                        'active' => 1,
                        'network_id' => $network_id,
                        'company_id' => $id,
                    ]);
                    unset($networks[$key]);
                }
            }

            foreach($networks as $network)
            {
                NetworkCompanies::where([['network_id', $network], ['company_id', $id]])
                ->update([
                    'active' => 0
                ]);
            }
        }

        return response()->json(['message' => 'Company Network Membership Updated.']);
        // if ($company->networks()->sync($network_ids)) {
        //     return response()->json(['message' => 'Company Network Membership Updated.']);
        // }
        // return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function destroySMFF($id)
    {
        $company = Company::find($id);
        if ($company) {
            $smff = Capability::find($company->smff_service_id);
            $smff->status = 0;
            return $smff->save() ? response()->json(['message' => 'SMFF Capabilities deleted.']) : response()->json(['message' => 'Could not delete SMFF Capabilities.']);
        }

        return response()->json(['message' => 'No Company found.'], 404);
    }

    /**
     * @return AnonymousResourceCollection
     */
// @todo: for removing smff data from user's who don't have smff data permission from backend
    private function getCompaniesModal () {
        $role_id = Auth::user()->role_id;

        if ($role_id == 7) { // Company Plan Manager
            return Company::whereIn('id', Auth::user()->companies()->pluck('id'))
                ->where('active', 1);
        } else if ($role_id == 3) { // QI Companies
            return Company::where([['vendor_type', 3], ['vendor_active', 1]]);
        } else if ($role_id == 4) { // Coast Guard
            return Company::where('active', 1);
        } else if ($role_id == 6) { // NASA / NAVY
            return Company::where('networks_active', 1)->orWhere('smff_service_id', '<>', 0);
        }
        return new Company;
    }

    /**
     * @return AnonymousResourceCollection
     */

// @todo: for removing smff data from user's who don't have smff data permission from backend
    private function getCompaniesModalList ($model, $staticSearch) {
        $role_id = Auth::user()->role_id;
        $userInfo = Auth::user();

        if ($role_id == 7) { // Company Plan Manager
            $companyIds = Auth::user()->companies()->pluck('id');
            $ids = [];
            foreach($companyIds as $companyId) {
                $ids[] = $companyId;
                $operatingCompany = Company::where('id', $companyId)->first()->operating_company_id;
                $affiliateCompanies = Company::where('operating_company_id', $companyId)->get();
                if(!$operatingCompany && isset($affiliateCompanies)) {
                    foreach($affiliateCompanies as $affiliateCompany)
                    {
                        $ids[] = $affiliateCompany->id;
                    }
                }
            }
            // $model = $model->whereIn('c1.id', $ids);
            if(array_key_exists('parent', $staticSearch)) {
                // $operatingCompanyID = Company::where('id', $staticSearch['parent'])->first()->operating_company_id;
                // if($mainCompanies && $userInfo->role_id == 7) {
                //     $model = $model->where('c1.operating_company_id', $operatingCompanyID)->orWhere('c1.id', $operatingCompanyID);
                // } else {
                //     $model = $model->where('c1.operating_company_id', $operatingCompanyID);
                // }
                $model = $model->whereIn('c1.id', $ids)->orWhereIn('c1.operating_company_id', $ids);
            } else {
                $model = $model->whereIn('c1.id', $ids);
            }
        } else if ($role_id == 3) { // QI Companies
            return $model->where([['c1.qi_id', $userInfo->primary_company_id], ['c1.active', 1]]);
        } else if ($role_id == 4) { // Coast Guard
            return $model->where('c1.active', 1);
        } else if ($role_id == 6) { // NASA / NAVY
            if (array_key_exists('networks', $staticSearch) && count($staticSearch['networks'])) {
                return $model;
            } else {
                // return $model->where('c1.networks_active', 1)->orWhere('smff_service_id', '<>', 0)
                //                 ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id');
                return $model->where('c1.networks_active', 1);
            }
        }
        return $model;
    }

    private function vrpStats($companies)
    {
        try {
            $filteredCompanies = $companies->filter(function ($company, $key) {
                return $company->plan_number !== null;
            });
            $company_ids = $filteredCompanies->pluck('plan_number', 'id');

//            $client = new Client();
//            $res = $client->request('POST', 'http://35.184.163.31/api/companies', ['json' => ['company_ids' => $company_ids], 'stream' => true, 'timeout' => 0, 'read_timeout' => 10]);
//            $vrp_data = json_decode($res->getBody()->getContents(), true);
            $vrp_data = json_decode(json_encode(VRPExpressCompanyHelper::getCompanies($company_ids)), true);
//            return VRPExpressCompanyHelper::getCompanies($company_ids);

            foreach ($companies as $company) {
                if ($company->plan_number) {
                    if(array_key_exists($company->id,$vrp_data))
                    {
                        $company->vrp_status = $vrp_data[$company->id]['status'];
                        if(isset($company->plan_holder))
                        {
                            $company->plan_holder = $vrp_data[$company->id]['plan_holder'];
                        }
                        $company->vrp_plan_type = $vrp_data[$company->id]['plan_type'];
                        $company->vrp_vessels_count = $vrp_data[$company->id]['vessels_count'];
                        $company->primary_smff = $vrp_data[$company->id]['primary_smff'];
                        $company->vrp_plan_number = $company->plan_number;
                        $company->vrp_express = true;
                    }
                    else
                    {
                        $company->vrp_plan_number = $company->plan_number;
                        $company->vrp_express = true;
                    }
                }
            }
        } catch (\Exception $error) {
            throw $error;
        }
        return $companies;
    }

    private function vrpSearch($companies, $exclude_ids, $query, $vrp_status)
    {
        $vrp_plans = [];
        try {
            $vrp_data = json_decode(json_encode(VRPExpressCompanyHelper::getCompaniesBySearch($query, $exclude_ids, $vrp_status)), true);
            foreach ($vrp_data as $plan) {
                $plan = (object)$plan;
                $vrp_plans[] = [
                    'id' => -1,
                    'name' => $plan->plan_holder ?? '',
                    'vrp_plan_name' => $plan->plan_holder ?? '',
                    'plan_number' => $plan->plan_number,
                    'vrp_plan_number' => trim($plan->plan_number) ? $plan->plan_number : '',
                    'resource_provider' => false,
                    'active' => false,
                    'location' => $plan->CDTCountry->code ?? '',
                    'country' => $plan->CDTCountry ?? '',
                    'stats' => [
                        'users' => '',
                        'individuals' => '',
                        'vessels' => '',
                        'contacts' => ''
                    ],
                    'vrp_status' => $plan->status,
                    'vrp_stats' => [
                        'plan_type' => $plan->plan_type ?? '',
                        'vessels' => $plan->vessels_count
                    ],
                    'vrp_express' => true,
                    'coverage'    => str_contains(strtolower($plan->primary_smff), 'donjon') ? 1 : 0,
                    'is_tank'   => $plan->plan_type ?? ''
                ];
            }
        } catch (\Exception $error) {

        }
        $merged_companies = [];
        foreach ($companies as $company) {
            $merged_companies[] = [
                'id' => $company->id,
                'name' => $company->name,
                'vrp_plan_name' => $company->plan_holder,
                'plan_number' => trim($company->plan_number) ? $company->plan_number : '',
                'vrp_plan_number' => trim($company->vrp_plan_number) ? $company->vrp_plan_number : '',
                'resource_provider' => $company->smffCapability ? true : false,
                'active' => (boolean)$company->active,
                'location' => count($company->primaryAddress) ? ($company->primaryAddress[0]->country ?? '') : '',
                'country' => count($company->primaryAddress) ? ($company->primaryAddress[0]->country ?? '') : '',
                'stats' => [
                    'users' => count($company->users),
                    'individuals' => count($company->individuals),
                    'vessels' => count($company->vessels),
                    'contacts' => count($company->contacts)
                ],
                'vrp_status' => $company->vrp_status ?? 'NO VRP LINK',
                'vrp_stats' => [
                    'plan_type' => $company->vrp_plan_type ?? '',
                    'vessels' => $company->vrp_vessels_count ?? ''
                ],
                'vrp_express' => $company->vrp_express ? true : false,
//                'coverage'    => str_contains($company->plan_holder, 'donjon') ? 1 : 0,
                'response'   => $company->smff_service_id ? 1 : 0,
                'is_tank'   => $company->vrp_plan_type ?? '',
                'coverage' => $company->active
            ];
        }
        $merged_companies = array_merge($merged_companies, $vrp_plans);
        return $merged_companies;
    }

    public function getVRPdata($plan)
    {
        try {
            $vrp_data = null;
            $vrp_data = json_decode(json_encode(VRPExpressCompanyHelper::getCompaniesByPlan($plan)), true);
        } catch (\Exception $error) {
        }

        return $vrp_data;
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

    public function getFilesCount(Company $company)
    {
        $files = [];
        $companyDirectory = 'files/Documents/' . $company->id . '/';
        $directories = Storage::disk('gcs')->directories($companyDirectory);
        foreach ($directories as $directory) {
            $filesInFolder = Storage::disk('gcs')->files($companyDirectory . pathinfo($directory)['basename']);
            $files[pathinfo($directory)['basename']] = [
                'count' => count($filesInFolder)
            ];
        }
        return response()->json($files);
    }

    public function getFilesDOC(Company $company, $type)
    {
        $files = [];
        $directory = 'files/Documents/' . $company->id . '/' . $type . '/';
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

    public function destroyFileDOC(Company $company, $type, $fileName)
    {
        $directory = 'files/Documents/' . $company->id . '/' . $type . '/';
        if (Storage::disk('gcs')->delete($directory . $fileName)) {
            return response()->json(['message' => 'File deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function bulkDestroy(Request $request, Company $company, $type)
    {
        $removeData = $request->all();
        for($i = 0; $i < count($removeData); $i ++) {
            $directory = 'files/Documents/' . $company->id . '/' . $type . '/';
            Storage::disk('gcs')->delete($directory . $removeData[$i]['name']);
        }
        return response()->json(['message' => 'File deleted.']);
    }

    public function downloadFileDOC(Company $company, $type, $fileName)
    {
        $directory = 'files/Documents/' . $company->id . '/' . $type . '/';
        $url = Storage::disk('gcs')->temporaryUrl(
            $directory . $fileName, now()->addMinutes(5)
        );
        return response()->json(['message' => 'Download started.', 'url' => $url]);
    }

    public function downloadFileDOCForce(Company $company, $type, $fileName)
    {
        ini_set('memory_limit', '-1');

        $directory = 'files/Documents/' . $company->id . '/' . $type . '/';
        return response()->streamDownload(function() use ($directory, $fileName) {
            echo Storage::disk('gcs')->get($directory . $fileName);
        }, $fileName, [
                'Content-Type' => 'application/octet-stream'
            ]);
    }

    public function uploadFileDOC(Company $company, $type, Request $request)
    {

        set_time_limit(0);
        ini_set('memory_limit', '-1');

      //  add_cors_headers_group_cdt_individual($request);

        $fileName = $request->file->getClientOriginalName();
        $directory = 'files/Documents/' . $company->id . '/' . $type . '/';

        if (Storage::disk('gcs')->exists($directory . $fileName)) {
            $fileName = date('m-d-Y_h:ia - ') . $fileName;
        }
        if (Storage::disk('gcs')->putFileAs($directory, \request('file'), $fileName)) {
            return response()->json(['message' => 'File uploaded.', 'name' => $fileName]);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function generateFileDOC(Company $company, $type, Request $request)
    {
        $data = [
            'footerText' => '',
            'issueDate' => \request('dateIssued'),
            'dateCreated' => \request('dateCreated'),
            'address' => CompanyAddress::where('id', request('address'))->first()
        ];
        $vessels = [];
        $fileName = request('name') . '.pdf';
        $directory = 'files/Documents/' . $company->id . '/' . $type . '/';
        $headerHtml = view()->make('documents.pdf-templates.partials.header-1')->render();
        $footerHtml = view()->make('documents.pdf-templates.partials.footer-1', compact('data'))->render();
        $pdf = App::make('snappy.pdf.wrapper');
        $pdf->setPaper('A4');
        $pdf->setOption('margin-bottom', '1cm');
        $pdf->setOption('margin-top', '2cm');
        $orientation = 'portrait';
        $pdf->setOption('margin-right', '1cm');
        $pdf->setOption('margin-left', '1cm');
        $pdf->setOption('enable-javascript', true);
        $pdf->setOption('enable-smart-shrinking', true);
        $pdf->setOption('no-stop-slow-scripts', true);
        switch ($type) {
            case 'group-v-consent-letter':
                $fileName = 'Consent Agreement For Vessel Response Plan.pdf';
                break;
            case 'letter-of-intent-non-tank-vessels-below-250-bbls':
                $data['capacity'] = '250';
                $data['footerText'] = 'Consent Agreement For Vessel Response Plans<br/>Conforms to International Group of P&I Guidelines';
                break;
            case 'letter-of-intent-non-tank-vessels':
                $data['capacity'] = '2,500';
                $data['footerText'] = 'Consent Agreement For Vessel Response Plans<br/>Conforms to International Group of P&I Guidelines';
                break;
            case 'smff-coverage-certification':
                $vessels = $company->vessels()->select('id', 'name')->get();
                $pdf->setOption('margin-bottom', '2cm');
                $footerHtml = view()->make('documents.pdf-templates.partials.footer-asa', compact('data'))->render();
                break;
            case 'damage-stability-coverage-certification':
                $data['certificateNumber'] = \request('certificateNumber');
                $data['certificateRevision'] = \request('certificateRevision');
                $pdf->setOption('margin-top', '2.5cm');
                $vessels = $company->vessels()->select('id', 'name', 'imo')->get();
                break;
            case 'nt-smff-annex':
                $pdf->setOption('minimum-font-size', 18);
                $data['qi'] = \request('qi');
                $data['dpa'] = \request('dpa');
                $data['contract'] = \request('contract');
                $data['title'] = 'NT Vessel Response Plan';
                $data['subtitle'] = 'Salvage and Marine Firefighting Annex';
                $data['footerText'] = 'Donjon-SM IT LLC<br/>Salvage & Marine Firefighting Annex';
                //TANKER === FALSE
                $vessels = $company->vessels()->select('id', 'name', 'imo', 'tanker')->where('tanker', 0)->get();
                $headerHtml = view()->make('documents.pdf-templates.partials.header-2', compact('data'))->render();
                break;
        }

        $pdf->setOrientation($orientation);
        $pdf->setOption('header-html', $headerHtml);
        $pdf->setOption('footer-html', $footerHtml);
        $pdf->loadView('documents.pdf-templates.' . $type . '.index', compact('company', 'data', 'vessels'));

        if (Storage::disk('gcs')->exists($directory . $fileName)) {
            $fileName = date('m-d-Y_h:ia - ') . $fileName;
        }
        $tmpName = md5($fileName . time());
        $pdf->save(storage_path($tmpName));

        if (Storage::disk('gcs')->putFileAs($directory, new File(storage_path($tmpName)), $fileName)) {
            unlink(storage_path($tmpName));
            return response()->json(['message' => 'File generated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    // get company id, name
    public function getCompanyInfo(Request $request) {
        return response()->json(Company::select('id', 'name')->get());
    }
    // end get jcompany id, name

    // save company data csv file
    public function storeBulk(Request $request)
    {
        /*
        Response Values
        success => response has succeeded or not
        error => server unexpected error ? true : false
        type => 'user'
        dup_emails => dup_emails array
        dup_plans => dup_plans array
        message => response message
        */
        if($request->hasFile('file'))
        {
            $path = $request->file('file')->getPathName();
            $csvFile = fopen($path, 'r');
            $total = 0;
            $first = 0;
            $dup_plans = [];
            $dup_emails = [];
            $companyIds = [];
            $to_be_added = [];
            while(($line = fgetcsv($csvFile)) !== FALSE)
            {
                if ($first < 3) {
                    $first++;
                    continue;
                } else {
                    // Check all values in the last row are empty
                    if (!empty(array_filter($line, function ($value) { return $value != ""; }))) {
                        if ( (int)$line[0] != 0 || $line[0] != "") {
                            if (Company::where('plan_number', (int)$line[0])->first()) {
                                array_push($dup_plans, $line[0]);
                                continue;
                            }
                        }
                        $to_be_added[] = [
                            'plan_number' => (int)$line[0],
                            'name' => $line[1],
                            'email' => $line[2],
                            'fax' => $line[3],
                            'phone' => $line[4],
                            'work_phone' => $line[5],
                            'aoh_phone' => $line[6],
                            'website' => $line[7],
                            'street' => $line[8],
                            'unit' => $line[9],
                            'city' => $line[10],
                            'province' => $line[11],
                            'state' => $line[12],
                            'country' => $line[13],
                            'zip' => $line[14],
                            'active' => $line[15]
                        ];
                    } else {
                        break;
                    }
                }
            }
            for ($i = 0; $i < count($to_be_added); $i++) {
                $company = new Company();
                $company->plan_number = $to_be_added[$i]['plan_number'];
                $company->name = $to_be_added[$i]['name'];
                $company->email = $to_be_added[$i]['email'];
                $company->fax = $to_be_added[$i]['fax'];
                $company->phone = $to_be_added[$i]['phone'];
                $company->work_phone = $to_be_added[$i]['work_phone'];
                $company->aoh_phone = $to_be_added[$i]['aoh_phone'];
                $company->website = $to_be_added[$i]['website'];
                $company->active = $to_be_added[$i]['active'];
                $company->network_active = 0;

                if ($company->save()) {
                    // Company Address
                    $companyId = $company->id;
                    if(Country::where('name', $to_be_added[$i]['country'])->first()) {
                        $countryCode = Country::where('name', $to_be_added[$i]['country'])->first()->code;
                    } else {
                        return response()->json(['success' => 'error', 'message' => 'Country code is not matching.']);
                    }
                    $addressData = [
                        'address_type_id' => '3',
                        'company_id' => $companyId,
                        'street' => $to_be_added[$i]['website'],
                        'unit' => $to_be_added[$i]['unit'],
                        'city' => $to_be_added[$i]['city'],
                        'province' => $to_be_added[$i]['province'],
                        'state' => $to_be_added[$i]['state'],
                        'country' => $countryCode,
                        'zip' => $to_be_added[$i]['zip'],
                        'phone' => $to_be_added[$i]['phone']
                    ];

                    if ($addressData['street'] || $addressData['city']) {
                        $geocoder = app('geocoder')->geocode($addressData['street'] . ' ' . $addressData['city'] . ' ' . $addressData['state'] . ' ' . $addressData['country'] . ' ' . $addressData['zip'])->get()->first();
                        if ($geocoder) {
                            $coordinates = $geocoder->getCoordinates();
                            $addressData['latitude'] = $coordinates->getLatitude();
                            $addressData['longitude'] = $coordinates->getLongitude();
                        }
                    }

                    $company->addresses()->create($addressData);
                    $total++;
                    $companyIds[] = $company->id;
                } else {
                    return response()->json(['success'=> 'error', 'message' => 'Something unexpected happened.']);
                }
            }
            $ids = '';
            foreach($companyIds as $companyId)
            {
                $ids .= $companyId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 3,
                'action_id' => 1,
                'count' => $total,
                'ids' => $ids,
            ]);

            if (count($dup_plans)) return response()->json(['success' => 'warning', 'message' => 'Duplicate Plan Numbers are '.join(', ', $dup_plans)]);

            return response()->json(['success' => 'success', 'message' => $total.' Companies are added.']);

        }
        return response()->json(['success'=> 'error', 'message' => 'File not found.']);
    }
    // end save company data csv

    public function importVrp($id){
        $companyVrp = VrpPlan::where('plan_number', $id)->first();
        if($companyVrp){
            $company = Company::where('plan_number', $id)->orWhere('name', $companyVrp->plan_holder)->first();
            if($company){
                return response()->json(['success' => false, 'message' => 'Already exists a company with such name or plan number']);
            }else{
               $companyCreated = Company::create([
                    'qi_id' => 0,
                    'operating_company_id' => 0,
                    'networks_active' => 0,
                    'vendor_active' => 0,
                    'plan_number' => $companyVrp->plan_number,
                    'name' => $companyVrp->plan_holder,
                    'smff_service_id' => 0,
                ]);

               $address = CompanyAddress::create([
                   'address_type_id' => 3,
                   'company_id' => $companyCreated->id,
                   'street' => $companyVrp->holder_address_1,
                   'city' => $companyVrp->holder_city,
                   'state' => $companyVrp->holder_state,
                   'country' => Country::where('name', $companyVrp->holder_country)->first()->code,
                   'zip' => $companyVrp->holder_zip,
               ]);

                return response()->json(['success' => true, 'message' => 'Company imported successfully']);

            }
        } else {
            return response()->json(['success' => true, 'message' => 'There is not a company with such id']);
        }
    }

    public function updateVendorType(){
        $company = Company::whereId(request('company_id'))->first();
        if($company){
            $company->update(['vendor_type' => request('vendor_type_id')]);
            $company->save();
            return response()->json(['success' => true, 'message' => 'Vendor Type updated successfully']);
        }
        return response()->json(['success' => false, 'message' => 'Vendor was not updated']);
    }

    public function importVendors(){
        //ini_set('max_execution_time', -1);
        $vendors = Vendor::all();
        Schema::disableForeignKeyConstraints();
        foreach($vendors as $vendor){
            //Inserting the vendor record inside of companies table
            //Inserting the vendor record inside of companies table
            $company = Company::create([
              'name' => $vendor->name,
              'email' => $vendor->company_email,
              'fax' => $vendor->fax,
              'phone' => $vendor->phone,
              'notes' => $vendor->notes,
              'vendor_active' => 1,
              'vendor_type' => $vendor->vendor_type_id,
              'shortname' => $vendor->shortname,
              'networks_active' => 0,
              'active' => 0,
            ]);

            //Update relation between vendors and vessels
            VesselVendor::where('company_id',$vendor->id)->update(['company_id' => $company->id]);
            Company::where('qi_id',$vendor->id)->update(['qi_id' => $company->id]);
            echo $vendor->id . "\n";
        }
        Schema::enableForeignKeyConstraints();

    }

    public function getPlanPreparer()
    {
        return PlanPreparer::all();
    }

    public function saveOpaNetwork($id, Request $request)
    {
        NetworkCompanies::where([['network_id', 1], ['company_id', $id]])->update([
            'contracted_company_id' => request('contracted_company_id'),
            'unique_identification_number_djs' => request('unique_identification_number_djs'),
            'unique_identification_number_ardent' => request('unique_identification_number_ardent'),
        ]);

        return response()->json(['success' => true, 'message' => 'OPA-90 Network updated.']);
    }

    public function getOpaNetwork()
    {
        $opaCompanies =  NetworkCompanies::where('network_id', 1)->get();
        $data = [];
        foreach($opaCompanies as $opaCompany)
        {
            $companyInfo = Company::where('id', $opaCompany->company_id)->first();
            if($companyInfo) {
                $data[] = [
                    'id' => $companyInfo->id,
                    'name' => $companyInfo->name,
                ];
            }
        }

        return $data;
    }

    public function getOpaNetworkCompanyCodes($id)
    {
        return  NetworkCompanies::where([['network_id', 1], ['company_id', $id], ['active', 1]])->first();
    }
}
