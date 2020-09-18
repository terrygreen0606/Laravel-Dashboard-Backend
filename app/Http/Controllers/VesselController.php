<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Company;
use App\Models\VesselFleets;
use App\Models\Network;
use App\Models\Capability;
use App\Models\CapabilityValue;
use App\Models\Vessel;
use App\Models\VesselListIndex;
use App\Models\Vrp\Vessel as VrpVessel;
use App\Models\User;
use App\Models\VesselType;
use App\Models\Vrp\VrpPlan;
use App\Models\Vendor;
use App\Models\VesselVendor;
use App\Models\TrackChange;
use App\Models\ChangesTableName;
use App\Models\Action;
use App\Helpers\VRPExpressVesselHelper;
use App\Http\Resources\NoteResource;
use Intervention\Image\ImageManagerStatic as Image;
use App\Http\Resources\CapabilityResource;
use App\Http\Resources\VesselIndexResource;
use App\Http\Resources\VesselResource;
use App\Http\Resources\VesselListResource;
use App\Http\Resources\VesselShortResource;
use App\Http\Resources\VesselShowAISResource;
use App\Http\Resources\VesselShowConstructionDetailResource;
use App\Http\Resources\VesselShowDimensionsResource;
use App\Http\Resources\VesselShowResource;
use App\Http\Resources\VesselTrackResource;
use App\Http\Resources\VesselPollResource;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use stdClass;
use App\Helpers\MTHelper;
use App\Models\VesselAISPositions;
use Illuminate\Support\Facades\DB;

class VesselController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function getAll(Request $request)
    {
        //return Vessel::with('vendors')->paginate(10);
        $query = $request->get('query');
        $page = (int)$request->get('page');

        $per_page = $request->get('per_page');
        $sort = $request->has('sortBy') ? $request->get('sortBy') : 'updated_at';
        $sortDir = $request->has('direction') ? $request->get('direction') : 'desc';
        $bulkSelected =  $request->get('bulkSelected');

        $vrp = Auth::user()->hasVRP();
        $vrpUnlinked = $vrp;

        $defaultModel = new Vessel;

        $cdtVesselsTable = $this->getVesselModal(null, $defaultModel);
        $cdtVesselTableName = $defaultModel->table();

        $companyTable = new Company;
        $companyTableName = $companyTable->table();

        $vesselTypeTable = new VesselType;
        $vesselTypeTableName = $vesselTypeTable->table();

        $cdtVessels = $cdtVesselsTable->from($cdtVesselTableName . ' AS v1')->select(
            DB::raw(empty($vrp) ? Vessel::FIELDS_CDT : Vessel::UNION_FIELDS_CDT))
                ->distinct()
                ->leftJoin($vesselTypeTableName . " AS t", 'v1.vessel_type_id', '=', 't.id')
                ->leftJoin($companyTableName . " AS c1", 'v1.company_id','=','c1.id')
                ->leftjoin('capabilities AS vs', function($join) {
                            $join->on('v1.smff_service_id','=','vs.id');
                            $join->on('vs.status','=',DB::raw('1'));
                        });

        if (!empty($query) && strlen($query) > 2) {
            $cuids = Vessel::search($query)->get('id')->pluck('id');
            if(intval($query) !== 0) {
                $cdtVessels->whereIn('v1.id', $cuids)->orWhere('c1.plan_number', $query);
            } else {
                $cdtVessels->whereIn('v1.id', $cuids);
            }

        }

        if ($request->has('staticSearch')) {
            $this->staticSearch($cdtVessels, $request->get('staticSearch'));
        }
        if (empty($vrp)) {
            $totalCount = $cdtVessels->count();
            $itemsPerPage = $per_page > 0 ? $per_page : $totalCount;

            $resultsQuery = $cdtVessels
                ->orderBy($sort, $sortDir)
                ->forPage($page, $itemsPerPage);
        } else {
            $vrpVesselTable = new VrpVessel;
            $vrpVesselTableName = $vrpVesselTable->table();

            $planTable = new VrpPlan;
            $planTableName = $planTable->table();
            $cdtVesselsOff = clone $cdtVessels;
            $cdtVessels = $cdtVessels
                    ->leftJoin($vrpVesselTableName . " AS vrpv", function($join) {
                        $join->on('v1.imo','=','vrpv.imo');
                        $join->on('c1.plan_number','=','vrpv.plan_number_id');
                    })
                    ->leftJoin($planTableName . " AS p", 'vrpv.plan_number_id','=','p.plan_number')
                    ->whereNull('v1.official_number');
            $cdtVesselsOff = $cdtVesselsOff
                    ->leftJoin($vrpVesselTableName . " AS vrpv", function($join) {
                        $join->on('v1.official_number','=','vrpv.official_number');
                        $join->on('c1.plan_number','=','vrpv.plan_number_id');
                    })
                    ->leftJoin($planTableName . " AS p", 'vrpv.plan_number_id','=','p.plan_number')
                    ->whereNotNull('v1.official_number');

            $cdtCount = $cdtVessels->count() + $cdtVesselsOff->count();

            $vrpCount = 0;
            if ($vrp) {
                if(request('staticSearch')['merge'] == -1) {
                    $vrpVessels = VrpVessel::from($vrpVesselTableName . " AS vrpv2")
                        ->select(DB::raw(Vessel::UNION_FIELDS_VRP))
                        ->leftJoin($planTableName . " AS p", 'vrpv2.plan_number_id','=','p.id')
                        ->leftJoin($companyTableName . " AS c2", 'p.plan_number','=','c2.plan_number')
                        // ->whereRaw('((vrpv2.imo IS NOT NULL AND vrpv2.imo NOT IN (SELECT imo FROM ' . $cdtVesselTableName . ' AS vx WHERE vx.imo IS NOT NULL AND vx.active = 1)) OR (vrpv2.imo IS NULL AND vrpv2.official_number IS NOT NULL AND vrpv2.official_number NOT IN (SELECT official_number FROM ' .$cdtVesselTableName. ' AS vx2 WHERE vx2.official_number IS NOT NULL AND vx2.active = 1)))')
                        ->whereRaw('((vrpv2.imo IS NOT NULL AND vrpv2.imo NOT IN (SELECT imo FROM ' . $cdtVesselTableName . ' AS vx WHERE vx.imo IS NOT NULL)) OR (vrpv2.imo IS NULL AND vrpv2.official_number IS NOT NULL AND vrpv2.official_number NOT IN (SELECT official_number FROM ' .$cdtVesselTableName. ' AS vx2 WHERE vx2.official_number IS NOT NULL)))')
                        ->distinct();
                } else {
                    $vrpVessels = VrpVessel::from($vrpVesselTableName . " AS vrpv2")
                        ->select(DB::raw(Vessel::UNION_FIELDS_VRP))
                        ->leftJoin($planTableName . " AS p", 'vrpv2.plan_number_id','=','p.id')
                        ->leftJoin($companyTableName . " AS c2", 'p.plan_number','=','c2.plan_number')
                        ->distinct();
                }

                if ($request->has('staticSearch')) {
                    $filter = $this->staticSearchVrpVessels($vrpVessels, $request->get('staticSearch'));
                }

                if (!empty($query) && strlen($query) > 2) {
                    if(preg_match('/[\'^£$%&*( )}{@#~?><>,|=_+¬-]/', $query, $specialChar)) {
                        $strings = explode($specialChar[0], $query);
                        $uids = VrpVessel::where([['vessel_name', 'like', '%' . $strings[0] . '%'], ['vessel_name', 'like', '%' . $strings[1] . '%']])->get('id')->pluck('id');
                        $vrpVessels->whereIn('vrpv2.id', $uids);
                    } else {
                        $uids = VrpVessel::search($query)->get('id')->pluck('id');
                        $vrpVessels->whereIn('vrpv2.id', $uids);
                    }
                }

                $vrpCount = $vrpVessels->count();

                $cdtVessels = $cdtVessels
                    ->union($cdtVesselsOff)
                    ->union($vrpVessels);
            }
            $totalCount = $cdtCount + $vrpCount;
            $itemsPerPage = $per_page > 0 ? $per_page : $totalCount;

            if (!empty($query) && strlen($query) > 2) {
                $resultsQuery = $cdtVessels
                    ->orderBy('linked', 'desc')
                    ->orderBy('djs', 'desc')
                    ->orderBy('auth', 'desc')
                    ->forPage($page, $itemsPerPage);
            } else {
                $resultsQuery = $cdtVessels
                    ->orderBy($sort, $sortDir)
                    ->forPage($page, $itemsPerPage);
            }
        }
        $results = [];
        if($request->has('bulkSelected') && !empty($bulkSelected)) {
            $resultsQuery = $cdtVessels
                    ->orderBy($sort, $sortDir)
                    ->forPage(1, $totalCount);
            $results = VesselListResource::collection(
                new LengthAwarePaginator($resultsQuery->get(), $totalCount, $totalCount, 1)
            );
        } else {
            $results = VesselListResource::collection(
                new LengthAwarePaginator($resultsQuery->get(), $totalCount, $itemsPerPage, $page)
            );
        }

        return $results;
    }

    private function staticSearch($model, $staticSearch)
    {
        if ($staticSearch['active'] !== -1) {
            $model = $model->where('v1.active', (boolean)$staticSearch['active']);
        }

        if ($staticSearch['vrp_status'] !== -1) {
            $model->whereNotNull('vrp_id');
        }

        if (array_key_exists('resource_provider', $staticSearch) && $staticSearch['resource_provider'] !== -1) {
            if ($staticSearch['resource_provider']) {
                $model = $model->whereRaw('(vs.id IS NOT NULL)');
            } else {
                $model = $model->whereRaw('(vs.id IS NULL)');
            }
        }

        if (count($staticSearch['types'])) {
            $model = $model->whereIn('v1.vessel_type_id', $staticSearch['types']);
        }

        if (array_key_exists('fleets', $staticSearch) && count($staticSearch['fleets'])) {
            $model = $model->join('vessels_fleets AS vf', 'v1.id', '=', 'vf.vessel_id')->whereIn('vf.fleet_id', $staticSearch['fleets']);
        }

        $index = 1;
        foreach (['vendors', 'qi', 'pi', 'response', 'societies', 'insurers', 'providers'] as $v_type) {
            if (array_key_exists($v_type, $staticSearch) && count($staticSearch[$v_type])) {
                $model = $model
                    ->join('vessels_vendors AS vv' . $index,
                        'v1.id', '=', 'vv' . $index . '.vessel_id')
                    ->whereIn('vv' . $index . '.company_id', $staticSearch[$v_type]);
                $index++;
            }
        }


        if (array_key_exists('company', $staticSearch)) {
            $ids[] = $staticSearch['company'];
            if (array_key_exists('operated', $staticSearch) && $staticSearch['operated']) {
                $ids = Company::where('operating_company_id', $staticSearch['company'])->pluck('id')->toArray();
            }
            $model = $model->whereIn('c1.id', $ids);
        }

        if (array_key_exists('companies', $staticSearch) && count($staticSearch['companies'])) {
            $model = $model->whereIn('c1.id', $staticSearch['companies']);
        }

        if (array_key_exists('networks', $staticSearch) && count($staticSearch['networks'])) {
            $model = $model
                ->join('network_companies AS nc', 'c1.id', '=', 'nc.company_id')
                ->where('c1.networks_active', 1)
                ->whereIn('nc.network_id', $staticSearch['networks']);
        }

        return $model;
    }


    private function staticSearchVrpVessels($model, $staticSearch)
    {
        $hasVendor = false;
        foreach (['vendors', 'qi', 'pi', 'response', 'societies', 'insurers', 'providers'] as $v_type) {
            if (array_key_exists($v_type, $staticSearch) && count($staticSearch[$v_type])) {
                $hasVendor = true;
            }
        }

        if (
            $hasVendor ||
            count($staticSearch['types']) ||
            count($staticSearch['fleets']) ||
            count($staticSearch['networks']) ||
            (intval($staticSearch['active']) !== -1) ||
            (array_key_exists('resource_provider', $staticSearch) && $staticSearch['resource_provider'] !== -1) ||
            (array_key_exists('include_vrp', $staticSearch) && $staticSearch['include_vrp'] !== -1)
                ) {
            $model->whereRaw('0=1');
        } else {
            if ($staticSearch['vrp_status'] !== -1) {
                $statusSearch = '';
                if ($staticSearch['vrp_status'] === 1) {
                    $statusSearch = 'Authorized';
                } else if ($staticSearch['vrp_status'] === 0) {
                    $statusSearch = 'Not Authorized';
                }
                $model = $model->where('vessel_status', $statusSearch);
            }

            if (array_key_exists('companies', $staticSearch) && count($staticSearch['companies'])) {
                $model = $model->whereIn('c2.id', $staticSearch['companies']);
            }

            if (array_key_exists('company', $staticSearch)) {
                $model = $model->where('c2.id', $staticSearch['company']);
            }

            if (count($staticSearch['types'])) {

            }
        }
        return $model;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return AnonymousResourceCollection
     */
    public function show($id)
    {
        $defaultModel = new Vessel;

        $cdtVesselsTable = $this->getVesselModal(null, $defaultModel);
        $cdtVesselTableName = $defaultModel->table();

        $companyTable = new Company;
        $companyTableName = $companyTable->table();

        $vesselTypeTable = new VesselType;
        $vesselTypeTableName = $vesselTypeTable->table();

        $vessel = $cdtVesselsTable
            ->from($cdtVesselTableName . ' AS v1')
            ->select(DB::raw(Vessel::FIELDS_CDT . ", v1.*"))
            ->leftJoin($vesselTypeTableName . " AS t", 'v1.vessel_type_id', '=', 't.id')
            ->leftJoin($companyTableName . " AS c1", 'v1.company_id','=','c1.id')
            ->leftjoin('capabilities AS vs', function($join) {
                $join->on('v1.smff_service_id','=','vs.id');
                $join->on('vs.status','=',DB::raw('1'));
            })
            ->where('v1.id', $id)
            ->get();
        return VesselShowResource::collection($vessel);
    }


    public function showVRP($id)
    {
        $vrp = Auth::user()->hasVRP();

        if (!$vrp) {
            return response()->json(null);
        }

        $vessel = Vessel::find($id);
        $company = $vessel->company;
        $vrp = VrpVessel::join('vrp_plan', 'plan_number_id', '=', 'vrp_plan.id')
             ->where('vrp_plan.plan_number', $company->plan_number)
            ->where('imo', $vessel->imo)
            ->first();

        return response()->json(!empty($vrp) ? [
            'vrp_status' => $vrp->vessel_status,
            'imo' => $vrp->imo,
            'official_number' => $vrp->official_number,
            'vessel_status' => $vrp->vessel_status,
            'vrp_plan_status' => $vrp->vrpPlan->status,
            'vrp_plan_number' => $vrp->vrpPlan->plan_number,
            'vessel_is_tank' => $vrp->vessel_is_tank === 'NT' ? 0 : 1,
            'vrp_count' => VrpVessel::where('imo', $vessel->imo)->count(),
            'vessel_name' => $vrp->vessel_name,
            'vessel_type' => $vrp->vessel_type,
            'plan_holder' => $vrp->vrpPlan->plan_holder ?? '',
            'primary_smff' => $vrp->vrpPlan->primary_smff ?? '',
            'wcd_barrels' => $vrp->wcd_barrels
        ] : null);
    }

    public function indexShort($id)
    {
        return VesselShortResource::collection($this->getVesselModal()->where('id', $id)->get());
    }

    public function getAllShort()
    {
        return VesselIndexResource::collection($this->getVesselModal()->get());
    }

    public function getAllUnderCompanyShort($cid)
    {
        return VesselIndexResource::collection(Vessel::where('company_id', $cid)->where('active',1)->get());
    }

    public function getRelatedList(Request $request)
    {//lead_ship_id
        $query = $request->get('query');

        if (strlen($query) < 3) {
            return [];
        }

        $ids = Vessel::search($query)->get('id')->pluck('id');
        return VesselShortResource::collection($this->getVesselModal()->whereIn('id', $ids)->where('lead_ship', 0)->get());
    }

    public function getParentList(Request $request)
    {//lead_ship_id
        $query = $request->get('query');

        if (strlen($query) < 3) {
            return [];
        }

        $ids = Vessel::search($query)->get('id')->pluck('id');
        return VesselShortResource::collection($this->getVesselModal()->whereIn('id', $ids)->where('lead_ship', 1)->get());
    }

    public function getSisterList()
    {//isLeadShip=false&noLeadShip=true&noChildShip=true
        return VesselShortResource::collection($this->getVesselModal()->where('lead_ship', 0)->whereNull('lead_ship_id')->get());
    }

    public function getChildVesselsList()
    {//isLeadShip=false&noLeadShip=true&noSisterShip=true
        //->whereNull('lead_ship_id')
        return VesselShortResource::collection($this->getVesselModal()->where('lead_ship', 0)->whereNull('lead_sister_ship_id')->get());
    }

    public function getSisterVesselsList()
    {//isLeadShip=false&noLeadShip=true&noChildShip=true
        return VesselShortResource::collection($this->getVesselModal()->where('lead_ship', 0)->whereNull('lead_ship_id')->get());
    }

    public function storePhoto(Vessel $vessel, Request $request)
    {
        $this->validate($request, [
            'file' => [
                'mimes:png,jpg,jpeg',
            ]
        ]);

        $frect = $request->file('file_rect');
        $fsqr = $request->file('file_sqr');

        $image_rect = Image::make($frect->getRealPath());
        $image_sqr = Image::make($fsqr->getRealPath());

        $directory = 'pictures/vessels/' . $vessel->id . '/';

        $name1 = 'cover_rect.jpg';
        $name2 = 'cover_sqr.jpg';

        if (
            Storage::disk('gcs')->put($directory . $name2, (string)$image_sqr->encode('jpg'), 'public') &&
            Storage::disk('gcs')->put($directory . $name1, (string)$image_rect->encode('jpg'), 'public')
        ) {
            $vessel->has_photo = true;
            $vessel->save();
            return response()->json(['message' => 'Picture uploaded.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function destroyPhoto(Vessel $vessel)
    {
        $directory = 'pictures/vessels/' . $vessel->id . '/';
        if (
            Storage::disk('gcs')->delete($directory . 'cover_rect.jpg') &&
            Storage::disk('gcs')->delete($directory . 'cover_sqr.jpg')
        ) {
            $vessel->has_photo = false;
            $vessel->save();
            return response()->json(['message' => 'Picture deleted.']);
        }
        return response()->json(['message' => 'Can not delete a company photo.']);
    }

    public function unAssignedVessel(Vessel $vessel)
    {
        $unAssignedVessel = Vessel::select('id','name')->get();
        return response()->json(['message' => 'Unassigned vessels list','vessels'=>$unAssignedVessel]);
    }



    public function assignMultipleVessel(Request $request)
    {
       $vessels_id = request('vessel.vessel_ids');
       foreach ($vessels_id as $key => $value) {
        Vessel::where('id',$value)
                ->update(['company_id' => request('vessel.company_id')]);
       }
        return response()->json(['message' => 'Company Added Successfully']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $vessel = new Vessel();
        $vessel->name = request('name');
        $vessel->imo = request('imo_number');
        $vessel->mmsi = request('mmsi_number');
        $vessel->company_id = request('company');
        $vessel->official_number = request('official_number');
        $vessel->sat_phone_primary = $request->input('phone_primary');
        $vessel->sat_phone_secondary = $request->input('phone_secondary');
        $vessel->email_primary = $request->input('email_primary');
        $vessel->email_secondary = $request->input('email_secondary');
        $vessel->tanker = (boolean)request('is_tank');
        $vessel->vessel_type_id = request('type');
        $vessel->dead_weight = request('dead_weight');
        $vessel->deck_area = request('deck_area');
        $vessel->oil_group = request('oil_group');
        $vessel->oil_tank_volume = request('oil_tank_volume');
        $vessel->primary_poc_id = request('primary_contact');
        $vessel->secondary_poc_id = request('secondary_contact');
        $vessel->active = (request('active'))?request('active'):0;
        $vessel->lead_ship_id = (request('parent_ship') && !request('is_lead'))?request('parent_ship'):0;
        $vessel->lead_sister_ship_id = (request('sister_ship') && !request('is_lead'))?request('sister_ship'):0;
        $vessel->ais_timestamp = '0000-00-00 00:00:00';

        if(request('is_lead')) {
            $vessel->lead_ship = 1;
        }

        if (!request('permitted')) {
            if ($request->has('imo_number') && (int)request('imo_number') != 0 && Vessel::where('imo', request('imo_number'))->first()) {
                return response()->json(['message' => 'That IMO already exists.', 'success' => false]);
            }

            if ($request->has('official_number') && (int)request('official_number') != 0 &&  Vessel::where('official_number', request('official_number'))->first()) {
                return response()->json(['message' => 'That Official Number already exists.', 'success' => false]);
            }
        }

        if ($vessel->save())
        {
            $qi = (request('qi_company'))?request('qi_company'):array();
            $pi = (request('pi_club'))?request('pi_club'):array();
            $societies =  (request('society'))?request('society'):array();
            $insurers =  (request('insurer'))?request('insurer'):array();
            $providers = (request('ds_provider'))?request('ds_provider'):array();
            $vessel->vendors()->attach($qi);
            $vessel->vendors()->attach($pi);
            $vessel->vendors()->attach($societies);
            $vessel->vendors()->attach($insurers);
            $vessel->vendors()->attach($providers);

            if (request('is_lead'))
            {
                if(request('sister_vessel'))
                {
                    foreach (\request('sister_vessel') as $id)
                    {
                        Vessel::find($id)->update(['lead_sister_ship_id' => $vessel->id]);
                    }
                }
                if(request('child_vessel'))
                {
                    foreach (\request('child_vessel') as $id) {
                        Vessel::find($id)->update(['lead_ship_id' => $vessel->id]);
                    }
                }
            }
            $vessel->fleets()->sync(\request('fleet'));

            $vessel->notes()->create([
                'note' => $request->input('comments'),
                'note_type' => 1,
                'user_id' => Auth::user()->id,
                'vessel_id' => $vessel->id
            ]);

            /*Image upload to S3*/
            if($request->image){
                $reqest_image = $request->image;
                $imageInfo = explode(";base64,", $reqest_image);
                $image1 = str_replace(' ', '+', $imageInfo[1]);

                $image = Image::make($image1);
                $image->fit(720, 405);

                $directory = 'pictures/vessels/' . $vessel->id . '/';

                $name = 'cover.jpg';

                if (Storage::disk('gcs')->put($directory.$name, (string)$image->encode('jpg'), 'public')) {
                    $vessel->photo = $name;
                    $vessel->save();
                }
            }

            $vesselIds = [];
            $vesselIds[] = $vessel->id;
            $ids = '';
            foreach($vesselIds as $vesselId)
            {
                $ids .= $vesselId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 2,
                'action_id' => 1,
                'count' => 1,
                'ids' => $ids,
            ]);
            return response()->json(['message' => 'Vessel added.', 'id' => $vessel->id, 'success' => true]);
        }
        return response()->json(['message' => 'Something unexpected happened.', 'success' => false]);
    }

    public function storeSMFF($id)
    {
        $vessel = Vessel::find($id);
        $smff = null;

        if ($vessel) {
            if ($vessel->smff_service_id) {
                $smff = Capability::find($vessel->smff_service_id);
                if (empty($smff)) {
                    // error???
                } else {
                    $smff->status = 1; // undelete
                    $smff->save();
                }
            } else {
                if ($vessel->company && $vessel->company->smffCapability) {
                    $smff_copy = $vessel->company->smffCapability->replicate();
                    $vessel->smff_service_id = Capability::create($smff_copy->toArray())->id;
                } else {
                    $vessel->smff_service_id = Capability::create()->id;
                }
            }
            return $vessel->save() ? response()->json(['message' => 'SMFF Capabilities created.']) : response()->json(['message' => 'Could not create SMFF Capabilities.']);
        }

        return response()->json(['message' => 'No vessel found.'], 404);
    }

    public function toggleStatus(Vessel $vessel)
    {
        $vessel->active = !$vessel->active;
        if ($vessel->save()) {
            return response()->json(['message' => 'Vessel status changed.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function toggleTanker(Vessel $vessel)
    {
        $vessel->tanker = !$vessel->tanker;
        if ($vessel->save()) {
            return response()->json(['message' => 'Vessel tanker status changed.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }


    public function showConstructionDetail($id)
    {
        return VesselShowConstructionDetailResource::collection(Vessel::where('id', $id)->get());
    }

    public function showAIS($id)
    {
        return VesselShowAISResource::collection(Vessel::where('id', $id)->get());
    }

    public function showSMFF($id)
    {
        $vessel = Vessel::where('id', $id)->first();
        $smff =  $vessel->smff();
        $networks = $vessel->networks;
        return response()->json([
            'vessel' => $vessel->smff_service_id,
            'smff' => $smff,
            'networks' => $networks->pluck('code'),
            'serviceItems' => Capability::primaryServiceAvailable()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {

        $vessel = Vessel::find($id);
        if ($request->has('name')) $vessel->name = request('name');
        if ($request->has('imo')) $vessel->imo = request('imo');
        if ($request->has('official_number')) $vessel->official_number = request('official_number');
        if ($request->has('mmsi')) $vessel->mmsi = request('mmsi');
        if ($request->has('vessel_type_id')) $vessel->vessel_type_id = request('vessel_type_id');
        if ($request->has('dead_weight')) $vessel->dead_weight = request('dead_weight');
        if ($request->has('tanker')) $vessel->tanker = request('tanker');
        if ($request->has('active')) $vessel->active = request('active');
        if ($request->has('deck_area')) $vessel->deck_area = request('deck_area');
        if ($request->has('oil_tank_volume')) $vessel->oil_tank_volume = request('oil_tank_volume');
        if ($request->has('oil_group')) $vessel->oil_group = request('oil_group');
        if ($request->has('company_id')) $vessel->company_id = request('company_id');
        if ($request->has('primary_poc_id')) $vessel->primary_poc_id = request('primary_poc_id');
        if ($request->has('secondary_poc_id')) $vessel->secondary_poc_id = request('secondary_poc_id');
        if ($request->has('sat_phone_primary')) $vessel->sat_phone_primary = request('sat_phone_primary');
        if ($request->has('sat_phone_secondary')) $vessel->sat_phone_secondary = request('sat_phone_secondary');
        if ($request->has('email_primary')) $vessel->email_primary = request('email_primary');
        if ($request->has('email_secondary')) $vessel->email_secondary = request('email_secondary');

        if ($vessel->save()) {
            if ($request->has('qi')) {
                $vessel->vendors()->detach();
                $vessel->vendors()->attach(array_merge(
                    request('qi'),
                    request('pi'),
                    request('societies'),
                    request('insurers'),
                    request('providers')
                ));
            }

            if ($request->has('fleet_id')) {
                $vessel->fleets()->sync(request('fleet_id'));
            }
            $vesselIds = [];
            $vesselIds[] = $vessel->id;
            $ids = '';
            foreach($vesselIds as $vesselId)
            {
                $ids .= $vesselId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 2,
                'action_id' => 3,
                'count' => 1,
                'ids' => $ids,
            ]);

            return response()->json(['message' => 'Vessel updated.']);
        }
        return response()->json(['message' => 'Can\'t save. Something unexpected happened.']);
    }

    public function importVrp($id)
    {
        $vrpVessel = VrpVessel::whereId($id)->first();

        if($vrpVessel){
            $vessel = Vessel::whereImo($vrpVessel->imo)->first();
            $type = VesselType::where('name',$vrpVessel->vessel_type)->first();
            $type_id = $type ? $type->id : null;
            if(!$type_id){
                $vessel_type_new = VesselType::create(['name' => $vrpVessel->vessel_type]);
                $type_id = $vessel_type_new->id;
            }
            $vrpPlan = VrpPlan::where('id',$vrpVessel->plan_number_id)->first();
            $company = null;
            if($vrpPlan){
                $company = Company::where('plan_number',$vrpPlan->plan_number)->first();
            }

            if($vessel){
                if($vessel->name != $vrpVessel->vessel_name){
                    $vessel->update([
                        'name' => $vrpVessel->vessel_name,
                        'imo' => $vrpVessel->imo,
                        'official_number' => $vrpVessel->official_number,
                        'company_id' => isset($company) ? $company->id : null,
                        'tanker' => in_array($vrpVessel->vessel_is_tank,array('NT','SMPEP','SOPEP','NT/SMPEP','NT/SOPEP')) ? 0:1,
                        'mmsi' => $vrpVessel->mmsi,
                        'vessel_type_id' => $type_id
                    ]);
                    $vessel->save();
                    return response()->json(['success' => true,'message' => 'VRP entry updated on cdt']);
                }else{
                    return response()->json(['success' => false,'message' => 'Already exists a vessels with the same name']);
                }

            }else{
                Vessel::create([
                    'name' => $vrpVessel->vessel_name,
                    'imo' => $vrpVessel->imo,
                    'official_number' => $vrpVessel->official_number,
                    'company_id' => isset($company) ? $company->id : null,
                    'tanker' => $vrpVessel->vessel_is_tank != 'NT' ? 1:0,
                    'mmsi' => $vrpVessel->mmsi,
                    'vessel_type_id' => $type_id
                ]);
                return response()->json(['success' => true,'message' => 'VRP entry created on cdt']);
            }
        }

        return response()->json(['success' => false,'message' => 'VRP entry does not exist']);


    }

    public function updateDimensions(Request $request, $id)
    {

        $vessel = Vessel::find($id);
        if ($request->has('construction_length_overall')) $vessel->construction_length_overall = request('construction_length_overall');
        if ($request->has('construction_length_bp')) $vessel->construction_length_bp = request('construction_length_bp');
        if ($request->has('construction_length_reg')) $vessel->construction_length_reg = request('construction_length_reg');
        if ($request->has('construction_bulbous_bow')) $vessel->construction_bulbous_bow = request('construction_bulbous_bow');
        if ($request->has('construction_breadth_extreme')) $vessel->construction_breadth_extreme = request('construction_breadth_extreme');
        if ($request->has('construction_breadth_moulded')) $vessel->construction_breadth_moulded = request('construction_breadth_moulded');
        if ($request->has('construction_draught')) $vessel->construction_draught = request('construction_draught');
        if ($request->has('construction_depth')) $vessel->construction_depth = request('construction_depth');
        if ($request->has('construction_height')) $vessel->construction_height = request('construction_height');
        if ($request->has('construction_tcm')) $vessel->construction_tcm = request('construction_tcm');
        if ($request->has('construction_displacement')) $vessel->construction_displacement = request('construction_displacement');

        if ($vessel->save()) {
            return response()->json(['message' => 'Dimensions Updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function updateProviders(Request $request, $id)
    {
        $vessel = Vessel::find($id);
        $dscp_ids = $vessel->vendors()->whereHas('type', function ($q) {
            $q->where('name', 'Damage Stability Certificate Provider');
        })->pluck('id')->toArray();
        $vessel->vendors()->detach($dscp_ids);
        if (request('providers')) {
            $vessel->vendors()->syncWithoutDetaching(request('providers'));
        }
        return response()->json(['message' => 'Providers Updated.']);
    }

    public function updateConstructionDetail(Request $request, $id)
    {
        $vessel = Vessel::find($id);
        $vessel->lead_ship = request('lead_ship');
        $vessel->lead_ship_id = request('lead_ship_id');
        $vessel->lead_sister_ship_id = request('lead_sister_ship_id');
        if ($vessel->save()) {
            if ($vessel->lead_ship) {
                foreach (\request('sister_vessels') as $idv) {
                    Vessel::find($idv)->update(['lead_sister_ship_id' => $vessel->id]);
                }
                foreach (\request('child_vessels') as $idv) {
                    Vessel::find($idv)->update(['lead_ship_id' => $vessel->id]);
                }
            }
            $dscp_ids = $vessel->vendors()->whereHas('type', function ($q) {
                $q->where('name', 'Damage Stability Certificate Provider');
            })->pluck('id')->toArray();
            $vessel->vendors()->detach($dscp_ids);
            $vessel->vendors()->syncWithoutDetaching(request('providers'));
            $vessel->fleets()->sync(\request('fleets'));
            return response()->json(['message' => 'Vessel construction detail updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function makeLead(Request $request, $id)
    {
        $vessel = Vessel::find($id);
        $vessel->lead_ship = request('lead_ship');
        if ($vessel->save()) {
            return response()->json(['message' => 'Lead relation updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function updateRelation(Request $request, $id)
    {
        $vessel = Vessel::find($id);
        $vessel->lead_ship = request('lead_ship');
        if (request('child_vessel')) {
            Vessel::find(request('child_vessel'))->update(['lead_ship_id' => $vessel->id]);
        } else if (request('sister_vessel')) {
            Vessel::find(request('sister_vessel'))->update(['lead_sister_ship_id' => $vessel->id]);
        } else if (request('parent')) {
            $vessel->lead_ship_id = request('parent');
        } else {
            $vessel->lead_sister_ship_id = request('lead_sister');
        }
        if ($vessel->save()) {
            return response()->json(['message' => 'A new relation added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function removeRelation(Request $request)
    {
        $current_vessel = Vessel::find(request('vessel_id'));
        $related_vessel = Vessel::find(request('id'));
        if (request('type') == 'child') {
            $related_vessel->update(['lead_ship_id' => null]);
        } else if (request('type') == 'sister') {
            $related_vessel->update(['lead_sister_ship_id' => null]);
        } else if (request('type') == 'lead_parent') {
            $current_vessel->update(['lead_ship_id' => null]);
        } else {
            $current_vessel->update(['lead_sister_ship_id' => null]);
        }
        return response()->json(['message' => 'Successfully removed.']);
    }

    public function updateAIS(Request $request, $id)
    {
        $vessel = Vessel::find($id);
        $vessel->latitude = request('latitude');
        $vessel->longitude = request('longitude');
        if ($vessel->save()) {
            $vessel->fleets()->sync(\request('fleets'));
            return response()->json(['message' => 'Vessel AIS data updated.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function updateSMFF(Request $request, $id)
    {
        $vessel = Vessel::find($id);
        $capabilities = Capability::find($vessel->smff_service_id);
        if (!$capabilities) {
            $this->storeSMFF($id);
            $vessel = Vessel::find($id);
            $capabilities = Capability::find($vessel->smff_service_id);
        }
        $capabilities->status = 1;
        $smffFields = request('smff');
        if (!$capabilities->updateValues(
            isset($smffFields['primary_service']) ? $smffFields['primary_service'] : null,
            isset($smffFields['notes']) ? $smffFields['notes'] : null,
            $smffFields)) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        return response()->json(['message' => 'Vessel SMFF Capabilities updated.']);
    }

    public function updateNetwork(Request $request, $id)
    {
        $vessel = Vessel::find($id);
        $network_ids = Network::whereIn('code', request('networks'))->pluck('id');
        if ($vessel->networks()->sync($network_ids)) {
            return response()->json(['message' => 'Vessel Network Membership Updated.']);
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
        $vessel = Vessel::find($id);
        if ($vessel) {
            //delete Damage Stability Models
            if ($vessel->company()->first()) {
                $dms = 'files/damage_stability_models/' . $vessel->company()->first()->id . '/' . $vessel->id . '/';
                Storage::deleteDirectory($dms);
            }

            VesselFleets::where('vessel_id',$id)->delete();
            VesselVendor::where('vessel_id', $id)->delete();

            $vesselIds = [];
            $vesselIds[] = $id;
            $ids = '';
            foreach($vesselIds as $vesselId)
            {
                $ids .= $vesselId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 2,
                'action_id' => 2,
                'count' => 1,
                'ids' => $ids,
            ]);
            return $vessel->delete() ? response()->json(['message' => 'Vessel deleted.']) : response()->json(['message' => 'Could not delete vessel.']);
        }

        return response()->json(['message' => 'No vessel found.'], 404);
    }

    public function destroySMFF($id)
    {
        $vessel = Vessel::find($id);
        if ($vessel) {
            $smff = Capability::find($vessel->smff_service_id);
            if (!empty($smff)) {
                $smff->status = 0;
            }
            return empty($smff) || $smff->save() ? response()->json(['message' => 'SMFF Capabilities deleted.']) : response()->json(['message' => 'Could not delete SMFF Capabilities.']);
        }

        return response()->json(['message' => 'No vessel found.'], 404);
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function getBySearch()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $uids = Vessel::search(request()->query('query'))->get('id');
        $ids = array();
        foreach ($uids as $u) {
            $ids[] = $u->id;
        }
        $vessels = $this->staticSearch($this->getVesselModal()->whereIn('id', $ids), \request('staticSearch'))->paginate($per_page);
        $vessels = $this->vrpStats($vessels);
        return VesselResource::collection($vessels);
    }

    public function getBySearchWithVRP(Request $request)
    {

        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $uids = Vessel::search(request()->query('query'))->get('id');
        $ids = array();
        foreach ($uids as $u) {
            $ids[] = $u->id;
        }
        $staticSearch = \request('staticSearch');
        $vessel_model = $this->staticSearch($this->getVesselModal()->latest()->whereIn('id', $ids)->with('Company:id,plan_number'), $staticSearch);
        $vessels = $vessel_model->get();
        $vessels = $this->vrpStats($vessels);

        $exclude_ids = [];

        foreach ($vessels as $vessel) {
            if ($vessel->vrp_count === 1) {
                $exclude_ids[] = $vessel->imo;
            }
        }

        //initiate paginator
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // Create a new Laravel collection from the array data merged with VRPexpress search
        $itemCollection = collect($this->vrpSearch($vessels, $exclude_ids, request()->query('query'), $staticSearch['vrp_status']));

        // Slice the collection to get the items to display in current page
        $currentPageItems = $itemCollection->slice(($currentPage * $per_page) - $per_page, $per_page)->all();

        // Create our paginator and pass it to the view
        $paginatedItems = new LengthAwarePaginator(array_values($currentPageItems), count($itemCollection), $per_page);

        // set url path for generted links
        $paginatedItems->setPath($request->url());

        return $paginatedItems;
    }

// @todo: for removing smff data from user's who don't have smff data permission from backend
    private function getVesselModal ($fleet_id=null, $model=null) {
        if (!$model) {
            $model = Vessel::latest();
        }
        $role_id = Auth::user()->role_id;
        $userInfo = Auth::user();
        switch ($role_id) {
            case Role::COMPANY_PLAN_MANAGER : // Company Plan Manager
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
                return $model->where('v1.active', 1)->whereIn('company_id', $ids);
            case Role::QI_COMPANIES : // QI Companies
                return $model->where('v1.active', 1)->join('vessels_vendors AS vv', 'v1.id', '=', 'vv.vessel_id')
                        ->whereIn('vv.company_id', Company::where([['id', $userInfo->primary_company_id], ['vendor_active', 1],['vendor_type', 3]])->pluck('id'));
            case Role::VESSEL_VIEWER : // Vessel viewer
                // return $model->whereIn('company_id', Company::where('qi_id', Auth::user()->vendor_id)->pluck('id'));
            case Role::COAST_GUARD : // Coast Guard
                return $model->where('v1.active', 1)->whereIn('company_id', Company::where('active', 1)->pluck('id'));
            case Role::NAVY_NASA :
                return $model->whereIn('company_id', Company::where('networks_active', 1)->orWhere('smff_service_id', '<>', 0)->pluck('id'));
            case Role::ADMIN : // falls through
            case Role::DUTY_TEAM :
                return $model;
        }
        if($fleet_id){
            return $model->whereHas('vessels_fleets', function($q) use ($fleet_id){
                $q->where('fleet_id', '=', $fleet_id);
            });
        }

        return null;
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function getByOrder()
    {
        $per_page = empty(request('per_page')) ? 10 : (int)request('per_page');
        $direction = request()->query('direction');
        $sortBy = request()->query('sortBy');
        $vessels = $this->staticSearch($this->getVesselModal()->orderBy($sortBy, $direction), \request('staticSearch'))->paginate($per_page);
        $vessels = $this->vrpStats($vessels);
        return VesselResource::collection($vessels);
    }

    public function getVesselsUnderPlan(Company $company)
    {
        ini_set('memory_limit','2048M');
        //include the VRP Express vessels under these companies
        //pass the plans, get the vessels
        $vessels = [];
        $plan_number = $company->plan_number;
        if ($plan_number) {
            try {
                $exclude_imo = Vessel::whereHas('company', static function ($q) use ($plan_number) {
                    $q->where('plan_number', $plan_number);
                })->pluck('imo');
//                $client = new Client();
//                $res = $client->request('POST', 'https://35.184.163.31/api/vessels/underPlan', ['json' => ['plan_number' => $plan_number, 'exclude_imo' => $exclude_imo], 'stream' => true, 'timeout' => 0, 'read_timeout' => 10]);
//                $vessels = json_decode($res->getBody()->getContents(), true);
                $vessels = json_decode(json_encode(VRPExpressVesselHelper::getVesselsUnderPlan($plan_number, $exclude_imo)),true);
            } catch (\Exception $error) {

            }
        }
        return $vessels;
    }

    private function vrpStats($vessels)
    {
        try {
            $filteredVessels = $vessels->filter(function ($vessel, $key) {
                return $vessel->imo !== null;
            });
            $vessel_data = [];
            foreach ($filteredVessels as $filteredVessel) {
                $vessel_data[] = [
                    'id' => $filteredVessel->id,
                    'imo' => $filteredVessel->imo,
                    'plan_number' => $filteredVessel->company->plan_number,
                    'official_number' => $filteredVessel->official_number
                ];
            }
//            $client = new Client();
//            $res = $client->request('POST', 'http://35.184.163.31/api/vessels', ['json' => ['vessel_data' => $vessel_data], 'stream' => true, 'timeout' => 0, 'read_timeout' => 10]);
//            $vrp_data = json_decode($res->getBody()->getContents(), true);
            $vrp_data = json_decode(json_encode(VRPExpressVesselHelper::getVessels($vessel_data)),true);
//            return $vessel_data;
            foreach ($vessels as $vessel) {
                if ($vessel->imo !== null && isset($vrp_data[$vessel->id])) {
                    $vessel->vrp_status = $vrp_data[$vessel->id]['status'];
                    $vessel->vrp_comparison = $vrp_data[$vessel->id]['vrp_comparison'];
                    $vessel->vrp_plan_number = $vrp_data[$vessel->id]['plan_number'];
                    $vessel->vrp_vessel_is_tank = $vrp_data[$vessel->id]['vessel_is_tank'];
                    $vessel->vrp_count = $vrp_data[$vessel->id]['vrp_count'];
                    $vessel->plan_holder = $vrp_data[$vessel->id]['plan_holder'];
                    $vessel->vrp_express = false;
                    $vessel->primary_smff = $vrp_data[$vessel->id]['primary_smff'];
                }
            }
        } catch (\Exception $error) {
//            return $error->getMessage();
        }
        return $vessels;
    }

    private function vrpSearch($vessels, $exclude_ids, $query, $vrp_status)
    {
        $vrp_vessels = [];
        try {
//            $client = new Client();
//            $res = $client->request('POST', 'http://35.184.163.31/api/vessels/search', ['json' => ['query' => $query, 'exclude_ids' => $exclude_ids, 'vrp_status' => $vrp_status], 'stream' => true, 'timeout' => 0, 'read_timeout' => 10]);
//            $vrp_data = json_decode($res->getBody()->getContents(), true);
            $vrp_data = json_decode(json_encode(VRPExpressVesselHelper::getVesselsBySearch($query, $exclude_ids, $vrp_status)),true);
            foreach ($vrp_data as $vessel) {
                $vessel = (object)$vessel;
                $loop[] = $vessel->imo;
                $vrp_vessels[] = [
                    'id' => -1,
                    'imo' => $vessel->imo,
                    'official_number' => $vessel->official_number,
                    'company' => [
                        'plan_number' => '',
                        'id' => -1
                    ],
                    'vrp_status' => $vessel->vessel_status ?? '',
                    'vrp_comparison' => $vessel->vrp_comparison ?? 'N/A',
                    'vrp_plan_number' => $vessel->vrp_plan_number ?? '',
                    'vrp_vessel_is_tank' => $vessel->vessel_is_tank,
                    'vrp_count' => $vessel->vrp_count ?? 0,
                    'name' => $vessel->vessel_name,
                    'type' => $vessel->vessel_type,
                    'resource_provider' => false,
                    'active' => false,
                    'fleets' => [],
                    'vrp_express' => true,
                    'coverage'    => str_contains(strtolower($vessel->primary_smff), 'donjon') ? 1 : 0,
//                    'response'   => $vessel->smff_service_id ? 1 : 0
                ];
            }
        } catch (\Exception $error) {

        }
        $merged_vessels = [];
        foreach ($vessels as $vessel) {
            $merged_vessels[] = [
                'id' => $vessel->id,
                'imo' => $vessel->imo,
                'official_number' => $vessel->official_number,
                'company' => [
                    'plan_number' => trim($vessel->company->plan_number) ? $vessel->company->plan_number : '',
                    'id' => $vessel->company->id
                ],
                'vrp_status' => $vessel->vrp_status ?? '',
                'vrp_comparison' => $vessel->vrp_comparison ?? 'N/A',
                'vrp_plan_number' => $vessel->vrp_plan_number ?? '',
                'vrp_vessel_is_tank' => $vessel->vrp_vessel_is_tank,
                'vrp_count' => $vessel->vrp_count ?? 0,
                'name' => $vessel->name,
                'type' => $vessel->type ? $vessel->type->name : 'Unknown',
                'tanker' => (boolean)$vessel->tanker,
                'resource_provider' => $vessel->smffCapability ? true : false,
                'active' => (boolean)$vessel->active,
                'fleets' => $vessel->fleets()->pluck('fleets.id'),
                'vrp_express' => $vessel->vrp_express ? true : false,
                'response' => $vessel->smff_service_id ? 1 : 0,
                'coverage' => $vessel->active,
            ];
        }
        $merged_vessels = array_merge($merged_vessels, $vrp_vessels);
        return $merged_vessels;
    }

    public function addNote(Vessel $vessel, Request $request)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        $this->validate($request, [
            'note_type' => 'required',
            'note' => 'required'
        ]);

        $note = $vessel->notes()->create([
            'note_type' => request('note_type'),
            'note' => request('note'),
            'user_id' => Auth::id()
        ]);

        if ($note) {
            return response()->json(['message' => 'Note added.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function getNotes(Vessel $vessel)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        return NoteResource::collection($vessel->notes()->get());
    }

    public function destroyNote(Vessel $vessel, $id)
    {
        if (!Auth::user()->isAdminOrDuty()) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
        if ($vessel->notes()->find($id)->delete()) {
            return response()->json(['message' => 'Note deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function saveFleets(Vessel $vessel, Request $request)
    {
        if ($vessel->fleets()->sync(\request('fleets'))) {
            return response()->json(['message' => 'Vessel Fleet saved.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function getUploadedFiles(Vessel $vessel, $location, $year)
    {
        $files = [];
        $company_id = isset($vessel->company()->first()->id) ? $vessel->company()->first()->id : null;

        $location == 'racs'
            ? $directory = 'files/new/' . $location . '/' . $vessel->id . '/' . $year . '/'
            : $directory = 'files/new/' . $location . '/' . $vessel->id . '/';
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

    public function uploadVesselFiles(Vessel $vessel, $location, $year, Request $request)
    {
        $fileName = $request->file->getClientOriginalName();
        $location == 'racs'
            ? $directory = 'files/new/' . $location . '/' . $vessel->id . '/' . $year . '/'
            : $directory = 'files/new/' . $location . '/' . $vessel->id . '/';

        if (Storage::disk('gcs')->exists($directory . $fileName)) {
            $fileName = date('m-d-Y_h:ia - ') . $fileName;
        }
        if (Storage::disk('gcs')->putFileAs($directory, \request('file'), $fileName)) {
            return response()->json(['message' => 'File uploaded.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function deleteSingleVesselFile(Vessel $vessel, $location, $year, $fileName)
    {
        $location == 'racs'
            ? $directory = 'files/new/' . $location . '/' . $vessel->id . '/' . $year . '/'
            : $directory = 'files/new/' . $location . '/' . $vessel->id . '/';
        if (Storage::disk('gcs')->delete($directory . $fileName)) {
            return response()->json(['message' => 'File deleted.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function deleteAllVesselFiles(Vessel $vessel, $location, $year, Request $request)
    {
        $removeData = $request->all();
        for($i = 0; $i < count($removeData); $i ++) {
            $location == 'racs'
                ? $directory = 'files/new/' . $location . '/' . $vessel->id . '/' . $year . '/'
                : $directory = 'files/new/' . $location . '/' . $vessel->id . '/';
            Storage::disk('gcs')->delete($directory . $removeData[$i]['name']);
        }
        return response()->json(['message' => 'Files are deleted.']);
    }

    public function downloadVesselFile(Vessel $vessel, $location, $year, $fileName)
    {
        $location == 'racs'
            ? $directory = 'files/new/' . $location . '/' . $vessel->id . '/' . $year . '/'
            : $directory = 'files/new/' . $location . '/' . $vessel->id . '/';

        return response()->streamDownload(function() use ($directory, $fileName) {
            echo Storage::disk('gcs')->get($directory . $fileName);
        }, $fileName, [
                'Content-Type' => 'application/octet-stream'
            ]);
    }

    public function getFilesCount(Request $request)
    {
        $files = [];
        $vesselNames = [
            'prefire_plans',
            'drawings',
            'damage_stability_models',
            'racs'
        ];
        foreach($vesselNames as $vesselName) {
            foreach ($request->ids as $id) {
                if ($id) {
                    $vessel = Vessel::find($id);
                    if ($vesselName == 'racs') {
                        for ($i = 0; $i < 3; $i++) {
                            $vesselDirectory = 'files/new/' . $vesselName . '/' . $id . '/' . (string)(date("Y") - $i) . '/';
                            $filesInFolder = Storage::disk('gcs')->files($vesselDirectory);
                            $files[$vesselName][$id][(string)(date("Y") - $i)] = count($filesInFolder);
                        }
                    } else {
                        $vesselDirectory = 'files/new/' . $vesselName . '/' . $id . '/';
                        $filesInFolder = Storage::disk('gcs')->files($vesselDirectory);
                        $files[$vesselName][$id] = count($filesInFolder);
                    }
                }
            }
        }
        return response()->json($files);
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

    public function transferToCompany(Request $request)
    {
        $vessels = Vessel::whereIn('id', \request('vessel_ids'));
        foreach ($vessels->get() as $vessel) {
            if (\request('company_id') !== $vessel->company_id) {
                $this->vesselFilesToCompany($vessel, \request('company_id'));
            }
        }
        if ($vessels->update(['company_id' => \request('company_id')])) {
            return response()->json(['message' => 'Success. ' . $vessels->count() . ' vessel(s) were assigned under a new company.']);
        }
        return response()->json(['message' => 'Something unexpected happened.']);
    }

    public function bulkAction()
    {
        $company_id = \request('action')['company'];
        $insurers = \request('action')['insurers'];
        $vessels = Vessel::whereIn('id', \request('vessel_ids'));
        try {
            foreach ($vessels->get() as $vessel) {
                if ($company_id && $company_id !== $vessel->company_id) {
                    $this->vesselFilesToCompany($vessel, $company_id);
                }
                if (count($insurers)) {
                    $vessel->vendors()->detach($insurers);
                    $vessel->vendors()->attach($insurers);
                }
            }
            if ($company_id) {
                $vessels->update(['company_id' => $company_id]);
            }
            return response()->json(['message' => 'Successfully updated ' . $vessels->count() . ' vessel(s).']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Something unexpected happened.']);
        }
    }

    private function vesselFilesToCompany($vessel, $company_id)
    {
        $vesselLocations = [
            'prefire_plans',
            'drawings',
            'damage_stability_models',
            date("Y"),
            date("Y") - 1,
            date("Y") - 2
        ];
        foreach ($vesselLocations as $location) {
            $folder = 'files/' . $location . '/' . $vessel->company_id . '/' . $vessel->id . '/';
            $files = Storage::disk('gcs')->files($folder);
            foreach ($files as $file) {
                Storage::disk('gcs')->move($file, 'files/' . $location . '/' . $company_id . '/' . $vessel->id . '/' . pathinfo($file)['filename']);
            }
        }
    }

    public function bulkImport()
    {
        $filename = storage_path('app/new_smff.csv');
        $file = fopen($filename, "r");
        while (($data = fgetcsv($file, 200, ',')) !== FALSE) {
            $id = $data[0];
            $s_heavy_lift = $data[1];
            $s_lifting_gear_minimum_swl = $data[2];
            //create smff for each vessel
            $load_data = [
                's_heavy_lift' => $s_heavy_lift,
                's_lifting_gear_minimum_swl' => $s_lifting_gear_minimum_swl
            ];
            $vessel = Vessel::where('id', $id)->withCount('smffCapability')->first();
            if ($vessel->smff_capability_count === 0) {
                $capability = Capability::create($load_data);
                $vessel->smff_service_id = $capability->id;
                $vessel->save();
            } else {
                $vessel->smffCapability()->update($load_data);
            }
        }
        fclose($file);
    }

    // store csv
    public function storeCSV(Request $request,Vessel $vessel)
    {
        if($request->hasFile('file'))
        {
            $path = $request->file('file')->getPathName();
            $csvFile = fopen($path, 'r');
            $total = 0;
            $dup_imos = [];
            $dup_officials = [];
            $first = 0;
            $vesselIds = [];
            $to_be_added = [];
            while(($row = fgetcsv($csvFile)) !== FALSE)
            {
                if ($first < 3) {
                    $first++;
                    continue;
                } else {
                    // Check all values in the last row are empty
                    if (!empty(array_filter($row, function ($value) { return $value != ""; }))) {
                        if ( (int)$row[1] != 0 || (int)$row[2] != 0 ) {
                            if($row[1] != "") {
                                if (Vessel::where('imo', $row[1])->first()) {
                                    array_push($dup_imos, $row[1]);
                                    continue;
                                }
                            }
                            if($row[2] != "") {
                                if (Vessel::where('official_number', $row[2])->first()) {
                                    array_push($dup_officials, $row[2]);
                                    continue;
                                }
                            }
                        }
                        $to_be_added[] = [
                            'name' => $row[0],
                            'imo' => (int)$row[1] != 0 ? (int)$row[1] : NULL,
                            'official_number' => (int)$row[2] != 0 ? (int)$row[2] : NULL,
                            'company_id' => (int)$row[3],
                            'sat_phone_primary' => $row[4],
                            'sat_phone_secondary' => $row[5],
                            'email_primary' => $row[6],
                            'email_secondary' => $row[7],
                            'vessel_type_id' => (int)$row[8],
                            'society' => $row[9],
                            'pi_club' => $row[10],
                            'hm_insurer' => $row[11],
                            'damage_stability' => $row[12],
                            'oil_group' => $row[13],
                            'dead_weight' => $row[14],
                            'deck_area' => $row[15],
                            'oil_tank_volume' => $row[16],
                            'tanker' => $row[17] == "YES" ? 1 : 0,
                            'active' => $row[18] == "YES" ? 1 : 0,
                        ];
                    } else {
                        break;
                    }
                }
            }
            for ($i = 0; $i < count($to_be_added); $i++) {
                $vessel = new Vessel();
                $vessel->name = $to_be_added[$i]['name'];
                $vessel->imo = $to_be_added[$i]['imo'];
                $vessel->official_number = $to_be_added[$i]['official_number'];
                $vessel->company_id = $to_be_added[$i]['company_id'];
                $vessel->sat_phone_primary = $to_be_added[$i]['sat_phone_primary'];
                $vessel->sat_phone_secondary = $to_be_added[$i]['sat_phone_secondary'];
                $vessel->email_primary = $to_be_added[$i]['email_primary'];
                $vessel->email_secondary = $to_be_added[$i]['email_secondary'];
                $vessel->vessel_type_id = $to_be_added[$i]['vessel_type_id'];
                $vessel->dead_weight = $to_be_added[$i]['dead_weight'];
                $vessel->deck_area = $to_be_added[$i]['deck_area'];
                $vessel->oil_tank_volume = $to_be_added[$i]['oil_tank_volume'];
                $vessel->oil_group = $to_be_added[$i]['oil_group'];
                $vessel->tanker = $to_be_added[$i]['tanker'];
                $vessel->active = $to_be_added[$i]['active'];
                $vessel->ais_timestamp = '0000-00-00 00:00:00';

                if($vessel->save()) {
                    $vendorCompanies = $to_be_added[$i]['society'] . ',' . $to_be_added[$i]['pi_club'] . ',' . $to_be_added[$i]['hm_insurer'] . ',' . $to_be_added[$i]['damage_stability'];
                    $vendorCompanies = explode(",", $vendorCompanies);

                    foreach($vendorCompanies as $vendorCompany)
                    {
                        $company_id = intval($vendorCompany);
                        if(Company::where('id', $company_id)->first()) {
                            VesselVendor::create([
                                'vessel_id' => $vessel->id,
                                'company_id' => $company_id,
                            ]);
                        }
                    }

                    $total++;
                    $vesselIds[] = $vessel->id;
                } else {
                    return response()->json(['success'=> 'error', 'message' => 'Something unexpected happened.']);
                }
            }
            $ids = '';
            foreach($vesselIds as $vesselId)
            {
                $ids .= $vesselId.',';
            }
            $ids = substr($ids, 0, -1);
            TrackChange::create([
                'changes_table_name_id' => 2,
                'action_id' => 1,
                'count' => $total,
                'ids' => $ids,
            ]);
            if (count($dup_imos) || count($dup_officials)) {
                $message = '';
                if (count($dup_imos) && count($dup_officials)) $message = 'Duplicate IMOs are '.join(', ', $dup_imos).' and '.'Duplicate Official Numbers are '.join(', ', $dup_officials);
                else if (count($dup_imos)) $message = 'Duplicate IMOs are '.join(', ', $dup_imos);
                else if (count($dup_officials)) $message = 'Duplicate Official Numbers are '.join(', ', $dup_officials);
                return response()->json(['success' => 'warning', 'message' => $message]);
            }
            return response()->json(['success' => 'success', 'message' => $total.' Vessels are added.']);
        }
        return response()->json(['success'=> 'error', 'message' => 'File not found.']);
    }
    // end store csv

    // Get duplicated vessel with IMO
    public function getDuplicateIMOVessel($number, $flag)
    {
        if ($number && (int)$number != 0) {
            $flag
                ? $duplicates = Vessel::where('imo', $number)->first()
                : $duplicates = Vessel::where('official_number', $number)->first();
            if ($duplicates) {
                return response()->json(['success' => false]);
            }
        }
        return response()->json(['success' => true]);
    }

    public function getVesselInfo() {
        return response()->json(Vessel::select('id', 'imo', 'name')->get());
    }

    public function getVRPdata($id)
    {
        try {
            $vrp_data = null;
            $vrp_data = json_decode(json_encode(VRPExpressVesselHelper::getVesselsUnderPlanById($id)), true);
        } catch (\Exception $error) {
        }

        return $vrp_data;
    }

    // return latest positions from vessel_ais_positions table
    public function getLatestAISPositions(Request $request)
    {
        $search = $request['search'];
        $perPage = $request['per-page'] ?? 10;
        $page = $request['page'] ?? 1;
        $orderBy = $request['sort-by'] ?? 'ais_timestamp';
        $orderDir = $request['direction'] ?? 'desc';

        $vesselsQuery = DB::table('vessels as v1')
            ->select('v1.id', 'v1.name', 'v1.ais_lat', 'v1.ais_long', 'v1.ais_timestamp')
            ->join('vessel_ais_positions as vap', 'v1.id', '=', 'vap.vessel_id')
            ->distinct()
            ->leftJoin('vessel_types as t', 'v1.vessel_type_id', '=', 't.id')
            ->leftJoin('companies as c1', 'v1.company_id', '=', 'c1.id')
            ->leftJoin('capabilities as vs', function($join) {
                $join->on('v1.smff_service_id', '=', 'vs.id');
                $join->on('vs.status', '=', DB::raw('1'));
            });

        if (!empty($search) && strlen($search) > 2) {
            $ids = Vessel::search($search)->get('id')->pluck('id');
            $vesselsQuery = $vesselsQuery->whereIn('v1.id', $ids);
        }

        if ($request->has('staticSearch')) {
            $this->staticSearch($vesselsQuery, $request['staticSearch']);
        }

        $total = count($vesselsQuery->get());

        $vessels = $vesselsQuery
            ->orderBy($orderBy, $orderDir)
            ->forPage($page, $perPage)
            ->get();

        foreach ($vessels as $vessel) {
            $latest = VesselAISPositions
                ::where([
                    ['vessel_id', $vessel->id],
                    ['timestamp', $vessel->ais_timestamp],
                ])
                ->first();
            $vessel->ais_dsrc = $latest['dsrc'];
        }

        return response()->json([
            'data' => $vessels,
            'total' => $total,
        ]);
    }

    // Sister Vessel Csv file import
    public function sisterVesselImport(Request $request)
    {
        if($request->hasFile('file'))
        {
            $path = $request->file('file')->getPathName();
            $csvFile = fopen($path, 'r');
            $first = 0;
            $imos = [];
            $leadShipId = 0;
            $existImo = '';

            while(($row = fgetcsv($csvFile)) !== FALSE)
            {
                if ($first < 1) {
                    $first++;
                    continue;
                } else {
                    if($row[1] == 1) {
                        $leadShipId = Vessel::where('imo', $row[0])->first()->id;
                        Vessel::where('id', $leadShipId)->update([
                            'lead_ship' => 1
                        ]);
                    } else {
                        $imos[] = $row[0];
                    }
                }
            }

            if($leadShipId) {
                foreach($imos as $imo)
                {
                    if(Vessel::where('imo', $imo)->first()) {
                        Vessel::where('imo', $imo)->update([
                            'lead_sister_ship_id' => $leadShipId
                        ]);
                    } else {
                        if($existImo == '') {
                            $existImo = $existImo . $imo;
                        } else {
                            $existImo = $existImo . ', ' . $imo;
                        }
                    }
                }
                if($existImo == '') {
                    // upload success
                    return response()->json(['success' => 'success', 'message' => 'Vessels are updated.']);
                } else {
                    // warning
                    return response()->json(['success' => 'warning', 'message' => 'Doesn\'t exist vessels: ' . $existImo]);
                }
            } else {
                // upload error
                return response()->json(['success' => 'error', 'message' => ' Import Failed: Lead Ship does not exist.']);
            }
        }
    }

    // Capabilities Import
    public function capabilitiesImport(Request $request)
    {
        if($request->hasFile('file'))
        {
            $path = $request->file('file')->getPathName();
            $csvFile = fopen($path, 'r');
            $first = 0;
            $imos = [];
            $leadShipId = 0;
            $existImo = '';

            while(($row = fgetcsv($csvFile)) !== FALSE)
            {
                if ($first < 1) {
                    $first++;
                    continue;
                } else {
                    $existVessel = Vessel::where('imo', $row[0])->first();
                    if($existVessel) {
                        if(!$existVessel->smff_service_id) {
                            $capability = new Capability();
                            $capability->status = 1;
                            $capability->save();
                            Vessel::where('imo', $row[0])->update([
                                'smff_service_id' => $capability->id,
                            ]);
                            // CapabilityValue::create([
                            //     'capabilities_id' => $capability->id,
                            //     'field_id' => 20,
                            //     'value' => $row[11],
                            // ]);
                            CapabilityValue::create([
                                'capabilities_id' => $capability->id,
                                'field_id' => 23,
                                'value' => $row[1],
                            ]);
                            CapabilityValue::create([
                                'capabilities_id' => $capability->id,
                                'field_id' => 18,
                                'value' => 1,
                            ]);
                        }
                    }
                }
            }
            return response()->json(['success' => 'success', 'message' => 'Vessels Capabilities are updated.']); 
        }
    }
}
